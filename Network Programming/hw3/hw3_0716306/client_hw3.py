#!/user/bin/python3
import socket 
import re
import sys
import random
import threading
from datetime import datetime
import select

current_time = datetime.now()
buf_size = 4096

host_name = sys.argv[1]
port = int(sys.argv[2])
bbs_id_num = 0 # the random number received from BBS server to identify the user
chatroom_id_num = 0 # the random number received from chatroom server to identify the user


client_UDP = socket.socket(socket.AF_INET,socket.SOCK_DGRAM)  
client_TCP = socket.socket(socket.AF_INET,socket.SOCK_STREAM)

# chatroom service
t_chatroom = "" # the thread to start chatroom master thread
service_id = 0 # 0 for BBS service, 1 for chatroom service ()
chatroom_status = 0 # 0: close, 1: open (maintained by server-side)
chatroom_owner = "" # the owner of chatroom
room_create = 0 # the chatroom created by chatroom owner, control the message of system welcome

owner_close_room = 0 # used to signal the close of this chatroom
owner_leave_room = 0 # used to signal the leave of owner from his chatroom
outsider_leave_chatroom = 0 # used to signal that this client leave a chatroom it connected to 


local_chatroom_addr = ("",0) # the address of local chatroom
remote_chatroom_addr = ("",0) # the address of other chatroom

chatroom_server_socket = socket.socket(socket.AF_INET,socket.SOCK_STREAM) # socket used to receive command to the chatroom created by other user
#chatroom_server_socket.setblocking(0) # set the socket to non-blocking and use select to detect the message broadcast
#chatroom_connect_socket.setblocking(0) # set the socket to non-blocking and use select to detect the message broadcast

chatroom_server_record = [] # used to maintain the record of local chatroom(include sender, send time, message)
chatroom_random_list = [[i,False] for i in range(1,100)] # allocate a random number for user to idntify itself in chatroom server
chatroom_connection_info = [] # store the connection of every user, format : [user_name, socket]

chatroom_lock = threading.Lock() # the lock to avoid race condition of local chatroom
chatroom_thread = [] # store the thread of user who connect to local chatroom

local_user_info = [] # used to store the information local user, format : [user_name, user_email, user_password]
chatroom_user_info = [] # store the information of user in the chatroom, format : [user_name, user_email, user_password]


def chatroom_service_master():

    global chatroom_status

    while True:
        (conn,addr) = chatroom_server_socket.accept()
        user_thread = threading.Thread(target = chatroom_service_child, args=(conn,addr))
        user_thread.start()
        chatroom_thread.append(user_thread)




