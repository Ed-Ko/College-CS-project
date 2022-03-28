#!/user/bin/python3

import socket
import re
import random
import threading
import sys

server_UDP = socket.socket(socket.AF_INET,socket.SOCK_DGRAM)
server_TCP = socket.socket(socket.AF_INET,socket.SOCK_STREAM)
host_name = "127.0.0.1"
port = sys.argv[1]
server_UDP.bind((host_name,port))
server_TCP.bind((host_name,port))

server_TCP.listen(10)

client_list = []
thread_list = []
random_list = [[i,False] for i in range(1,100)]

def master_thread_TCP():
    while True:
        (conn,addr) = server_TCP.accept()
        print("New connection\n")
        t_now = threading.Thread(target = bulletin_board_TCP, args = (conn,addr))
        t_now.start()
        thread_list.append(t_now)

def bulletin_board_TCP(client_socket,address):
    while True:
        msg = client_socket.recv(1024)
        msg = str(msg,encoding = 'utf-8')
        line = re.split(r'\s',msg)
        command = line[0]
        received_id_num = int(line[-1])
        
        if command == "login": #TCP
            if len(line) == 4:
                client_name = line[1]
                client_password = line[2]
                check = 0
                
                if received_id_num == 0: #this user hasn't login yet
                    for info_now in client_list:
                        if info_now[0] == client_name and info_now[2] == client_password: #user_name and password correct
                            check = 1
                            
                                
                    if check == 0:
                        client_socket.send("Login failed\n".encode())
                    elif check == 1:
                        client_index = -1
                        for i in range(len(client_list)):
                            if client_list[i][0] == client_name:
                                client_index = i
                        client_list[client_index][3][0] = True # set the account login history to true
                        id_num = -1 # allocate an unused random number to this user terminal
                        while True: 
                            id_num = random.randint(1,99)
                            if random_list[id_num-1][1] == False:
                                random_list[id_num-1][1] = True
                                random_list[id_num-1].append(client_name)
                                break
                        client_list[client_index][3].append(id_num) # update the user who use this account currently
                        client_socket.send(("Welcome, " + client_name + ".\n" + str(id_num)).encode()) 
                
                else: # user already login, so cannot login to any other account
                    client_socket.send("Please logout first.\n".encode())
                    
            
            else:
                client_socket.send("Usage: login <username> <password>.\n".encode())
                
        if command == "logout": #TCP
            if len(line) == 2:
                if received_id_num == 0: # this user not login yet
                    client_socket.send("Please login first.\n".encode()) 
                else:
                    logout_name = random_list[received_id_num-1][2] # get the logout account used by this user
                    random_list[received_id_num-1][1] = False # set this random number to available
                    random_list[received_id_num-1].pop(-1) #remove the account which occupy this random number
                    for client_now in client_list:
                        if client_now[0] == logout_name:
                            client_now[3].remove(received_id_num) # remove the random number used by logout user
                            if len(client_now[3]) == 1: # if no user use this account, set this value to false
                                client_now[3][0] = False 
                            break
                    client_socket.send(("Bye, " + logout_name + ".\n").encode())
            else:
                client_socket.send("Usage: logout.\n".encode())
                
        if command == "list-user":
            if len(line) == 2:
                if len(client_list) == 0:
                    client_socket.send("No user now.\n".encode())
                else:
                    send_msg = "Name               email\n"
                    for client in client_list:
                        send_msg = send_msg + (str(client[0]) + str((19-len(client[0]))*' ') + str(client[1]) + "\n")
                    client_socket.send(send_msg.encode())
            else:
                client_socket.send("Usage: list-user.\n".encode())
                
        if command == "exit":
            break

def bulletin_board_UDP():
    while True:
        data,addr = server_UDP.recvfrom(1024)
        msg = str(data.decode())
        line = re.split(r'\s',msg)
        command = line[0]
        
        if command == "register": 
            if len(line) == 4:
                client_name = line[1]
                client_email = line[2]
                client_password = line[3]
                name_unused = 1
                for info_now in client_list:
                    if info_now[0] == client_name:
                        name_unused = 0
                        break
                    
                if name_unused == 1:
                    client_list.append([client_name,client_email,client_password,[False]])
                    server_UDP.sendto("Register successfully.\n".encode(),addr)
                else:
                    server_UDP.sendto("Username is already used.\n".encode(),addr)
            else:
                server_UDP.sendto("Usage: register <username> <email> <password>\n".encode(),addr)
         
        
        if command == "whoami":
            if len(line) == 2:
                received_id_num = int(line[-1])
                if received_id_num == 0:
                    server_UDP.sendto("Please login first.\n".encode(),addr)
                else:
                    user = str(random_list[received_id_num-1][2] + "\n")
                    server_UDP.sendto(user.encode(),addr)
            else:
                server_UDP.sendto("Usage: whoami \n".encode(),addr)



if __name__ == '__main__':
    t_TCP = threading.Thread(target = master_thread_TCP)
    t_UDP = threading.Thread(target = bulletin_board_UDP)
    t_TCP.start()
    t_UDP.start()
    t_UDP.join()
    server_UDP.close()
    server_TCP.close()