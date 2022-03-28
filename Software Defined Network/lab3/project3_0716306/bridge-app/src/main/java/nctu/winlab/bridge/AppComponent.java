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
package nctu.winlab.bridge;

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

import java.util.Dictionary;
import java.util.Properties;
import java.util.Set;

import static org.onlab.util.Tools.get;

import org.onlab.util.KryoNamespace;
import org.onosproject.core.ApplicationId;
import org.onosproject.core.CoreService;
import org.onosproject.net.packet.PacketService;
import org.onosproject.net.packet.PacketContext;
import org.onosproject.net.packet.PacketProcessor;
import org.onosproject.net.packet.PacketPriority;
import org.osgi.service.component.ComponentContext;
import org.onosproject.net.topology.TopologyService;
import org.onosproject.net.topology.TopologyListener;
import org.onosproject.net.flow.TrafficSelector;
import org.onosproject.net.flow.DefaultTrafficSelector;
import org.onosproject.net.packet.InboundPacket;
import org.onlab.packet.Ethernet;
import org.onlab.packet.MacAddress;
import org.onosproject.net.HostId;
import org.onlab.packet.VlanId;
import org.onosproject.net.Host;
import org.onosproject.net.host.HostService;
import org.onosproject.net.Path;
import org.onlab.packet.IPv4;
import org.onlab.packet.Ip4Prefix;
import org.onosproject.net.PortNumber;
import org.onosproject.net.flow.TrafficTreatment;
import org.onosproject.net.flow.DefaultTrafficTreatment;
import org.onosproject.net.flowobjective.ForwardingObjective;
import org.onosproject.net.flowobjective.DefaultForwardingObjective;
import org.onosproject.net.flowobjective.FlowObjectiveService;
import org.onosproject.net.flow.FlowRuleService;
import org.onosproject.store.service.StorageService;


/**
 * Skeletal ONOS application component.
 */
@Component(immediate = true,
           service = {SomeInterface.class},
           property = {
               "someProperty=Some Default String Value",
           })
public class AppComponent implements SomeInterface {

    private final Logger log = LoggerFactory.getLogger(getClass());

