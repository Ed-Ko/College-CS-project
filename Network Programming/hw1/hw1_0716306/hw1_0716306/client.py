#!/user/bin/python3

import socket 
import re
import sys


print("********************************\n"  + 
      "** Welcome to the BBS server. **\n"  +
      "********************************\n")


host_name = sys.argv[1]
port = sys.argv[2]
id_num = 0

client_UDP = socket.socket(socket.AF_INET,socket.SOCK_DGRAM)  
client_TCP = socket.socket(socket.AF_INET,socket.SOCK_STREAM)
client_TCP.connect((host_name,port))

if __name__ == '__main__':
    while True:
        user_input = input("bash% ")
        input_list = re.split(r'\s',user_input)        
        input_server = user_input + " " + str(id_num)
        
        if input_list[0] == "register" :   #protocol is UDP
            client_UDP.sendto(user_input.encode(),(host_name,port))
            (recv_data,addr) = client_UDP.recvfrom(2048)
            server_msg = str(recv_data, encoding = 'utf-8')
            print(server_msg)
        
        elif input_list[0] == "whoami":
            client_UDP.sendto(input_server.encode(),(host_name,port))
            (recv_data,addr) = client_UDP.recvfrom(2048)
            server_msg = str(recv_data, encoding = 'utf-8')
            print(server_msg)
            
        elif input_list[0] == "list-user":   #protocol is TCP
            client_TCP.send(input_server.encode()) # send 
            server_msg = str(client_TCP.recv(2048), encoding = 'utf-8')
            print(server_msg)
            
        
        elif input_list[0] == "exit":
            client_TCP.send(input_server.encode())
            break
        
        elif input_list[0] == "login":
            client_TCP.send(input_server.encode())
            server_msg = str(client_TCP.recv(2048), encoding = 'utf-8')
            received_list = re.split(r'\s',server_msg)
            if len(received_list) == 3 and received_list[0] == "Welcome,":
                print(received_list[0] + " " + received_list[1] + "\n")
                id_num = int(received_list[2])
            else:
                print(server_msg)
        elif input_list[0] == "logout":
            client_TCP.send(input_server.encode())
            server_msg = str(client_TCP.recv(2048), encoding = 'utf-8')
            received_list = re.split(r'\s',server_msg)
            print(server_msg)
            id_num = 0
        else:
            print("Not a command\n")
         
client_TCP.close()            
client_UDP.close()