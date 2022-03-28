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
package nctu.winlab.unicastdhcp;

import static org.onosproject.net.config.NetworkConfigEvent.Type.CONFIG_ADDED;
import static org.onosproject.net.config.NetworkConfigEvent.Type.CONFIG_UPDATED;
import static org.onosproject.net.config.basics.SubjectFactories.APP_SUBJECT_FACTORY;

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


import java.util.Dictionary;
import java.util.Properties;
import java.util.Set;

import static org.onlab.util.Tools.get;

/**
 * Skeletal ONOS application component.
 */
@Component(immediate = true)
public class AppComponent {

  //packet service
  @Reference(cardinality = ReferenceCardinality.MANDATORY)
  protected PacketService packetService;

  //packet processor
  private PktProcessor pktProcessor = new PktProcessor();
  
  //app id
  private ApplicationId appId;


  private final Logger log = LoggerFactory.getLogger(getClass());
  private final NameConfigListener cfgListener = new NameConfigListener();
  private final ConfigFactory factory =
      new ConfigFactory<ApplicationId, NameConfig>(
          APP_SUBJECT_FACTORY, NameConfig.class, "UnicastDhcpConfig") {
        @Override
        public NameConfig createConfig() {
          return new NameConfig();
        }
      };

 

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


  @Activate
  protected void activate() {
    appId = coreService.registerApplication("nctu.winlab.unicastdhcp");
    netCfgService.addListener(cfgListener);
    netCfgService.registerConfigFactory(factory);

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
    netCfgService.removeListener(cfgListener);
    netCfgService.unregisterConfigFactory(factory);
    withdrawIntercepts();
    flowRuleService.removeFlowRulesById(appId);
    packetService.removeProcessor(pktProcessor);
    pktProcessor = null;
    log.info("Stopped");
  }

  //object to print setting of network configuration
  private class NameConfigListener implements NetworkConfigListener {
    @Override
    public void event(NetworkConfigEvent event) {
      if ((event.type() == CONFIG_ADDED || event.type() == CONFIG_UPDATED)
          && event.configClass().equals(NameConfig.class)) {
        NameConfig config = netCfgService.getConfig(appId, NameConfig.class);
        if (config != null) {
          log.info("DHCP server is at {}!", config.name());
        }
      }
    }
  }

  //packet processor
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


	//get the position of switch on which DHCP server is locate
	NameConfig config = netCfgService.getConfig(appId, NameConfig.class);
	ConnectPoint connectPoint = ConnectPoint.deviceConnectPoint(config.name());


	if(ethPkt.getEtherType() == Ethernet.TYPE_IPV4){
	    IPv4 ipv4Packet = (IPv4) ethPkt.getPayload();
	    byte ipv4Protocol = ipv4Packet.getProtocol();	    
		if(ipv4Protocol == IPv4.PROTOCOL_UDP){
                    UDP udpPacket = (UDP) ipv4Packet.getPayload();

			// if this packet is sent from client
			if(udpPacket.getSourcePort() == udpPacket.DHCP_CLIENT_PORT){
			    
			    // packet is now on the edge switch of DHCP server
			    if(pkt.receivedFrom().deviceId().equals(connectPoint.deviceId())){
			        if(!context.inPacket().receivedFrom().port().equals(connectPoint.port())){
				    log.info("packet now is on edge switch of DHCP server");

				    
				    
			    	    installRule(context, connectPoint.port());
			        }
			        return;
			    }


			    // packet is not on the edge switch of destination, get a path from client switch to the DHCP server switch
			    Set<Path> paths = topologyService.getPaths(topologyService.currentTopology(),
								       pkt.receivedFrom().deviceId(),
								       connectPoint.deviceId());
			    Path path = pickForwardPathIfPossible(paths, pkt.receivedFrom().port());
			    if(path.src().port() != null){
			        log.info("path.src().port() is ok now ======== client to server");
			        installRule(context, path.src().port());		
			    }
			    else if(path.src().port() == null){
			        log.info("path.src().port() is null now ======== client to server");
			        return;
			    }

			}

			// if this packet is sent from server
			else if(udpPacket.getSourcePort() == udpPacket.DHCP_SERVER_PORT){
			    // get client id
			    HostId clientid = HostId.hostId(ethPkt.getDestinationMAC(), VlanId.vlanId(ethPkt.getVlanID()));
			    Host client = hostService.getHost(clientid);

			    // packet is now on the edge switch of DHCP client
			    if(pkt.receivedFrom().deviceId().equals(client.location().deviceId())){
			        if(!context.inPacket().receivedFrom().port().equals(client.location().port())){
				    log.info("packet now is on edge switch of DHCP client");
			    	    installRule(context, client.location().port());
			        }
			        return;
			    }

			    

			    // packet is not on the edge switch of destination, get a path from DHCP switch to the client switch
			    Set<Path> paths = topologyService.getPaths(topologyService.currentTopology(),
								       pkt.receivedFrom().deviceId(),
								       client.location().deviceId());
			    Path path = pickForwardPathIfPossible(paths, pkt.receivedFrom().port());
			    if(path.src().port() != null){
			        log.info("path.src().port() is ok now ======== server to client");
			        installRule(context, path.src().port());		
			    }
			    else if(path.src().port() == null){
			        log.info("path.src().port() is null now ======== server to client");
			        return;
			    }
			}	    
		}
	    }


