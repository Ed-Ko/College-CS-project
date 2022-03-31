#include <iostream>
#include <cstdio>
#include <cstdlib>
#include <pcap.h>
#include <cstring>
#include <netinet/ip.h>
#include <netinet/ether.h>
#include <vector>
#include <utility>

using namespace std;
int packet_num = 0;
int rule_change = 0;
int gretap_num = 0;
string srcip_to_ch;
string dstip_to_ch;
string brgr_ip = "140.113.0.1";

vector <pair <string,string>> connection_list;


void tunnel_creation(int src_ip,int dst_ip){
    int src[7];
    int dst[7];

    //change source ip from int to string
    src[0] = ((src_ip & 0xFF));
    src[1] = ((src_ip >> 8) & 0xFF);
    src[2] = ((src_ip >> 16) & 0xFF);
    src[3] = ((src_ip >> 24) & 0xFF);

    //change dsestination ip from int to string
    dst[0] = ((dst_ip & 0xFF));
    dst[1] = ((dst_ip >> 8) & 0xFF);
    dst[2] = ((dst_ip >> 16) & 0xFF);
    dst[3] = ((dst_ip >> 24) & 0xFF);

    srcip_to_ch = to_string(src[0]) + "." + to_string(src[1]) + "." + to_string(src[2]) + "." + to_string(src[3]);
    dstip_to_ch = to_string(dst[0]) + "." + to_string(dst[1]) + "." + to_string(dst[2]) + "." + to_string(dst[3]);

    cout << "creating tunnel with source ip : " << srcip_to_ch << " and destination ip : " << dstip_to_ch << endl;
    string add_gre;
    if(srcip_to_ch == brgr_ip)
        add_gre = "ip link add GRETAP" + to_string(gretap_num) + " type gretap remote " + dstip_to_ch + " local " + srcip_to_ch;
    else if(dstip_to_ch == brgr_ip)
        add_gre = "ip link add GRETAP" + to_string(gretap_num) + " type gretap remote " + srcip_to_ch + " local " + dstip_to_ch;
    string set_gre_up = "ip link set GRETAP" + to_string(gretap_num) + " up";
    string brctl_addif = "brctl addif br0 GRETAP" + to_string(gretap_num);

    
    
    

    cout << "command to cmd : " << add_gre << endl;
    system(add_gre.c_str());

    cout << "command to cmd : " << set_gre_up << endl;
    system(set_gre_up.c_str());

    cout << "command to cmd : " << brctl_addif << endl;
    system(brctl_addif.c_str());

    rule_change = 1;
    gretap_num++;

}

static void callback(u_char *ch,const struct pcap_pkthdr *header,const u_char *content){
    
    cout << "Packet number [" << packet_num << "]" << endl;
    packet_num++;

    //print content of captured packet
    cout << "packet content : ";
    for(long i = 0 ; i < header->caplen ; i++)
        printf("%02x ",content[i]);
    cout << endl;
    
    //outer ethernet header
    struct ethhdr *eth_hdr_outer = (struct ethhdr*)(content);

    //outer ip header
    struct iphdr *ip_hdr_outer = (struct iphdr*)(content + sizeof(struct ethhdr));

    //gre header
    u_char *gre_proto = (u_char *)(content + sizeof(struct ethhdr) + sizeof(struct iphdr) + 2);


    //inner ethernet header
    struct ethhdr *eth_hdr_inner = (struct ethhdr *)(content + sizeof(struct ethhdr) + sizeof(struct iphdr) + 4);

    //inner ip header
    struct iphdr *ip_hdr_inner = (struct iphdr *)(content + sizeof(struct ethhdr) + sizeof(struct iphdr) + 4 + sizeof(struct ethhdr));
    unsigned char source_addr[4];
    unsigned char dst_addr[4];

    

    cout << "Outer Source MAC: ";
    for(int i = 0 ; i < 6 ; i++){
        printf("%02x",eth_hdr_outer->h_source[i]);
        if(i != 5)
            cout << ":";
    }
    cout << endl;
    
    cout << "Outer Destination MAC: ";
    for(int i = 0 ; i < 6 ; i++){
        printf("%02x",eth_hdr_outer->h_dest[i]);
        if(i != 5)
            cout << ":";
    }
    cout << endl;
    
    cout << "Ethernet type: " << eth_hdr_inner->h_proto << endl;
    
    cout << "Src IP ";
    uint32_t buf_src = ip_hdr_outer->saddr;
    cout << (buf_src & 0xFF) << ".";
    cout << ((buf_src >> 8) & 0xFF) << ".";
    cout << ((buf_src >> 16) & 0xFF) << ".";
    cout << ((buf_src >> 24) & 0xFF);
    cout << endl;

    cout << "Dst IP ";
    uint32_t buf_dst = ip_hdr_outer->daddr;
    cout << (buf_dst & 0xFF) << ".";
    cout << ((buf_dst >> 8) & 0xFF) << ".";
    cout << ((buf_dst >> 16) & 0xFF) << ".";
    cout << ((buf_dst >> 24) & 0xFF);
    cout << endl;


    cout << "Inner Source MAC: ";
    for(int i = 0 ; i < 6 ; i++){
        printf("%02x",eth_hdr_inner->h_source[i]);
        if(i != 5)
            cout << ":";
    }
    cout << endl;
    
    cout << "Inner Destination MAC: ";
    for(int i = 0 ; i < 6 ; i++){
        printf("%02x",eth_hdr_inner->h_dest[i]);
        if(i != 5)
            cout << ":";
    }
    cout << endl;




    printf("Next Layer protocol : %02x%02x\n",*gre_proto,*(gre_proto+1));
    cout << "\n\n";

    cout << "check if tunnel for the two IPs already exists\n";

    uint32_t check_src_buf = ip_hdr_outer->saddr;
    uint32_t check_dst_buf = ip_hdr_outer->daddr;
    int check_src[4];
    int check_dst[4];
    check_src[0] = ((check_src_buf & 0xFF));
    check_src[1] = ((check_src_buf >> 8) & 0xFF);
    check_src[2] = ((check_src_buf >> 16) & 0xFF);
    check_src[3] = ((check_src_buf >> 24) & 0xFF);
    check_dst[0] = ((check_dst_buf & 0xFF));
    check_dst[1] = ((check_dst_buf >> 8) & 0xFF);
    check_dst[2] = ((check_dst_buf >> 16) & 0xFF);
    check_dst[3] = ((check_dst_buf >> 24) & 0xFF);

    string check_src_str = to_string(check_src[0]) + "." + to_string(check_src[1]) + "." + to_string(check_src[2]) + "." + to_string(check_src[3]);
    string check_dst_str = to_string(check_dst[0]) + "." + to_string(check_dst[1]) + "." + to_string(check_dst[2]) + "." + to_string(check_dst[3]); 



    int tunnel_exist = 0;
    for(int i = 0  ; i < connection_list.size() ; i++){
        cout << "check src_str : " << check_src_str << endl;
        cout << "check dst_str : " << check_dst_str << endl;
        cout << "check coonection now first : " << connection_list[i].first << endl;
        cout << "check connection now second : " << connection_list[i].second << endl;
        if(check_src_str == connection_list[i].first && check_dst_str == connection_list[i].second){
            tunnel_exist = 1;
            break;
        }
        if(check_src_str == connection_list[i].second && check_dst_str == connection_list[i].first){
            tunnel_exist = 1;
            break;
        }
    }

    if(tunnel_exist == 0){
        cout << "tunnel not created yet, create tunnel for new host" << endl;
        tunnel_creation(ip_hdr_outer->saddr,ip_hdr_outer->daddr); // function to create tunnel
        cout << "tunnel for the two host created, break loop to add rule\n";
        pcap_breakloop((pcap_t *)ch);
    }
    else{
        cout << "tunnel for the two host already exist\n";
    }

    
    cout << "Tunnel finish\n" << endl;
}



