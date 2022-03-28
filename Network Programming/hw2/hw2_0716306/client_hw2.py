#!/user/bin/python3
import socket 
import re
import sys


print("********************************\n"  + 
      "** Welcome to the BBS server. **\n"  +
      "********************************\n")


host_name = sys.argv[1]
port = int(sys.argv[2])
id_num = 0

client_UDP = socket.socket(socket.AF_INET,socket.SOCK_DGRAM)  
client_TCP = socket.socket(socket.AF_INET,socket.SOCK_STREAM)
client_TCP.connect((host_name,port))


if __name__ == '__main__':
    buf_size = 4096
    while True:
        user_input = input("bash% ")
        input_list = re.split(r'\s',user_input)        
        input_server = user_input + " " + str(id_num)
        
        if input_list[0] == "register" :   #protocol is UDP
            client_UDP.sendto(user_input.encode(),(host_name,port))
            (recv_data,addr) = client_UDP.recvfrom(buf_size)
            server_msg = str(recv_data, encoding = 'utf-8')
            print(server_msg)
        
        elif input_list[0] == "whoami": #protocol is UDP
            client_UDP.sendto(input_server.encode(),(host_name,port))
            (recv_data,addr) = client_UDP.recvfrom(buf_size)
            server_msg = str(recv_data, encoding = 'utf-8')
            print(server_msg)
            
        elif input_list[0] == "list-user":   #protocol is TCP
            client_TCP.send(input_server.encode()) # send 
            server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
            print(server_msg)
            
        
        elif input_list[0] == "exit":   #protocol is TCP
            client_TCP.send(input_server.encode())
            break
        
        elif input_list[0] == "login":  #protocol is TCP
            client_TCP.send(input_server.encode())
            server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
            received_list = re.split(r'\s',server_msg)
            if len(received_list) == 3 and received_list[0] == "Welcome,":
                print(received_list[0] + " " + received_list[1] + "\n")
                id_num = int(received_list[2])
            else:
                print(server_msg)

        elif input_list[0] == "logout": #protocol is TCP
            client_TCP.send(input_server.encode())
            server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
            received_list = re.split(r'\s',server_msg)
            print(server_msg)
            id_num = 0
        



        elif input_list[0] == "create-board": #protocol is TCP
            client_TCP.send(input_server.encode())
            server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
            print(server_msg)


        elif input_list[0] == "create-post":
            command_msg = "create-post " + str(id_num)
            client_TCP.send(command_msg.encode()) # send the command first
            server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
            
            if server_msg == "start":
                for i in range(0,len(user_input),buf_size):
                    out_data = user_input[i:i+buf_size] #send all the remained data
                    client_TCP.send(out_data.encode())
                    if i+buf_size >= len(user_input):
                        client_TCP.send("eof".encode()) #inform the server all the data is transmitted
                
                server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
                print(server_msg)
            else:
                print(server_msg)


        elif input_list[0] == "list-board":
            client_TCP.send(input_server.encode())
            received_msg = ""
            server_msg = ""
            server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
            print(server_msg)
            

        elif input_list[0] == "list-post":
            client_TCP.send(input_server.encode())
            received_msg = ""
            server_msg = ""
            server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
            '''
            while True: 
                received_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
                if received_msg == "eof":
                    break
                server_msg += received_msg
            '''
            print(server_msg)


        elif input_list[0] == "read":
            client_TCP.send(input_server.encode())
            server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
            print(server_msg)


        elif input_list[0] == "delete-post":
            client_TCP.send(input_server.encode())
            server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
            print(server_msg)


        elif input_list[0] == "update-post":
            command_msg = "update-post " + str(id_num)
            client_TCP.send(command_msg.encode())
            server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
            if server_msg == "start":
                for i in range(0,len(user_input),buf_size):
                    client_TCP.send(user_input[i:i+buf_size].encode())
                    if i+buf_size >= len(user_input):
                        client_TCP.send("eof".encode())
                server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
                print(server_msg)
            else:
                print(server_msg)

        elif input_list[0] == "comment":
            command_msg = "comment " + str(id_num)
            client_TCP.send(command_msg.encode())
            server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
            if server_msg == "start":
                for i in range(0,len(user_input),buf_size):
                    client_TCP.send(user_input[i:i+buf_size].encode())
                    if i+buf_size >= len(user_input):
                        client_TCP.send("eof".encode())
                server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
                print(server_msg)
            else:
                print(server_msg)

        else:
            print("Not a command\n")

        
         
client_TCP.close()            
client_UDP.close()