	if(dst == null){
	    log.info("destination is null");
	    return;
	}

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
  	InboundPacket Pkt = context.inPacket();
	Ethernet inPkt = Pkt.parsed();
	TrafficSelector.Builder selectorBuilder = DefaultTrafficSelector.builder();

	//if packet is ARP packet, forward directly to output port
	if(inPkt.getEtherType() == Ethernet.TYPE_ARP){
	    packetOut(context, portNumber);
	    return;
	}
        
	//match MAC port
        selectorBuilder.matchInPort(context.inPacket().receivedFrom().port());

	//match IPv4 & UDP
	if(inPkt.getEtherType() == Ethernet.TYPE_IPV4){
	    IPv4 ipv4Packet = (IPv4) inPkt.getPayload();
	    byte ipv4Protocol = ipv4Packet.getProtocol();

	    //match ethernet type IPv4
	    selectorBuilder.matchEthType(Ethernet.TYPE_IPV4);

	    //match DHCP port
	    if(ipv4Protocol == IPv4.PROTOCOL_UDP){
	        UDP udpPacket = (UDP) ipv4Packet.getPayload();
		if(udpPacket.getSourcePort() == udpPacket.DHCP_CLIENT_PORT){
		    log.info(" ========= packet is sent from clent");
		    selectorBuilder.matchIPProtocol(ipv4Protocol)
			           .matchUdpSrc(TpPort.tpPort(udpPacket.DHCP_CLIENT_PORT))
			           .matchUdpDst(TpPort.tpPort(udpPacket.DHCP_SERVER_PORT));
		}
		else if(udpPacket.getSourcePort() == udpPacket.DHCP_SERVER_PORT){
		    log.info(" ========= packet is sent from server");
		    //if not match destination MAC when packet is sent from server, DHCP offer will sent to original client
		    selectorBuilder.matchEthDst(inPkt.getDestinationMAC());
	
		    selectorBuilder.matchIPProtocol(ipv4Protocol)
			           .matchUdpSrc(TpPort.tpPort(udpPacket.DHCP_SERVER_PORT))
			           .matchUdpDst(TpPort.tpPort(udpPacket.DHCP_CLIENT_PORT));
		}
	    }

	}

	
	TrafficTreatment treatment = DefaultTrafficTreatment.builder().setOutput(portNumber).build();
	ForwardingObjective forwardingObjective = DefaultForwardingObjective.builder()
						  .withSelector(selectorBuilder.build())
						  .withTreatment(treatment)
						  .withPriority(100)
						  .withFlag(ForwardingObjective.Flag.VERSATILE)
						  .fromApp(appId)
						  .makeTemporary(100)
						  .add();

	// install forwarding rules on specific device
	flowObjectiveService.forward(context.inPacket().receivedFrom().deviceId(), forwardingObjective);
	packetOut(context, portNumber);
  }

}
