import dpkt
import datetime
import socket
import sys
import math

def get_formatted_mac_addr(original_mac_addr):
    return ':'.join('%02x' % dpkt.compat.compat_ord(x) for x in original_mac_addr)

def print_packets(pcap):
    
    #[TODO]: 
    #1. Use MGMT_TYPE packets to calculate AP's mac addr / connection time / handoff times, and to collect beacon SNR
    #2. Use DATA_TYPE packets to calculate total transmitted bytes / CDF of packets' SNR 
    #3. Please do not print the SNR information in your submitted code, dump it to a file instead
    #Note: As for SNR information, you only need to count downlink packets (but for all APs)
    
    all_start_time = 0
    all_stop_time = 0
    
    mac_list = {}
    duration_each_ap = []
    byte_each_ap = []
    
    old_ap = ""
    new_ap = ""
    handoff_count = 0
    sum_rate = 0
    
    connected = 0
    current_ap_mac = ""
    single_duration_start = 0
    single_duration_stop = 0

    
    test_len = 0
    
    snr_list = {}
    
    for timestamp, buf in pcap:
    
    
        if(all_start_time == 0):
            all_start_time = timestamp
    
        wlan_pkt = dpkt.radiotap.Radiotap(buf).data
        
        test_len += len(buf)
        single_duration_stop = timestamp
        
        if(wlan_pkt.type == dpkt.ieee80211.MGMT_TYPE):

            if(connected == 0):

                if(wlan_pkt.subtype == 1):
                    connected = 1
                    
                    single_duration_start = timestamp

                    current_ap_mac = get_formatted_mac_addr(wlan_pkt.mgmt.src)
                    new_ap = get_formatted_mac_addr(wlan_pkt.mgmt.src)

                    if(not(current_ap_mac in mac_list)):
                        mac_list[current_ap_mac] = [0,0]
                    
                    
                    if(new_ap != old_ap and new_ap != "" and old_ap != ""):
                        handoff_count += 1

            else:

                income_mac = get_formatted_mac_addr(wlan_pkt.mgmt.src)
                outgo_mac = get_formatted_mac_addr(wlan_pkt.mgmt.dst)
                if(income_mac == current_ap_mac or outgo_mac == current_ap_mac):
                    mac_list[current_ap_mac][1] += len(buf)
                    
                    if(wlan_pkt.subtype == 8):
                        ant_sig = math.pow(10,(dpkt.radiotap.Radiotap(buf).ant_sig.db/10))
                        ant_noise = math.pow(10,(dpkt.radiotap.Radiotap(buf).ant_noise.db/10))
                        #print("check snr : ",ant_sig," ",ant_noise)
                        sum_rate += 20 * math.log2((1+(ant_sig/ant_noise)))
                    
                        if(not((dpkt.radiotap.Radiotap(buf).ant_sig.db-dpkt.radiotap.Radiotap(buf).ant_noise.db) in snr_list)):
                            snr_list[(dpkt.radiotap.Radiotap(buf).ant_sig.db-dpkt.radiotap.Radiotap(buf).ant_noise.db)] = 0
                        else:
                            snr_list[(dpkt.radiotap.Radiotap(buf).ant_sig.db-dpkt.radiotap.Radiotap(buf).ant_noise.db)] += 1
                    
                
                if(wlan_pkt.subtype == 10):
                    #print("disassociation at :  ",timestamp," with ",new_ap)

                    # calculate time duration & assign duation and transmitted byte
                    mac_list[current_ap_mac][0] += single_duration_stop - single_duration_start
                    connected = 0
                    single_duration_start = timestamp
                    old_ap = new_ap
                    new_ap = ""
                    current_ap_mac = ""

    
        elif(wlan_pkt.type == dpkt.ieee80211.DATA_TYPE):
            if(connected == 1):
            
                income_mac = get_formatted_mac_addr(wlan_pkt.data_frame.src)
                outgo_mac = get_formatted_mac_addr(wlan_pkt.data_frame.dst)

                if(income_mac == current_ap_mac or outgo_mac == current_ap_mac):
                    #print("data msg added : ",len(buf),"   ",timestamp)
                    mac_list[current_ap_mac][1] += len(buf)
                    ant_sig = pow(10,dpkt.radiotap.Radiotap(buf).AntennaSignal(buf).db/10)
                    ant_noise = pow(10,dpkt.radiotap.Radiotap(buf).AntennaNoise(buf).db/10)
                    ch_freq =  dpkt.radiotap.Radiotap(buf).Channel(buf).freq
                    sum_rate += abs(ch_freq - 2400) * math.log((1+(ant_sig/ant_noise)),2)
    	        
    	      
        elif(wlan_pkt.type == dpkt.ieee80211.CTL_TYPE):
            if(connected == 1 and wlan_pkt.subtype == dpkt.ieee80211.C_RTS):
                income_mac = get_formatted_mac_addr(wlan_pkt.rts.src)
                outgo_mac = get_formatted_mac_addr(wlan_pkt.rts.dst)
                if(income_mac == current_ap_mac or outgo_mac == current_ap_mac):
                    #print("ctrl msg added : ",len(buf),"   ",timestamp)
                    mac_list[current_ap_mac][1] += len(buf)
                    ant_sig = pow(10,dpkt.radiotap.Radiotap(buf).AntennaSignal(buf).db/10)
                    ant_noise = pow(10,dpkt.radiotap.Radiotap(buf).AntennaNoise(buf).db/10)
                    ch_freq =  dpkt.radiotap.Radiotap(buf).Channel(buf).freq
                    sum_rate += abs(ch_freq - 2400) * math.log((1+(ant_sig/ant_noise)),2)
                    
    if(connected == 1):
        mac_list[current_ap_mac][0] += single_duration_stop - single_duration_start
        connected = 0
    
    if(all_stop_time == 0):
        all_stop_time = single_duration_stop
        
    # count total sum-rate     
    sum_rate = (0.1024 / (all_stop_time - all_start_time)) * sum_rate
    
    
    #for key in snr_list:
        #print(key," : ",snr_list[key])
    
    #print(" ============================ ")
    
    print("[Connection statistics]")
    count = 1
    for key in mac_list:
        print("-AP",count)
        print("-  MAC addr : ",key)
        print("-  Total connection duration : ",mac_list[key][0])
        print("-  Total transmitted bytes : ",mac_list[key][1],"bytes")
        count += 1
    
    #print("test",test_len)
    print("[Other statistics]")
    print("  - Number of handoff events :",handoff_count)
    print("  - Theoretical sum-rate : ",sum_rate,"mbps")
    
    
    
    


    
    
    '''
    # For each packet in the pcap process the contents
    for timestamp, buf in pcap:
        # radiotap -> ieee80211
        print(buf)
        print(" ===================================================== ")
    
        wlan_pkt = dpkt.radiotap.Radiotap(buf).data
    

        if(wlan_pkt.type == dpkt.ieee80211.MGMT_TYPE): 
            dst_mac_addr = get_formatted_mac_addr(wlan_pkt.mgmt.dst)
            src_mac_addr = get_formatted_mac_addr(wlan_pkt.mgmt.src)
            print('%8.6f WLAN-Pack-Mgmt: %s -> %s' % (timestamp, src_mac_addr, dst_mac_addr))
        
        elif(wlan_pkt.type == dpkt.ieee80211.DATA_TYPE):
            dst_mac_addr = get_formatted_mac_addr(wlan_pkt.data_frame.dst)
            src_mac_addr = get_formatted_mac_addr(wlan_pkt.data_frame.src)
            print('%8.6f WLAN-Pack-Data: %s -> %s' % (timestamp, src_mac_addr, dst_mac_addr))

            # ieee80211 -> llc
            llc_pkt = dpkt.llc.LLC(wlan_pkt.data_frame.data)
            if llc_pkt.type == dpkt.ethernet.ETH_TYPE_ARP:
                # llc -> arp
                arp_pkt = llc_pkt.data
                src_ip_addr = socket.inet_ntop(socket.AF_INET, arp_pkt.spa)
                dst_ip_addr = socket.inet_ntop(socket.AF_INET, arp_pkt.tpa)
                print('[ARP packet]: %s -> %s' % (src_ip_addr, dst_ip_addr))
            elif llc_pkt.type == dpkt.ethernet.ETH_TYPE_IP:
                # llc -> ip
                ip_pkt = llc_pkt.data
                src_ip_addr = socket.inet_ntop(socket.AF_INET, ip_pkt.src)
                dst_ip_addr = socket.inet_ntop(socket.AF_INET, ip_pkt.dst)
                src_port = ip_pkt.data.sport
                dst_port = ip_pkt.data.dport
                print('[IP packet] : %s:%s -> %s:%s' % (src_ip_addr, str(src_port), dst_ip_addr, str(dst_port)))
        
        elif(wlan_pkt.type == dpkt.ieee80211.CTL_TYPE):
            if wlan_pkt.subtype == dpkt.ieee80211.C_ACK:
                dst_mac_addr = get_formatted_mac_addr(wlan_pkt.ack.dst)
                src_mac_addr = ' '*17
            elif wlan_pkt.subtype == dpkt.ieee80211.C_CTS:
                dst_mac_addr = get_formatted_mac_addr(wlan_pkt.cts.dst)
                src_mac_addr = ' '*17
            elif wlan_pkt.subtype == dpkt.ieee80211.C_RTS:
                dst_mac_addr = get_formatted_mac_addr(wlan_pkt.rts.dst)
                src_mac_addr = get_formatted_mac_addr(wlan_pkt.rts.src)
            print('%8.6f WLAN-Pack-Ctrl: %s -> %s' % (timestamp, src_mac_addr, dst_mac_addr))
    '''
if __name__ == '__main__':
    with open(sys.argv[1], 'rb') as f:
        pcap = dpkt.pcap.Reader(f)
        print_packets(pcap)