def chatroom_service_child(client_socket,client_addr):
    global chatroom_status
    welcome_msg = "********************************\n" + "**  Welcome to the chatroom.  **\n" + "********************************\n"
    client_socket.send(welcome_msg.encode())

    incoming_user = str(client_socket.recv(buf_size),encoding = 'utf-8')
    incoming_user = re.split(r'\s',incoming_user) # get the information of user connnected, format : [user_name, user_email, user_password]
    # allocate a random number for the user who join the chatroom

    chatroom_lock.acquire()
    chatroom_connection_info.append([incoming_user[0],client_socket]) # use user_name to recognize socket

    # allocate a random number for user
    chat_id = -1
    while True:
        chat_id = random.randint(1,99)
        if chatroom_random_list[chat_id-1][1] == False:
            chatroom_random_list[chat_id-1][1] = True
            chatroom_random_list[chat_id-1].append(incoming_user[0]) # tell the chatroom server that the user occupy the random number
            break
    
    client_socket.send(str(chat_id).encode()) # send the chatroom id to user connected
    incoming_user.append(chat_id) # put the assigned chatroom id to the user information
    chatroom_user_info.append(incoming_user) # put the information of incoming user to user list


    # send the latest 3 news to the join user
    to_join_user = ""
    if len(chatroom_server_record) < 3:
        for msg in reversed(chatroom_server_record):
            to_join_user = to_join_user + msg + "\n"
    else:
        for i in range(3):
            to_join_user = to_join_user + chatroom_server_record[2-i] + "\n"
    
    if to_join_user != "":
        client_socket.send(to_join_user.encode())
    else:
        client_socket.send(" \n".encode())



    global room_create
    # put the message of an incoming user to the latest chatroom message
    join_msg = " \n"
    if room_create == 0:
        room_create = 1
    else:
        join_msg = "sys[" + str(current_time.hour) + ":" + str(current_time.minute) + "] : " + incoming_user[0] + " join us."

    # broadcast the message to all the user
    if room_create != " \n":
        for i in range(len(chatroom_connection_info)):
            if chatroom_connection_info[i][0] != incoming_user[0]:
                chatroom_connection_info[i][1].send(join_msg.encode())

    if len(chatroom_server_record) > 50: # set the size of chatroom record to 50
        chatroom_server_record.pop() 

    chatroom_lock.release()

    while True:

        msg = ""
        in_list = []
        # keep waiting for input
        while True:
            input_list = []

            # continuing update the input list of select
            for i in range(len(chatroom_connection_info)):
                if chatroom_connection_info[i][1].fileno() != -1:
                    input_list.append(chatroom_connection_info[i][1])

            in_list, out_list, ex_list = select.select(input_list, [], [], 0.1)
            if len(in_list) != 0:
                break

        # extract input from select
        for sck in in_list:
            try:
                test_msg = str(sck.recv(buf_size),encoding='utf-8')
                msg = test_msg
            except OSError:
                if sck.fileno() == -1:
                    break
                else:
                    pass
            

        if msg != "":
            line = re.split(r'\s',msg)
            command = line[0]
            received_id_num = int(line[-1])

            if command == "leave-chatroom":
                user_now = chatroom_random_list[received_id_num-1][2] # use user_name to identify the socket of users

                if chatroom_owner == user_now: # the user who want to leave chatroom is chatroom owner
                    chatroom_lock.acquire()
                    chatroom_owner_idx = -1
                    # tell all the user to end their connection
                    for i in range(len(chatroom_connection_info)):
                        # for user who connect to this chatroom , send message to make them leave as outsider
                        if chatroom_connection_info[i][0] != chatroom_owner:
                            chatroom_connection_info[i][1].send("leave-chatroom\n outsider".encode())
                            leave_msg = str(chatroom_connection_info[i][1].recv(buf_size),encoding = 'utf-8')
                            leave_msg = re.split(r'\s',leave_msg)

                            #print(" ====== outsider leave : ",leave_msg," --------user_now : ",user_now)

                            # clear the currrnt random number
                            closed_socket_id = int(leave_msg[-1])
                            if chatroom_random_list[closed_socket_id-1][1] == True:
                                chatroom_random_list[closed_socket_id-1][1] = False
                                chatroom_random_list[closed_socket_id-1].pop()


                            #chatroom_connection_info[i][1].send("leave-chatroom\n outsider".encode())
                            #connection_closed = str(chatroom_connection_info[i][1].recv(buf_size), encoding = 'utf-8') # make sure that connection closed
                            #print("connection closed ===================== : ",connection_closed)
                            
                            
                            chatroom_connection_info[i][1].close() # close the server-side connection

                        # for chatroom owner, make him leave as owner
                        else:
                            chatroom_owner_idx = i
                

                    chatroom_random_list[received_id_num-1][1] = False
                    chatroom_random_list[received_id_num-1].pop()


                    
                    chatroom_connection_info[chatroom_owner_idx][1].send("leave-chatroom\n owner".encode())
                    #connection_closed = str(chatroom_connection_info[chatroom_owner_idx][1].recv(buf_size), encoding = 'utf-8') # make sure that connection closed
                    chatroom_connection_info[chatroom_owner_idx][1].close() # close the server-side connection
                    

                    #print("check 1 : ",chatroom_connection_info[chatroom_owner_idx])
                    #print("check 2 : ",client_socket)


                    # clear all the connection info of user
                    for i in range(len(chatroom_connection_info)):
                        chatroom_connection_info.pop()

                    # clear all the information of user(because every time a user connect,he will send his information to chatroom) and pop the chatroom user info
                    for i in range(len(chatroom_user_info)):
                        chatroom_user_info.pop()

                    chatroom_status = 0 # close the chatroom

                    chatroom_lock.release()

                    # server slave thread closed after all the connetions closed
                    break


                else: # the user who want to leave chatroom is not chatroom owner

                    chatroom_lock.acquire()
                    # give the assigned random number back to random list
                    chatroom_random_list[received_id_num-1][1] = False           
                    chatroom_random_list[received_id_num-1].pop()
                    
                    # tell the client side to end connection
                    socket_idx = -1
                    for i in range(len(chatroom_connection_info)):
                        if chatroom_connection_info[i][0] == user_now:
                            socket_idx = i
                            break

                    
                    chatroom_connection_info[socket_idx][1].send("leave-chatroom\n outsider".encode()) # use this message to tell client side to close connection
                    connection_closed = str(chatroom_connection_info[socket_idx][1].recv(buf_size),encoding='utf-8') # ensure that the client-side connection closed
                    chatroom_connection_info[socket_idx][1].close() # close the current socket
                    chatroom_connection_info.pop(socket_idx) # pop the socket which is going to be closed
                    

                    client_leave_msg = "sys[" + str(current_time.hour) + ":" + str(current_time.minute) + "] : " + user_now + " leave us."

                    for i in range(len(chatroom_connection_info)):
                        if chatroom_connection_info[i][0] != user_now:
                            #print("check speaker : ",user_now," ==== check receiver : ",chatroom_connection_info[i][0])
                            chatroom_connection_info[i][1].send(client_leave_msg.encode())

                    chatroom_lock.release()
                    # server slave thread closed after all the connetions closed
                    break


            elif command == "detach":
                user_now = chatroom_random_list[received_id_num-1][2]

                #print("detach ===== chatroom owner : ",chatroom_owner,"  ----- user_now",user_now)
                
                if user_now == chatroom_owner:

                    chatroom_lock.acquire()

                    # give the random number owned by chatroom owner back to chatroom random list
                    chatroom_random_list[received_id_num-1][1] = False           
                    chatroom_random_list[received_id_num-1].pop()

                    # pop the socket of chatroom owner
                    socket_idx = -1
                    for i in range(len(chatroom_connection_info)):
                        if chatroom_connection_info[i][0] == user_now:
                            socket_idx = i
                            break

                    # tell the client side of chatroom owner to end connection
                    chatroom_connection_info[socket_idx][1].send("check".encode()) # use this message to tell client side to close connection
                    connection_closed = str(chatroom_connection_info[socket_idx][1].recv(buf_size),encoding='utf-8') # ensure that the client-side connection closed
                    #print("detach disconnected : ",connection_closed)
                    chatroom_connection_info[socket_idx][1].close() # close the current socket
                    chatroom_connection_info.pop(socket_idx)

                    chatroom_lock.release()

                    # server slave thread closed after all the connetions closed
                    break
                
                else:
                    chatroom_lock.acquire()

                    # store the message to chatroom record
                    chat_msg = user_now + "[" + str(current_time.hour) + ":" + str(current_time.minute) + "] : "
                    for i in range(len(line[0:-1])):
                        chat_msg += str(line[i]) + " "
                    chatroom_server_record.insert(0,chat_msg)

                    # broadcast the message to all the user
                    for i in range(len(chatroom_connection_info)):
                        if chatroom_connection_info[i][0] != user_now:
                            chatroom_connection_info[i][1].send(chat_msg.encode())

                    # reply the message to user to tell him that his message is not a command(not shown)
                    client_socket.send("not command".encode())

                    chatroom_lock.release()
                    

            else: # the string is not command, just an input string, store it into chatroom message record

                user_now = chatroom_random_list[received_id_num-1][2]

                chatroom_lock.acquire()

                # store the message to chatroom record
                chat_msg = user_now + "[" + str(current_time.hour) + ":" + str(current_time.minute) + "] : "
                for i in range(len(line[0:-1])):
                    chat_msg += str(line[i]) + " "
                chatroom_server_record.insert(0,chat_msg)


                # broadcast the message to all the user
                for i in range(len(chatroom_connection_info)):
                    if chatroom_connection_info[i][0] != user_now:
                        #print("check speaker : ",user_now," ==== check receiver : ",chatroom_connection_info[i][0])
                        chatroom_connection_info[i][1].send(chat_msg.encode())

                chatroom_lock.release()     