void packet_filter(){
    cout << "try to get interface\n";
    char *dev_name,errbuf[256];
    pcap_if_t *device_list = NULL;
    
    if(pcap_findalldevs(&device_list,errbuf) == -1){
        cout << "could not find device : " << errbuf << endl;
        exit(1);
    }

    //use loop to print all the device and select the first deviece in list
    int count_if = 0;
    for(pcap_if_t *device_now = device_list ; device_now ; device_now = device_now->next){
        cout << count_if << " Name: " << device_now->name << endl;
        count_if++;
    }

    int selected_num;
    cout << "Insert a number to select interface\n";
    cin >> selected_num;


    getchar(); // consume 


    int select_if = 0;
    for(pcap_if_t *device_now = device_list ; device_now ; device_now = device_now->next){
        if(select_if == selected_num){
            dev_name = device_now->name;
            break;
        }
        select_if++;
    }

    cout << "Start listening at " << string(dev_name) << endl;
    char ebuf[256];
    pcap_t *handle = pcap_open_live(dev_name,BUFSIZ,1,1000,ebuf);
    if(handle == NULL){
        cout << "could not open device : " << ebuf << endl;
        exit(1);
    }


    
    string rule;
    cout << "Insert default BPF filter expression: ";
    getline(cin,rule); // get the whole line
    cout << "filter: " << rule << endl;

    while(1){

        if(rule_change == 1){
            cout << "new tunnel created, ready to change BPF rule\n";
            if(srcip_to_ch == brgr_ip)
                rule = rule + " and not host " + dstip_to_ch;
            else if(dstip_to_ch == brgr_ip)
                rule = rule + " and not host " + srcip_to_ch;
            
            cout << "rule changed to : " << rule << endl;
            rule_change = 0;
        }

        cout << "compile the filter\n";
        struct bpf_program bppro;
        char *filter_rule = &rule[0];
        int netmask = 8;
        if(pcap_compile(handle,&bppro,filter_rule,0,netmask) < 0){
            cout << "pcap compiling failed\n";
            exit(1);
        }


        cout << "installing filter\n";
        if(pcap_setfilter(handle,&bppro) < 0){
            cout << "installing filter error\n";
            exit(1);
        }
        

        cout << "capture packet\n";
        long long old_count = 1;
        if(pcap_dispatch(handle,-1,callback,(u_char *)handle) < 0){
            cout << "capture packet error\n";
        }
    }

    

    cout << "GRE packet filtering successfully\n" << endl;
    cout << "close handle" << endl;
    pcap_close(handle);
    pcap_freealldevs(device_list);
}



int main(){

    packet_filter();
    

    return 0;
}