    /** Some configurable property. */
    private String someProperty;
    

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected TopologyService topologyService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected PacketService packetService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected HostService hostService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected FlowRuleService flowRuleService;
    
    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected FlowObjectiveService flowObjectiveService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected ComponentConfigService cfgService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY)
    protected CoreService coreService;

    @Reference(cardinality = ReferenceCardinality.MANDATORY) 
    protected StorageService storageService;

    //@Reference(cardinality = ReferenceCardinality.MANDATORY) 
    //protected TopologyService topologyService;

    

    private ApplicationId appId;

    private BridgeApp bridgeApp = new BridgeApp();



    @Activate
    protected void activate() {

        cfgService.registerProperties(getClass());
        appId = coreService.registerApplication("nctu.winlab.bridge-app");


        packetService.addProcessor(bridgeApp, PacketProcessor.director(2));
        //topologyService.addListener(topologyListener);
        //readComopnentConfiguration(context);
        requestIntercepts();


        log.info("Started");
    }

    private void requestIntercepts(){
        TrafficSelector.Builder selector = DefaultTrafficSelector.builder();
        selector.matchEthType(Ethernet.TYPE_IPV4);
	packetService.requestPackets(selector.build(),PacketPriority.REACTIVE, appId);


	// ipv6 packet request needed?

    }
    
    
    private void withdrawIntercepts(){
	TrafficSelector.Builder selector = DefaultTrafficSelector.builder();
        selector.matchEthType(Ethernet.TYPE_IPV4);
        packetService.cancelPackets(selector.build(), PacketPriority.REACTIVE, appId);


        // ipv6 packet request needed?
    }


    private class BridgeApp implements PacketProcessor{
        
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


		MacAddress macAddress = ethPkt.getSourceMAC();
		HostId id = HostId.hostId(ethPkt.getDestinationMAC(), VlanId.vlanId(ethPkt.getVlanID()));
		Host dst = hostService.getHost(id);
		
		if(id.mac().isLldp()){
		    return;    
		}

		if(dst == null){
			log.info("MAC {} is missed on ! Flood packet!i",dst);
			flood(context);
			return;
		}


		if(pkt.receivedFrom().deviceId().equals(dst.location().deviceId())){
			if(!context.inPacket().receivedFrom().port().equals(dst.location().port())){
				log.info("MAC {} is matched on ! Install flow rule!",ethPkt.getDestinationMAC());
				installRule(context, dst.location().port());
			}
			return;
		}

		
		Set<Path> paths = topologyService.getPaths(topologyService.currentTopology(),pkt.receivedFrom().deviceId(), dst.location().deviceId());
                if(paths.isEmpty()){
			log.info("MAC {} is missed on ! Flood packet!",ethPkt.getDestinationMAC());
			flood(context);
			return;
		}


		Path path = pickForwardPathIfPossible(paths, pkt.receivedFrom().port());		

		if(path == null){
			log.info("MAC {} is missed on ! Flood packet!",ethPkt.getDestinationMAC());
			flood(context);
			return;
		}

		installRule(context, path.src().port());
	}






    }


    private boolean isControlPacket(Ethernet eth){
	short type = eth.getEtherType();
	return type == Ethernet.TYPE_LLDP || type == Ethernet.TYPE_BSN;

    }


    private void flood(PacketContext context){
	if(topologyService.isBroadcastPoint(topologyService.currentTopology(),context.inPacket().receivedFrom())){
	    packetOut(context, PortNumber.FLOOD);
	}
	else{
	    context.block();
	}
    }

    
        
    private void packetOut(PacketContext context, PortNumber portNumber){
	context.treatmentBuilder().setOutput(portNumber);
	context.send();

    }
    

    private Path pickForwardPathIfPossible(Set<Path> paths, PortNumber notToPort){
	for(Path path : paths){
	    if(!path.src().port().equals(notToPort)){
		return path;
	    }
	
	}
	return null;
    }    



    private void installRule(PacketContext context, PortNumber portNumber){
	Ethernet inPkt = context.inPacket().parsed();


	// selector here
	TrafficSelector.Builder selectorBuilder = DefaultTrafficSelector.builder();

	if(inPkt.getEtherType() == Ethernet.TYPE_ARP){
	    packetOut(context, portNumber);
	    return;
	}
	

	selectorBuilder.matchEthDst(inPkt.getDestinationMAC()).matchEthSrc(inPkt.getSourceMAC());

	//selectorBuilder.matchInPort(context.inPacket().receivedFrom().port())
	//	       .matchEthSrc(inPkt.getSourceMAC())
        //               .matchEthDst(inPkt.getDestinationMAC());

	if(inPkt.getEtherType() == Ethernet.TYPE_IPV4){
	    IPv4 ipv4Packet = (IPv4) inPkt.getPayload();
	    byte ipv4Protocol = ipv4Packet.getProtocol();
	    //Ip4Prefix matchIp4SrcPrefix = Ip4Prefix.vauleOf(ipv4Packet.getSourceAddress(),Ip4Prefix.MAX_MASK_LENGTH);
	    //Ip4Prefix matchIp4DstPrefix = Ip4Prefix.valueOf(ipv4Packet.getDestinationAddress(),Ip4Prefix.MAX_MASK_LENGTH);

	    //selectorBuilder.matchEthType(Ethernet.TYPE_IPV4).matchIPSrc(matchIp4SrcPrefix).matchIPDst(matchIp4DstPrefix);
	    //selectorBuilder.matchEthType(Ethernet.TYPE_IPV4).matchIPProtocol(IPv4.PROTOCOL_ICMP);
	}

	TrafficTreatment treatment;

	// treatment here
	treatment = context.treatmentBuilder().setOutput(portNumber).build();

	ForwardingObjective forwardingObjective = DefaultForwardingObjective.builder()
						  .withSelector(selectorBuilder.build())
						  .withTreatment(treatment)
						  .withPriority(30)
						  .withFlag(ForwardingObjective.Flag.VERSATILE)
						  .fromApp(appId)
						  .makeTemporary(30)
						  .add();
	
	flowObjectiveService.forward(context.inPacket().receivedFrom().deviceId(),forwardingObjective);

	packetOut(context, portNumber);
    }







    

    /**
    private void readComponentConfiguration(ComponentContext context){
    	Dictionary<?,?> properties = context.getProperties();

	Boolean packetOutOnlyEnabled = 



    }
    **/





    @Deactivate
    protected void deactivate() {
        cfgService.unregisterProperties(getClass(), false);
	withdrawIntercepts();
	flowRuleService.removeFlowRulesById(appId);
	packetService.removeProcessor(bridgeApp);
	bridgeApp = null;
        log.info("Stopped");
    }

    @Modified
    public void modified(ComponentContext context) {
        Dictionary<?, ?> properties = context != null ? context.getProperties() : new Properties();
        if (context != null) {
            someProperty = get(properties, "someProperty");
        }
        log.info("Reconfigured");
    }

    @Override
    public void someMethod() {
        log.info("Invoked");
    }

}