if __name__ == '__main__':
    exit_num = 0
    while True:
        if service_id == 0:
            client_TCP = socket.socket(socket.AF_INET,socket.SOCK_STREAM)
            client_TCP.connect((host_name,port))
            welcome_msg = str(client_TCP.recv(buf_size),encoding='utf-8')
            if owner_close_room == 1 or outsider_leave_chatroom == 1 or owner_leave_room == 1:
                print("Welcome back to BBS.\n")
            else:
                print(welcome_msg)

            # if the chatroom owner close the chatroom, change the chatroom status in BBS server and allocate a random number to owner
            if owner_close_room == 1:
                client_TCP.send(("close-chatroom-owner" + " " + str(local_user_info[0] + " " + str(0))).encode())
                check_msg = str(client_TCP.recv(buf_size),encoding = 'utf-8')
                if check_msg == "closing success":
                    client_TCP.send("id".encode())
                    id_msg = str(client_TCP.recv(buf_size),encoding='utf-8')
                    bbs_id_num = int(id_msg)
                    owner_close_room = 0
                    chatroom_status = 0

                else:
                    print("Error : chatroom not closed successfully.\n")

            # the client connect to a chatroom but chatroom owner close the chatroom
            if outsider_leave_chatroom == 1 :
                client_TCP.send(("close-chatroom-outsider" + " " + str(local_user_info[0] + " " + str(0))).encode())
                check_msg = str(client_TCP.recv(buf_size),encoding = 'utf-8')
                if check_msg == "closing success":
                    client_TCP.send("id".encode())
                    id_msg = str(client_TCP.recv(buf_size),encoding='utf-8')
                    bbs_id_num = int(id_msg)
                    outsider_leave_chatroom = 0

                else:
                    print("Error : chatroom not closed successfully.\n")

            # when chatroom owner leave his chatroom, allocate a new identity
            if owner_leave_room == 1 :
                client_TCP.send(("leave-chatroom-owner" + " " + str(local_user_info[0] + " " + str(0))).encode())
                check_msg = str(client_TCP.recv(buf_size),encoding = 'utf-8')

                if check_msg == "leave success":
                    client_TCP.send("id".encode())
                    id_msg = str(client_TCP.recv(buf_size),encoding='utf-8')
                    bbs_id_num = int(id_msg)
                    owner_leave_room = 0

            
            while True:
                user_input = input("bash% ")
                input_list = re.split(r'\s',user_input)        
                input_server = user_input + " " + str(bbs_id_num)
            
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
                    exit_num = 1
                    break
                
                elif input_list[0] == "login":  #protocol is TCP
                    client_TCP.send(input_server.encode())
                    server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
                    received_list = re.split(r'\s',server_msg)
                    if len(received_list) == 3 and received_list[0] == "Welcome,":
                        print(received_list[0] + " " + received_list[1] + "\n")

                        bbs_id_num = int(received_list[2])
                    else:
                        print(server_msg)

                elif input_list[0] == "logout": #protocol is TCP
                    client_TCP.send(input_server.encode())
                    server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
                    received_list = re.split(r'\s',server_msg)
                    print(server_msg)

                    if server_msg == ("Bye, " + local_user_info[0] + ".\n"):
                        bbs_id_num = 0
                
                # board_manipulation
                # protocol is TCP
                elif input_list[0] == "create-board": 
                    client_TCP.send(input_server.encode())
                    server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
                    print(server_msg)

                elif input_list[0] == "create-post":
                    command_msg = "create-post " + str(bbs_id_num)
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
                    command_msg = "update-post " + str(bbs_id_num)
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
                    command_msg = "comment " + str(bbs_id_num)
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


                # BBS chatromm command

                elif input_list[0] == "create-chatroom":
                    client_TCP.send(input_server.encode())
                    server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
                    print(server_msg)

                    client_TCP.send("check".encode()) # just for check


                    if server_msg == "start to create chatroom...\n":
                        chatroom_server_socket.bind((host_name,int(input_list[1]))) # the chatroom created, so the user can connect in 
                        local_chatroom_addr = (host_name,int(input_list[1])) # save the information of local chatroom server
                        remote_chatroom_addr = (host_name,int(input_list[1])) # connect the user to its local room
                        chatroom_server_socket.listen(20)
                        # received the user information from BBS server
                        received_msg = str(client_TCP.recv(buf_size),encoding='utf-8')
                        creator_info = re.split(r'\s',received_msg)
                        chatroom_user_info.append(creator_info) # store user_name, user_email, user_password to the room he created
                        local_user_info = creator_info # store user_name, user_email, user_password to transmit data to remote socket
                        chatroom_owner = creator_info[0]
                        client_TCP.send(("exit " + str(bbs_id_num)).encode()) # exit BBS server
                        client_TCP.close() # close connection

                        # use a thread to start chatroom server
                        t_chatroom = threading.Thread(target = chatroom_service_master, args = ())
                        t_chatroom.start()

                        bbs_id_num = 0 # set the random number received from BBS server to 0
                        service_id = 1 # change mode to chatroom
                        chatroom_status = 1 # open chatroom
                        break # break out the loop to change to chatroom mode

                elif input_list[0] == "list-chatroom":
                    client_TCP.send(input_server.encode())
                    server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
                    print(server_msg)

                elif input_list[0] == "join-chatroom":
                    client_TCP.send(input_server.encode())
                    server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')

                    if server_msg == "success\n":
                        client_TCP.send("success".encode())
                        info_get = str(client_TCP.recv(buf_size), encoding = 'utf-8')
                        info_get = re.split(r'\s',info_get)
                        addr_get = [info_get[3],info_get[4]] # get remote chatroom address
                        local_user_info = info_get[0:3] # store the information to local user to identify itself in remote chatroom
                        remote_chatroom_addr = (str(addr_get[0]),int(addr_get[1]))
                        exit_msg = "exit " + str(bbs_id_num)
                        client_TCP.send(exit_msg.encode()) # exit BBS server
                        client_TCP.close() # close connection

                        bbs_id_num = 0 # set the random number received from BBS server to 0
                        service_id = 1 # change mode to chatroom
                        break
                    else:
                        client_TCP.send("  ".encode())
                        print(server_msg)

                elif input_list[0] == "attach":
                    if bbs_id_num != 0:
                        if local_chatroom_addr != ("",0):
                            if chatroom_status == 1: # this chatroom is open
                                remote_chatroom_addr = local_chatroom_addr # chatroom owner connect to his chatroom
                                exit_msg = "exit " + str(bbs_id_num)
                                client_TCP.send(exit_msg.encode()) # exit BBS server
                                client_TCP.close() # close connectionto BBS
                                service_id = 1 # change to chatroom mode
                                bbs_id_num = 0
                                break
                            else:
                                print("Please restart-chatroom first.\n")
                        else:
                            print("Please create-chatroom first.\n")
                    else:
                        print("Please login first.\n")

                elif input_list[0] == "restart-chatroom":
                    client_TCP.send(input_server.encode())
                    server_msg = str(client_TCP.recv(buf_size), encoding = 'utf-8')
                    print(server_msg)

                    if server_msg == "start to create chatroom...\n":
                        #chatroom_server_socket.bind(local_chatroom_addr)
                        #chatroom_server_socket.listen(20)
                        remote_chatroom_addr = local_chatroom_addr# connect the user to its local room
                        chatroom_user_info.append(local_user_info) # store user_name, user_email, user_password to the room he created
                        client_TCP.send(("exit " + str(bbs_id_num)).encode()) # exit BBS server
                        client_TCP.close() # close connection

                        # use a thread to start chatroom server
                        t_chatroom = threading.Thread(target = chatroom_service_master, args = ())
                        t_chatroom.start()

                        bbs_id_num = 0 # set the random number received from BBS server to 0
                        service_id = 1 # change mode to chatroom
                        chatroom_status = 1 # open chatroom
                        break # break out the loop to change to chatroom mode

                else:
                    print("Not a command\n")


        elif service_id == 1: # chatroom mode

            chatroom_connect_socket = socket.socket(socket.AF_INET,socket.SOCK_STREAM) # socket used to send command to the other chatroom
            chatroom_connect_socket.connect(remote_chatroom_addr) # connect to the remote chatroom            
            welcome_msg = str(chatroom_connect_socket.recv(buf_size),encoding='utf-8')
            print(welcome_msg)

            # send information of join user to get chatroom id num
            chatroom_connect_socket.send((str(local_user_info[0]) + " " + str(local_user_info[1] + " " + str(local_user_info[2]))).encode()) # send the 
            
            # receive the assigned id from chatroom connected
            received_id = str(chatroom_connect_socket.recv(buf_size), encoding = 'utf-8')
            chatroom_id_num = int(received_id) 

            # get the latest three message of chatroom
            join_msg = str(chatroom_connect_socket.recv(buf_size), encoding = 'utf-8')
            print(join_msg)
            

            while True:
                user_input = ""
                in_list = []
                # received message from chatroom server periodically
                inputs = [chatroom_connect_socket.fileno(), sys.stdin.fileno()]

                while True:
                    in_list, out_list, ex_list = select.select(inputs, [], [], 0.1)
                    if len(in_list) > 0:
                        break 

                for thing in in_list:

                    # the chatroom broadcast to all the user
                    if thing == chatroom_connect_socket.fileno():

                        server_msg = str(chatroom_connect_socket.recv(buf_size),encoding='utf-8')

                        # when user is informed that the chatroom he connect to is closed by owner
                        # chatroom owner will receive message in the processing command
                        if server_msg == "leave-chatroom\n outsider": 
                            leave_msg = "leave-chatroom " + str(chatroom_id_num)
                            chatroom_connect_socket.send(leave_msg.encode()) # just a message to let server-side close socket
                            #server_msg = str(chatroom_connect_socket.recv(buf_size),encoding='utf-8')

                            #if server_msg == "leave-chatroom\n outsider":
                            #chatroom_connect_socket.send("client-side closed".encode())
                            chatroom_connect_socket.close()
                            print("sys[" + str(current_time.hour) + ":" + str(current_time.minute) + "] : the chatroom is closed.")
                            outsider_leave_chatroom = 1
                            service_id = 0
                            chatroom_id_num = 0
                            break

                        elif server_msg != "":
                            print(server_msg)


                    # no message from other users, 
                    elif thing == sys.stdin.fileno():
                        user_input = sys.stdin.readline()
                        input_list = re.split(r'\s',user_input)        
                        input_server = user_input + " " + str(chatroom_id_num)

                        # chat room service command
                        if input_list[0] == "leave-chatroom":
                            chatroom_connect_socket.send(input_server.encode()) # send the command 
                            server_msg = str(chatroom_connect_socket.recv(buf_size),encoding='utf-8')

                            # current user who leave the chatroom is not chatroom owner
                            if server_msg == "leave-chatroom\n outsider":
                                #print("check into leave-chatroom outsider\n")
                                chatroom_connect_socket.send("client-side closed\n".encode()) # just a message to let server-side close socket
                                chatroom_connect_socket.close()
                                outsider_leave_chatroom = 1
                                service_id = 0
                                chatroom_id_num = 0


                            # current user who leave the chatroom is chatroom owner
                            elif server_msg == "leave-chatroom\n owner":
                                
                                # client send leave message to chatroom
                                # chatroom_connect_socket.send("client-side closed\n".encode()) # just a message to let server-side close socket
                                chatroom_connect_socket.close()
                                owner_close_room = 1
                                service_id = 0 # change to BBS mode
                                chatroom_id_num = 0 # give the random number of chatroom back to chatroom server

                            # message just for check
                            else:
                                print("leave-chatroom receive no correct response : ",server_msg)

                            break


                        elif input_list[0] == "detach":
                            chatroom_connect_socket.send(input_server.encode())
                            disconnect_msg = str(chatroom_connect_socket.recv(buf_size), encoding='utf-8')

                            if disconnect_msg == "check":
                                chatroom_connect_socket.send("client-side disconnected\n".encode())
                                chatroom_connect_socket.close()
                                service_id = 0 # change to BBS mode
                                chatroom_id_num = 0 # give the random number of chatroom back to chatroom server
                                owner_leave_room = 1

                                break


                        else: # not a command, send the message to the chat_server_record of the chatroom
                            chatroom_connect_socket.send(input_server.encode())

                if service_id == 0:
                    break


        if exit_num == 1: # user exit application
            break

        
         
client_TCP.close()            
client_UDP.close()
chatroom_server_socket.close()
chatroom_connect_socket.close()
