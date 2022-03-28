/*
 * Copyright 2021-present Open Networking Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package nctu.winlab.ProxyArp;

import com.google.common.collect.ImmutableSet;
import org.onosproject.cfg.ComponentConfigService;
import org.osgi.service.component.ComponentContext;
import org.osgi.service.component.annotations.Activate;
import org.osgi.service.component.annotations.Component;
import org.osgi.service.component.annotations.Deactivate;
import org.osgi.service.component.annotations.Modified;
import org.osgi.service.component.annotations.Reference;
import org.osgi.service.component.annotations.ReferenceCardinality;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import static org.onlab.util.Tools.get;

import org.onosproject.net.config.NetworkConfigRegistry;
import org.onosproject.core.CoreService;
import org.onosproject.net.host.HostService;
import org.onosproject.net.topology.TopologyService;
import org.onosproject.net.flowobjective.FlowObjectiveService;
import org.onosproject.net.packet.PacketService;
import org.onosproject.core.ApplicationId;
import org.onosproject.net.flow.TrafficSelector;
import org.onosproject.net.flow.DefaultTrafficSelector;
import org.onosproject.net.packet.PacketPriority;
import org.onosproject.net.packet.PacketProcessor;
import org.onosproject.net.packet.PacketContext;
import org.onosproject.net.packet.InboundPacket;
import org.onlab.packet.Ethernet;
import org.onosproject.net.HostId;
import org.onosproject.net.Host;
import org.onlab.packet.VlanId;
import org.onlab.packet.ARP;
import org.onlab.packet.IPv4;
import org.onlab.packet.Ip4Prefix;
import org.onosproject.net.PortNumber;
import org.onosproject.net.edge.EdgePortService;
import org.onosproject.net.ConnectPoint;
import org.onlab.packet.MacAddress;
import org.onosproject.net.device.DeviceService;
import org.onosproject.net.Device;
import org.onlab.packet.IpAddress;
import org.onlab.packet.Ip4Address;
import org.onosproject.net.flow.TrafficTreatment;
import org.onosproject.net.flow.DefaultTrafficTreatment;
import org.onosproject.net.packet.DefaultOutboundPacket;

import java.util.Dictionary;
import java.util.Properties;
import java.util.HashMap;
import java.util.Map;
import java.util.Iterator;
import java.util.Set;
import java.nio.charset.StandardCharsets;
import java.lang.Character;
import java.lang.Integer;
import java.nio.ByteBuffer;


import static org.onlab.util.Tools.get;

/**
 * Skeletal ONOS application component.
 */
@Component(immediate = true)
public class AppComponent {

    //packet service
    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected PacketService packetService;

    //network configuration service
    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected NetworkConfigRegistry netCfgService;

    //core service
    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected CoreService coreService;

    //host service
    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected HostService hostService;

    //topology service
    @Reference(cardinality = ReferenceCardinality.MANDATORY) 
    protected TopologyService topologyService;

    //flow objective service
    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected FlowObjectiveService flowObjectiveService;

    //edge port service
    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected EdgePortService edgePortService;

    //device service
    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected DeviceService deviceService;


    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected ComponentConfigService cfgService;





    //packet processor
    private PktProcessor pktProcessor = new PktProcessor();
  
    //app id
    private ApplicationId appId;
    private final Logger log = LoggerFactory.getLogger(getClass());


    /** Some configurable property. */
    private String someProperty;

    

    // table for (IP,MAC)
    Map<String, String> ipmac_table = new HashMap<String, String>();
    Map<String, String> port_table = new HashMap<String, String>();



    @Activate
    protected void activate() {
	appId = coreService.registerApplication("nctu.winlab.ProxyArp");
        //cfgService.registerProperties(getClass());
	packetService.addProcessor(pktProcessor, PacketProcessor.director(2));
	requestIntercepts();
        log.info("Started");
    }

    private void requestIntercepts(){
        TrafficSelector.Builder selector = DefaultTrafficSelector.builder();
        selector.matchEthType(Ethernet.TYPE_IPV4);
        packetService.requestPackets(selector.build(),PacketPriority.REACTIVE, appId);
    }
    
    
    private void withdrawIntercepts(){
        TrafficSelector.Builder selector = DefaultTrafficSelector.builder();
        selector.matchEthType(Ethernet.TYPE_IPV4);
        packetService.cancelPackets(selector.build(), PacketPriority.REACTIVE, appId);
    }


    @Deactivate
    protected void deactivate() {
        //cfgService.unregisterProperties(getClass(), false);
	withdrawIntercepts();
	packetService.removeProcessor(pktProcessor);
	pktProcessor = null;
	log.info("Stop");
    }


    private int arp_req = 0;
    private long port_request = 0;


