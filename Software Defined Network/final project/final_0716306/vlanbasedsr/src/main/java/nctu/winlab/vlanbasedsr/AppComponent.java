/*
 * Copyright 2022-present Open Networking Foundation
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
package nctu.winlab.vlanbasedsr;

import static org.onosproject.net.config.NetworkConfigEvent.Type.CONFIG_ADDED;
import static org.onosproject.net.config.NetworkConfigEvent.Type.CONFIG_UPDATED;
import static org.onosproject.net.config.basics.SubjectFactories.APP_SUBJECT_FACTORY;
import static org.onosproject.net.config.basics.SubjectFactories.CONNECT_POINT_SUBJECT_FACTORY;


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

import org.onosproject.core.ApplicationId;
import org.onosproject.core.CoreService;
import org.onosproject.net.config.ConfigFactory;
import org.onosproject.net.config.NetworkConfigEvent;
import org.onosproject.net.config.NetworkConfigListener;
import org.onosproject.net.config.NetworkConfigRegistry;


import org.onosproject.net.packet.PacketProcessor;
import org.onosproject.net.flow.TrafficSelector;
import org.onosproject.net.flow.DefaultTrafficSelector;
import org.onosproject.net.packet.PacketService;
import org.onosproject.net.flow.FlowRuleService;
import org.onosproject.net.packet.PacketContext;
import org.onlab.packet.MacAddress;
import org.onlab.packet.VlanId;
import org.onosproject.net.PortNumber;
import org.onosproject.net.HostId;
import org.onosproject.net.Host;
import org.onosproject.net.host.HostService;
import org.onosproject.net.Path;
import org.onosproject.net.Link;
import org.onosproject.net.topology.TopologyService;
import org.onlab.packet.IPv4;
import org.onlab.packet.UDP;
import org.onlab.packet.TpPort;
import org.onlab.packet.DHCP;
import org.onlab.packet.BasePacket;
import org.onosproject.net.flow.DefaultTrafficTreatment;
import org.onosproject.net.flow.TrafficTreatment; 
import org.onosproject.net.flowobjective.DefaultForwardingObjective;
import org.onosproject.net.flowobjective.ForwardingObjective;
import org.onlab.packet.Ethernet;
import org.onosproject.net.packet.PacketPriority;
import org.onosproject.net.packet.InboundPacket;
import org.onlab.packet.Ip4Prefix;
import org.onosproject.net.flowobjective.FlowObjectiveService;
import org.onosproject.net.ConnectPoint;
import org.onosproject.net.HostLocation;
import org.onlab.packet.IpAddress;
import org.onosproject.net.DeviceId;
import org.onosproject.net.device.DeviceService;
import org.onosproject.net.Device;
import org.onosproject.net.edge.EdgePortService;
import org.onosproject.net.packet.DefaultOutboundPacket;
import org.onosproject.net.intf.InterfaceService;
import org.onosproject.net.intf.Interface;

import java.util.*;
import java.lang.*;
import java.util.HashMap;
import java.util.Map;
import java.util.Dictionary;
import java.util.Properties;
import java.util.Set;
import java.nio.ByteBuffer;


/**
 * Skeletal ONOS application component.
 */
@Component(immediate = true)
public class AppComponent{

    private final Logger log = LoggerFactory.getLogger(getClass());

    // map to store host ip and corresponding port to packetout 
    Map<String, String> ipmac_table = new HashMap<String, String>();
    Map<String, String> port_table = new HashMap<String, String>();






    // map switch to sr id
    Map<String, Integer> srid_table = new HashMap<String, Integer>();

    // map network id to edge switch
    Map<String, String> id_sw_table = new HashMap<String, String>();

    // test sr id
    int[] test_sr = new int[10];

    // test network id
    String[] test_netid = new String[10];