    private class PktProcessor implements PacketProcessor{
	@Override
        public void process(PacketContext context){
	    if(context.isHandled()){
	        return;
            }

	    InboundPacket pkt = context.inPacket();
	    Ethernet ethPkt = pkt.parsed();

	    if(ethPkt == null){
	        return;
	    }

	    HostId id = HostId.hostId(ethPkt.getDestinationMAC(), VlanId.vlanId(ethPkt.getVlanID()));
	    Host dst = hostService.getHost(id);

	    if(id.mac().isLldp()){
	        return;    
	    }


	    if(ethPkt.getEtherType() == Ethernet.TYPE_ARP){

		ARP arp_pkt = (ARP) ethPkt.getPayload();

		// if it's a ARP request
		if(arp_pkt.getOpCode() == 1){
		
			
		    // get IP address of ARP request receiver
		    byte[] buffer = arp_pkt.getTargetProtocolAddress();
		    String des_ip = Ip4Address.valueOf(buffer).toString(); 

		    if(arp_req == 0){
		        port_request = pkt.receivedFrom().port().toLong();
		        arp_req = 1;
		    }
		    
		    
	            // if target IP & target MAC is recorded in table
		    if(ipmac_table.containsKey(des_ip)){

		        // fetch MAC address of specific IP
			String des_mac = (String) ipmac_table.get(des_ip);

		        log.info("TABLE HIT. Requested MAC = {}",des_mac);

			// create a ARP reply packet to send back
			Ip4Address req_ip = Ip4Address.valueOf(des_ip);
			MacAddress req_mac = MacAddress.valueOf(des_mac);
			Ethernet arp_reply = ARP.buildArpReply(req_ip, req_mac, ethPkt);

			TrafficTreatment.Builder treatment = DefaultTrafficTreatment.builder();
			treatment.setOutput(PortNumber.portNumber(port_request));


		        // packet out to ARP request sender
			DefaultOutboundPacket out_pkt = new DefaultOutboundPacket(pkt.receivedFrom().deviceId(), treatment.build(), ByteBuffer.wrap(arp_reply.serialize()));
			packetService.emit(out_pkt);


	    		arp_req = 0;
		    }			

                    // if target IP & target MAC is not recorded in table
		    else{

			log.info("TABLE MISS. Send request to edge ports");			
			
	                // flood ARP request to edge port
			flood(context,des_ip);

		    }
		    

	        }
		// if it's a ARP reply
		else if(arp_pkt.getOpCode() == 2){


		    // get IP & MAC of ARP reply sender & parse ARP reply to get IP & MAC
		    byte[] buffer_ip_byte = arp_pkt.getSenderProtocolAddress();
		    byte[] buffer_mac_byte = arp_pkt.getSenderHardwareAddress();
		    String src_ip = Ip4Address.valueOf(buffer_ip_byte).toString();
		    String src_mac = MacAddress.valueOf(buffer_mac_byte).toString();
			
		    log.info("RECV REPLY. Requested MAC = {}",src_mac);

		    // recorded the IP & MAC of ARP reply sender in table
		    ipmac_table.put(src_ip, src_mac);

		    //log.info("==================================================");
		    //for(Map.Entry<String, String> entry : ipmac_table.entrySet()){
		    //log.info("========= {} : {} =========",entry.getKey(),entry.getValue());
		    //    		
		    //}
		    //log.info("==================================================");


	            // packet out the ARP reply to ARP request sender
	            packetOut(context,PortNumber.portNumber(port_request));

		    arp_req = 0;
		}
	    }
	}
    }


    private boolean isControlPacket(Ethernet eth){
	short type = eth.getEtherType();
	return type == Ethernet.TYPE_LLDP || type == Ethernet.TYPE_BSN;

    }


    private void flood(PacketContext context, String des_ip){

	// find all the connection points of hosts, only flood to switch on which hosts locate
	Iterable<ConnectPoint> devices = edgePortService.getEdgePoints();
	Iterator<ConnectPoint> device_it = devices.iterator();

	while(device_it.hasNext()){

	    ConnectPoint edge_now = device_it.next();
	    
	    // do not packet out to the switch of ARP request sender

	    //log.info("============== device of sender : {} ===========",context.inPacket().receivedFrom().deviceId().toString());
	    //log.info("===== device now : {} / {} =====",edge_now.deviceId().toString(),edge_now.port().toString());

	    Set<Host> hosts = hostService.getConnectedHosts(edge_now.deviceId());
	    Iterator<Host> host_it = hosts.iterator();

	    while(host_it.hasNext()){
		Host host_now = host_it.next();
		//log.info("============== host now : {} ===========",host_now.location().toString());
	        Set<IpAddress> ips = host_now.ipAddresses();
		Iterator<IpAddress> ip_it = ips.iterator();
		while(ip_it.hasNext()){
		    IpAddress ip_now = ip_it.next();
		    //log.info("============== ip of host now : {} ===========",ip_now.toString());
		}
	    }


	    
	    if(context.inPacket().receivedFrom().deviceId().toString() != edge_now.deviceId().toString()){
		packetOut(context, PortNumber.FLOOD);
	    }
	    else
		context.block();


	}


    }


    private void packetOut(PacketContext context, PortNumber port){
	context.treatmentBuilder().setOutput(port);
	context.send();
    }




    @Modified
    public void modified(ComponentContext context) {
        Dictionary<?, ?> properties = context != null ? context.getProperties() : new Properties();
        if (context != null) {
            someProperty = get(properties, "someProperty");
        }
        log.info("Reconfigured");
    }


}