    /** Some configurable property. */
    private String someProperty;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected ComponentConfigService cfgService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected NetworkConfigRegistry netCfgService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected CoreService coreService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected FlowRuleService flowRuleService;
  
    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected HostService hostService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY) 
    protected TopologyService topologyService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected FlowObjectiveService flowObjectiveService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected PacketService packetService;

    //device service
    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected DeviceService deviceService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected EdgePortService edgePortService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected InterfaceService interfaceService;



    //packet processor
    private PktProcessor pktProcessor = new PktProcessor();
  
    //app id
    private ApplicationId appId;

    //private final NameConfigListener cfgListener = new NameConfigListener();

    //private final ConfigFactory factory =
    //    new ConfigFactory<ApplicationId, NameConfig>(CONNECT_POINT_SUBJECT_FACTORY, NameConfig.class, "interfaces") {
    //        @Override
    //        public NameConfig createConfig() {
    //          return new NameConfig();
    //        }
    //};



    /**
    //object to print setting of network configuration
    private class NameConfigListener implements NetworkConfigListener {
        @Override
        public void event(NetworkConfigEvent event) {
            if ((event.type() == CONFIG_ADDED || event.type() == CONFIG_UPDATED)
                && event.configClass().equals(NameConfig.class)) {
                NameConfig config = netCfgService.getConfig(appId, NameConfig.class);
                if (config == null) {
                    log.info("config file error!!!!!");
                }
		else{
		    log.info("======= network config =======");
		    log.info("name : {}",config.name());
		    log.info("id : {}",config.id());
		    log.info("======= network config =======");
		}
            }
        }
    }
    **/


    @Activate
    protected void activate() {
        appId = coreService.registerApplication("nctu.winlab.vlanbasedsr");
        //netCfgService.addListener(cfgListener);
        //netCfgService.registerConfigFactory(factory);
        packetService.addProcessor(pktProcessor, PacketProcessor.director(2));
        requestIntercepts();



        // initialize sr id & network id for test
	for(int i=0;i<test_sr.length;i++){
	    test_sr[i] = 100 + i;
        }
	test_netid[0] = "10.0.2.0";
	test_netid[1] = "10.0.3.0";

	
	// get interface information from config file
	/**
        Set<Interface> config_interface = interfaceService.getInterfaces();
	for(Interface int_now : config_interface){
	    if(!srid_table.containsValue(int_now.vlan().toShort())){
	        srid_table.put(int_now.connectPoint().deviceId().toString(), Integer.valueOf(int_now.vlan().toShort()));
	    }
        }
	**/


	id_sw_table.put(test_netid[0], "of:0000000000000002");
	id_sw_table.put(test_netid[1], "of:0000000000000003");

	
	// explore all the switch in topology & record sr & switch id in table
	Iterable<Device> devices = deviceService.getAvailableDevices();
	Iterator<Device> device_it = devices.iterator();
	int i = 100;
	while(device_it.hasNext()){
	    Device device_now = device_it.next();
	    srid_table.put(device_now.id().toString(), i);
	    i += 1;
        }
	

	// initialize network id to switch
	//Iterable<ConnectPoint> connectPoint = edgePortService.getEdgePoints();
	//Iterator<ConnectPoint> con_it = connectPoint.iterator();
	//int j = 0;
	//while(con_it.hasNext()){
	//    ConnectPoint con_now = con_it.next();
	//    if(!id_sw_table.containsValue(con_now.deviceId().toString())){
	//	id_sw_table.put(test_netid[j], con_now.deviceId().toString());
	//	j += 1;
	//    }
        //}
	
	

	// show initialization
	log.info("==============================");
	for(Map.Entry<String, Integer> set : srid_table.entrySet())
	    log.info("{} : {}",set.getKey(),set.getValue());
	log.info("==============================");

	log.info("==============================");
	for(Map.Entry<String, String> set2 : id_sw_table.entrySet())
	    log.info("{} : {}",set2.getKey(),set2.getValue());
	log.info("==============================");




	// show edge point of topo
	//Iterable<ConnectPoint> connectPoints = edgePortService.getEdgePoints();
	//Iterator<ConnectPoint> con_it = connectPoints.iterator();
	//while(con_it.hasNext()){
	//    ConnectPoint cur_con = con_it.next();
	//    log.info("====================");
	//    log.info(" current : {}",cur_con.toString());
	//    log.info("====================");
	//}










        log.info("Started");
    }


    @Deactivate
    protected void deactivate() {
        //netCfgService.removeListener(cfgListener);
        //netCfgService.unregisterConfigFactory(factory);
        flowRuleService.removeFlowRulesById(appId);
        withdrawIntercepts();
        packetService.removeProcessor(pktProcessor);
        pktProcessor = null;
        log.info("Stopped");
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

    private Path pickForwardPathIfPossible(Set<Path> paths, PortNumber toPort){
	for(Path path : paths){
	    if(!path.src().port().equals(toPort))
		return path;
	}
	return null;
    }


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


	HostId id = HostId.hostId(ethPkt.getDestinationMAC());
	Host dst = hostService.getHost(id);

	if(id.mac().isLldp()){
	    return;    
	}

	//log.info("ethPkt.getEtherType() = {}",ethPkt.getEtherType());
	//log.info("ethPkt.toString() = {}",ethPkt.toString());


	if(ethPkt.getEtherType() == Ethernet.TYPE_IPV4){

	    IPv4 ipv4Packet = (IPv4) ethPkt.getPayload();
	    byte ipv4Protocol = ipv4Packet.getProtocol();	    
        
	    String src_ip = IPv4.fromIPv4Address(ipv4Packet.getSourceAddress());
	    String des_ip = IPv4.fromIPv4Address(ipv4Packet.getDestinationAddress());

	    // parse IP according to subnet(but what is the topo TA used to test??????)	    
	    String[] dstip_buffer = des_ip.split("\\.");
	    String dst_ip_parsed = dstip_buffer[0] + "." + dstip_buffer[1] + "." + dstip_buffer[2] + ".0";
	    String[] srcip_buffer = src_ip.split("\\.");
	    String src_ip_parsed = srcip_buffer[0] + "." + srcip_buffer[1] + "." + srcip_buffer[2] + ".0";

	    // find device id of destination switch
	    Device des_sw = null;
	    Iterable<Device> devices = deviceService.getAvailableDevices();
	    Iterator<Device> device_it = devices.iterator();
	    while(device_it.hasNext()){
	        Device device_now = device_it.next();
		log.info("------- {} == {} -------",device_now.id().toString(),id_sw_table.get(dst_ip_parsed));
	        if(device_now.id().toString().equals(id_sw_table.get(dst_ip_parsed))){
		    des_sw = device_now;
		    break;
		}
            }
	   
            
	    // compute path from current node to destination edge switch(shortest-path first)
	    
	    // if current switch is the edge switch of source, install flow rule : match subnet ip, push VID into packet & forward
	    if(ethPkt.getVlanID() == -1){
		// find vlan id of destination switch
		// IP parsed, take out the subnet and search table
		int spec_id = -1;
	        if(id_sw_table.containsKey(dst_ip_parsed)){
	            String device_id_found = id_sw_table.get(dst_ip_parsed);

	            // get device segment id from table
	            spec_id = srid_table.get(device_id_found);
	        }   
	        else
		    log.info("VLAN ID not found with dst_ip_parsed {}",dst_ip_parsed);




		// set the VID in packet
	        ethPkt = ethPkt.setVlanID((short)spec_id);
		log.info("========== packet is now on edge of source edge switch");
	        log.info("========== the packet is to {} with VID {} ==========",des_ip,ethPkt.getVlanID());


	
		// if the source edge switch is also destination edge switch, pop VID and forward the packet
		if(srid_table.get(pkt.receivedFrom().deviceId().toString()) == ethPkt.getVlanID()){

		    ethPkt = ethPkt.setVlanID((short)-1);	
		    packetOut(context, ethPkt, dst.location().port());

		}

		// else compute the route
		else{

		    // find all the possible path
		   
		    log.info("src node : {}",pkt.receivedFrom().deviceId().toString());
		    if(des_sw == null)
			log.info("dst node is null");
		    else
		        log.info("dst node : {}",des_sw.id());


		    Set<Path> paths = topologyService.getPaths(topologyService.currentTopology(),
	    				                   pkt.receivedFrom().deviceId(),
					                   des_sw.id());

		    // pick the shortest one
		    Path path_selected = pickForwardPathIfPossible(paths, pkt.receivedFrom().port());

		    List<Link> links = path_selected.links();


		    installRule(context, ethPkt, dst_ip_parsed, path_selected.src().port());
		}
	    }
            
	    // if current switch is the edge switch of destination
	    else if(srid_table.get(pkt.receivedFrom().deviceId().toString()) == ethPkt.getVlanID()){
		log.info("========== packet is now on edge of destination edge switch");
	        log.info("========== the packet is to {} with VID {} ==========",des_ip,ethPkt.getVlanID());
		// pop the vlan id
	        ethPkt = ethPkt.setVlanID((short)-1);

		
		packetOut(context, ethPkt, dst.location().port());

	    }

	    // else the current is in the middle of route
	    else{
		log.info("========== packet is now in the middle of route");
	        log.info("========== the packet is to {} with VID {} ==========",des_ip,ethPkt.getVlanID());

		// find all the possible path
		Set<Path> paths = topologyService.getPaths(topologyService.currentTopology(),
	    				                   pkt.receivedFrom().deviceId(),
					                   des_sw.id());

		// pick the shortest one
		Path path_selected = pickForwardPathIfPossible(paths, pkt.receivedFrom().port());

		installRule(context, ethPkt, "0.0.0.0", path_selected.src().port());

	    }	




        }


	
    }

    private void installRule(PacketContext context, Ethernet ethPkt, String netid, PortNumber portNumber){

	
	TrafficSelector.Builder selectorBuilder = DefaultTrafficSelector.builder();

	TrafficTreatment treatment = DefaultTrafficTreatment.builder().setOutput(portNumber).build();


	if(ethPkt.getEtherType() == Ethernet.TYPE_ARP){
	    packetOut(context, ethPkt, portNumber);
	    return;
	}

	if(!netid.equals("0.0.0.0")){
	    Ip4Prefix matchIp4DstPrefix = Ip4Prefix.valueOf(netid + "/24");
            selectorBuilder.matchIPDst(matchIp4DstPrefix);
	}
	else{
	    selectorBuilder.matchVlanId(VlanId.vlanId(ethPkt.getVlanID()));
	}


	ForwardingObjective forwardingObjective = DefaultForwardingObjective.builder()
						  .withSelector(selectorBuilder.build())
						  .withTreatment(treatment)
						  .withFlag(ForwardingObjective.Flag.VERSATILE)
						  .fromApp(appId)
						  .makePermanent()
						  .add();

	flowObjectiveService.forward(context.inPacket().receivedFrom().deviceId(), forwardingObjective);


	packetOut(context, ethPkt, portNumber);


    }



    private void packetOut(PacketContext context, Ethernet ethPkt, PortNumber portNumber){

	TrafficTreatment treatment = DefaultTrafficTreatment.builder().setOutput(portNumber).build();

	DefaultOutboundPacket out_pkt = new DefaultOutboundPacket(context.inPacket().receivedFrom().deviceId(), 
								  treatment, 
								  ByteBuffer.wrap(ethPkt.serialize()));
	packetService.emit(out_pkt);
    }


  }



}
