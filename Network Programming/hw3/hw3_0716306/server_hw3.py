#!/user/bin/python3
import socket
import re
import random
import threading
import sys
from datetime import date

server_UDP = socket.socket(socket.AF_INET,socket.SOCK_DGRAM)
server_TCP = socket.socket(socket.AF_INET,socket.SOCK_STREAM)
host_name = "127.0.0.1"
port = int(sys.argv[1])
server_UDP.bind((host_name,port))
server_TCP.bind((host_name,port))

server_TCP.listen(10)

client_list = [] # format : [client_name,client_email,client_password,account used or not]
thread_list = []
random_list = [[i,False] for i in range(1,100)]


# list of board to record all the board & posts in each board
board_list = []
board_list_lock = threading.Lock() # the lock use to avoid race condition when listing boards

# used to count the number of posts -> shared memory lock to prevent race condition
class count_post:
    def __init__(self):
        self.lock = threading.Lock()
        self.post_num = 0
    
    def post_num_add(self):
        self.post_num += 1

post_count = count_post()        

class Board:
    def __init__(self):
        self.moderator = ""
        self.post_list = []
        self.board_lock = threading.Lock() #when using list-posts, no other users can create or delete post

class post:
    def __init__(self, seri_num, title, author, date, content):
        self.seri_num = seri_num
        self.title = title
        self.author = author
        self.date = date
        self.content = content
        self.comment_list = []


# chat room storage
chatroom_info = [] # store the chatroom owner and the chatroom status
chatroom_lock = threading.Lock()



def master_thread_TCP():
    while True:
        (conn,addr) = server_TCP.accept()
        print("New connection\n")

        welcome_msg = "**********************************\n" + "**  Welcome to the BBS server.  **\n" + "**********************************\n"
        conn.send(welcome_msg.encode())
        t_now = threading.Thread(target = bulletin_board_TCP, args = (conn,addr))
        t_now.setDaemon(True) # set the thread to daemon
        t_now.start()
        thread_list.append(t_now)

buf_size = 4096
def bulletin_board_TCP(client_socket,address):
    while True:
        msg = client_socket.recv(buf_size)
        msg = str(msg,encoding = 'utf-8')
        print("check msg : ",msg)
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
            send_msg = ""
            if len(line) == 2:
                if received_id_num != 0:
                    logout_name = random_list[received_id_num-1][2] # get the logout account used by this user

                    find_chatroom = 0
                    chatroom_closed = 0
                    for i in range(len(chatroom_info)):
                        if chatroom_info[i][0] == logout_name:
                            find_chatroom = 1
                            if chatroom_info[i][1] == False:
                                chatroom_closed = 1
                            else:
                                chatroom_closed = 0
                            break

                    if find_chatroom == 1:
                        if chatroom_closed == 1:
                            random_list[received_id_num-1][1] = False # set this random number to available
                            random_list[received_id_num-1].pop(-1) #remove the account which occupy this random number

                            for client_now in client_list:
                                if client_now[0] == logout_name:
                                    client_now[3].remove(received_id_num) # remove the random number used by logout user
                                    if len(client_now[3]) == 1: # if no user use this account, set this value to false
                                        client_now[3][0] = False 
                                    break
                            send_msg = "Bye, " + logout_name + ".\n"
                        else:
                            send_msg = "Please do “attach” and “leave-chatroom” first.\n"
                    else:
                        send_msg = "Bye, " + logout_name + ".\n"
                else: # this user not login yet
                    send_msg = "Please login first.\n"
            else:
                send_msg = "Usage: logout.\n"

            client_socket.send(send_msg.encode())        
        

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


        # the operation below needs lock to avoid race condition
        if command == "create-board":
            board_list_lock.acquire()
            send_msg = ""
            if received_id_num != 0: # user not logged in
                moderator = str(random_list[received_id_num-1][2])
                if len(line) != 3:
                    send_msg = "Usage: create-board <name>.\n"
                else:
                    input_board_name = line[1]
                    board_exist = 0
                    for board in board_list:
                        if board[0] == input_board_name:
                            board_exist = 1
                            break
                    
                    if board_exist == 0:
                        
                        
                        board_now = Board()
                        board_list.append([line[1],board_now,moderator])
                       

                        send_msg = "Create board successfully.\n"
                    else:
                        send_msg = "Board already exists.\n"
            else:
                send_msg = "Please login first.\n"
            client_socket.send(send_msg.encode())
            board_list_lock.release()


        if command == "create-post":
            board_list_lock.acquire()
            
            send_msg = ""
            if received_id_num == 0: # user not login
                send_msg = "Please login first.\n"
            else:
                client_socket.send("start".encode())
                title_command_idx = -1
                content_command_idx = -1
                board_name = ""
                post_title = ""
                post_content = ""
                post_author = random_list[received_id_num-1][2]
                date_now = date.today()
                board_idx = -1
                board_exist = 0
                while True:
                    received_msg = client_socket.recv(buf_size)
                    if str(received_msg, encoding = 'utf-8') == "eof": # means all the data in the create-post command is transmitted
                        if board_name == "" or board_name == command or title_command_idx >= content_command_idx or post_title == "":
                            send_msg = "Usage: create-post <board-name> --title <title> --content <content>.\n"
                            break
                        if board_exist == 0:
                            send_msg = "Board does not exist.\n"
                            break

                        

                        post_count.post_num_add()
                        post_serialnum = post_count.post_num
                        #post_count.lock.release() # unlocked post_count lock
                        # the separate word found, but can't find correspond parameter in the message
                        
                        post_content = post_content.replace("<br>","\n")

                        
                        #board_list[board_idx][1].board_lock.acquire()

                        board_list[board_idx][1].post_list.append(post(post_serialnum, post_title, post_author, date_now, post_content))
                        send_msg = "Create post successfully.\n"
                        
                        #board_list[board_idx][1].board_lock.release() # release the lock to allow list-post
                        break

                    else:
                        line_now = re.split(r'\s',received_msg.decode())
                        # the msg include the separate word of title and part, and part of the content(the initial part of command)
                        try: 
                            if line_now.index("--title") and line_now.index("--content"):
                                title_command_idx = line_now.index("--title")
                                content_command_idx = line_now.index("--content")
                                board_name = line_now[title_command_idx - 1]

                                # post title may has space character
                                for i in range(title_command_idx + 1, content_command_idx):
                                    post_title += line_now[i]
                                    if i+1 != content_command_idx:
                                        post_title += " "
                                
                                # post content may has space character
                                for i in range(content_command_idx + 1, len(line_now)):
                                    post_content += line_now[i] + " "
                                    if i+1 != len(line_now)+1:
                                        post_title += " "

                                else:
                                    for i in range(len(board_list)):
                                        if board_list[i][0] == board_name:
                                            board_exist = 1
                                            board_idx = i
                                            break
                                    
                        # the initial part finished, keep receiving the content until get EOFerror
                        except ValueError:
                            post_content += str(received_msg, encoding = 'utf-8')
            client_socket.send(send_msg.encode())

            board_list_lock.release()


        if command == "list-board":  # make all the other actions of board to stop and wait for the lock to release
            board_list_lock.acquire() #make sure no other users can modify the board list
            send_msg = ""
            if len(line) == 2:
                if len(board_list) == 0:
                    send_msg = "No board exist now.\n"
                    client_socket.send(send_msg.encode())
                else:
                    #client_socket.send("start".encode())
                    
                    
                    send_msg = "Index               Name               Moderator\n"
                    for i in range(len(board_list)):
                        send_msg = send_msg + str(i+1) + str(' '*(20-len(str(i+1)))) + str(board_list[i][0]) + str(' '*(19-len(board_list[i][0]))) + board_list[i][2] + "\n"
                    
                    client_socket.send(send_msg.encode())
                    
            else:
                send_msg = "Usage: list-board.\n"
                client_socket.send(send_msg.encode())
            board_list_lock.release()


        if command == "list-post": # make all the other actions of post to stop and wait for the lock to release
            board_list_lock.acquire()
            send_msg = ""
            if len(line) == 3:
                board_name_now = line[1]
                board_exist = 0
                board_idx = -1
                for i in range(len(board_list)):
                    if board_list[i][0] == board_name_now:
                        board_exist = 1
                        board_idx = i
                        break

                if board_exist == 1:
                    #client_socket.send("start".encode())
                    #board_list[board_idx][1].board_lock.acquire()
                    send_msg = "S/N          Title              Author          Date\n"
                    for post_now in board_list[board_idx][1].post_list:
                        send_msg = send_msg + str(post_now.seri_num) + str(' '*(13-len(str(post_now.seri_num)))) + str(post_now.title) + str(' '*(19-len(str(post_now.title)))) + str(post_now.author) + str(' '*(16-len(str(post_now.author)))) + str(post_now.date) + "\n"
                    #board_list[board_idx][1].board_lock.release()
                    #print("check message of list-post: ",send_msg)
                    client_socket.send(send_msg.encode())

                else:
                    send_msg = "Board does not exist.\n"
                    client_socket.send(send_msg.encode())
            else:
                send_msg = "Usage: list-post <board-name>.\n"
                client_socket.send(send_msg.encode())
            board_list_lock.release()
            
        
        if command == "read":
            board_list_lock.acquire()
            send_msg = ""
            if len(line) == 3 and line[1].isnumeric():
                
                post_serial = int(line[1])
                post_exist = 0
                board_idx = -1
                post_idx = -1

                for i in range(len(board_list)):
                    for j in range(len(board_list[i][1].post_list)):
                        if board_list[i][1].post_list[j].seri_num == post_serial:
                            post_exist = 1
                            board_idx = i
                            post_idx = j
                            break
                    if post_exist == 1:
                        break
    
                if post_exist == 1:
                    
                    post_wanted = board_list[board_idx][1].post_list[post_idx]
                    
                    send_msg = "Author: " + post_wanted.author + "\n" + "Title: " + post_wanted.title + "\n" + "Date: " + str(post_wanted.date) + "\n"
                    send_msg += "--\n"
                    send_msg += post_wanted.content +  "\n"
                    send_msg += "--\n"
                    for i in range(len(post_wanted.comment_list)):
                        send_msg = send_msg + post_wanted.comment_list[i][0] + ": " + post_wanted.comment_list[i][1] + "\n"
                    send_msg += "\n"
                                    
                    client_socket.send(send_msg.encode())

                else:
                    send_msg = "Post does not exist.\n"
                    client_socket.send(send_msg.encode())
            else:
                send_msg = "Usage: read <post-S/N>.\n"
                client_socket.send(send_msg.encode())

            board_list_lock.release()


        if command == "delete-post":
            board_list_lock.acquire()
            send_msg = ""
            if received_id_num != 0:
                if len(line) == 3:
                    post_serial = int(line[1])
                    post_exist = 0
                    board_idx = -1
                    post_idx = -1
                    user = str(random_list[received_id_num-1][2])
                    for i in range(len(board_list)):
                        for j in range(len(board_list[i][1].post_list)):
                            if board_list[i][1].post_list[j].seri_num == post_serial:
                                post_exist = 1
                                board_idx = i
                                post_idx = j
                                break

                        if post_exist == 1:
                            break

                    if post_exist == 0:
                        send_msg = "Post does not exist.\n"
                    elif user != board_list[i][1].post_list[j].author:
                        send_msg = "Not the post owner.\n"
                    else:
                        # lock both the board_lock, so list-post and other users can't influence the post
                        
                        #board_list[board_idx][1].board_lock.acquire()
                        board_list[board_idx][1].post_list.pop(j) # delete the post by using pop()
                        #board_list[board_idx][1].board_lock.release()
                        
                        send_msg = "Delete successfully.\n"

                else:
                    send_msg = "Usage: delete-post <post-S/N>.\n"
            else:
                send_msg = "Please login first.\n"

            client_socket.send(send_msg.encode())
            board_list_lock.release()


        if command == "update-post":
            board_list_lock.acquire()
            send_msg = ""
            if received_id_num != 0:
                client_socket.send("start".encode())
                post_serial = 0
                separate_word_idx = -1
                post_author = str(random_list[received_id_num-1][2])
                update_item = ""
                update_data = ""
                board_idx = -1
                post_idx = -1

                while True:
                    received_msg = client_socket.recv(buf_size)

                    if str(received_msg, encoding = "utf-8") == "eof": # all the message is transmitted
                        if separate_word_idx == -1 or post_serial == 0 or post_serial == command:
                            send_msg = "Usage: update-post <post-S/N> --title/content <new>.\n"
                            break
                        if board_idx == -1 or post_idx == -1: # post not exist
                            send_msg = "Post does not exist.\n"
                            break
                        if post_author != board_list[board_idx][1].post_list[post_idx].author: # user is not author, update not permitted
                            send_msg = "Not the post owner.\n"
                            break
                        if post_author != board_list[board_idx][1].post_list[post_idx].author: # user is not author, update not permitted
                            send_msg = "Not the post owner.\n"
                            break
                        if update_data == "":
                            send_msg = "Usage: update-post <post-S/N> --title/content <new>.\n"
                            break
                        
                        

                        #board_list[board_idx][1].board_lock.acquire()
                        
                        
                        if update_item == "title":
                            board_list[board_idx][1].post_list[post_idx].title = update_data
                        elif update_item == "content":
                            board_list[board_idx][1].post_list[post_idx].content = update_data
                        
                        #board_list[board_idx][1].board_lock.release()
                        send_msg = "Update successfully.\n"
                        break

                    else:
                        line_now = re.split(r'\s',received_msg.decode())
                        separate_detect = 0
                        # use two try except to test if the separate word(--title or --content) exist
                        try:
                            if line_now.index("--title"): # --title exist
                                separate_word_idx = line_now.index("--title")
                                update_item = "title"
                                separate_detect = 1
                        except ValueError:
                            pass
                        
                        try:
                            if line_now.index("--content"): # --content exist
                                separate_word_idx = line_now.index("--content")
                                update_item = "content"
                                separate_detect = 2
                        except ValueError:
                            pass

                        if separate_detect != 0: # the initial part of the input
                            post_serial = line_now[1]
                            if post_serial.isnumeric(): # the serial number detected, format maybe correct
                                post_serial = int(post_serial)
                                
                                for i in range(len(board_list)):
                                    for j in range(len(board_list[i][1].post_list)):
                                        if post_serial == board_list[i][1].post_list[j].seri_num: # post exist
                                            board_idx = i
                                            post_idx = j
                                            break
                                    if board_idx != -1 and post_idx != -1:
                                        break

                                if board_idx != -1 and post_idx != -1: # post exist
                                    for i in range(separate_word_idx + 1, len(line_now)):
                                        update_data += line_now[i]
                                        if i+1 != len(line_now):
                                            update_data += " "
                

                            else: # serial number not detected, format wrong
                                send_msg = "Usage: update-post <post-S/N> --title/content <new>.\n"

                        else: # receive the remained part of title or content
                            update_data += str(received_msg, encoding = 'utf-8')
            else:
                send_msg = "Please login first.\n"

            client_socket.send(send_msg.encode())
            board_list_lock.release()


        if command == "comment":
            board_list_lock.acquire()
            send_msg = ""
            if received_id_num != 0:
                client_socket.send("start".encode())
                board_idx = -1
                post_idx = -1
                post_serial = 0
                comment_editor = str(random_list[received_id_num-1][2])
                comment = ""

                while True:
                    received_msg = client_socket.recv(buf_size)
                    
                    if str(received_msg, encoding = "utf-8") == "eof":
                        if post_serial == -1:
                            send_msg = "Usage: comment <post-S/N> <comment>\n"
                            break
                        if comment == "":
                            send_msg = "Usage: comment <post-S/N> <comment>\n"
                            break
                        if board_idx == -1 or post_idx == -1:
                            send_msg = "Post does not exist.\n"
                            break
                        comment = comment.replace("<br>", "\n")
                        
                        #board_list[board_idx][1].board_lock.acquire()
                        board_list[board_idx][1].post_list[post_idx].comment_list.append([comment_editor, comment])
                        #board_list[board_idx][1].board_lock.release()
                        
                        send_msg = "Comment successfully.\n"
                        break

                    else:
                        #print("received_msg : ",received_msg.decode())
                        line_now = re.split(r'\s',received_msg.decode())
                        try: # if can find the command in the received message, the initial part is received
                            if not line_now[1].isnumeric():
                                post_serial = -1
                            else:
                                post_serial = int(line_now[1])
                            for i in range(len(board_list)):
                                for j in range(len(board_list[i][1].post_list)):
                                    if board_list[i][1].post_list[j].seri_num == post_serial:
                                        board_idx = i
                                        post_idx = j
                                        break
                                if board_idx != -1 and post_idx != -1:
                                    break
                            for i in range(2,len(line_now)):
                                comment += line_now[i]
                                if i+1 != len(line_now):
                                    comment += " "
                            

                        except ValueError:
                            comment += str(received_msg, encoding = 'utf-8')
            else:
                send_msg = "Please login first.\n"

            client_socket.send(send_msg.encode())
            board_list_lock.release()


        # chatroom command

        # extra-command : when the chatroom owner leave his chatroom
        if command == "close-chatroom-owner":
            owner_name = str(line[1])

            # close the chatroom
            for i in range(len(chatroom_info)):
                if chatroom_info[i][0] == owner_name:
                    chatroom_info[i][1] = False
            
            # send the message about closing chatroom to user
            client_socket.send("closing success".encode())
            client_socket.recv(buf_size)

            # allocate a new random number for the user who leave room
            id_num = -1 # allocate an unused random number to this user terminal
            while True: 
                id_num = random.randint(1,99)
                if random_list[id_num-1][1] == False:
                    random_list[id_num-1][1] = True
                    random_list[id_num-1].append(owner_name)
                    break

            # append the random number to current user in the client information list 
            for i in range(len(client_list)):
                if client_list[i][0] == owner_name:
                    client_list[i][3].append(id_num)
            client_socket.send(str(id_num).encode())


        # extra command : when a chatroom owner close his chatroom, all the client is logout from chatroom
        if command == "close-chatroom-outsider":
            user_name = str(line[1])

            # send a message just for check
            client_socket.send("closing success".encode())
            client_socket.recv(buf_size)

            # allocate a new random number for the user who leave room
            id_num = -1 # allocate an unused random number to this user terminal
            while True: 
                id_num = random.randint(1,99)
                if random_list[id_num-1][1] == False:
                    random_list[id_num-1][1] = True
                    random_list[id_num-1].append(user_name)
                    break

            # append the random number to current user in the client information list 
            for i in range(len(client_list)):
                if client_list[i][0] == user_name:
                    client_list[i][3].append(id_num)
            client_socket.send(str(id_num).encode())


        # extra command : when chatroom owner detach his chatroom, allocate a new idntity to him
        if command == "leave-chatroom-owner":
            user_name = str(line[1])

            # send a message just for check
            client_socket.send("leave success".encode())
            client_socket.recv(buf_size)

            # allocate a new random number for the user who leave room
            id_num = -1 # allocate an unused random number to this user terminal
            while True: 
                id_num = random.randint(1,99)
                if random_list[id_num-1][1] == False:
                    random_list[id_num-1][1] = True
                    random_list[id_num-1].append(user_name)
                    break
            
            # append the random number to current user in the client information list 
            for i in range(len(client_list)):
                if client_list[i][0] == user_name:
                    client_list[i][3].append(id_num)

            client_socket.send(str(id_num).encode())


        if command == "create-chatroom":
            send_msg = ""
            user_now = ""
            if received_id_num != 0:
                if len(line) == 3 and line[1].isnumeric():
                    if int(line[1]) >= 0 and int(line[1]) <= 65535:

                        request_addr = (str(address[0]),int(line[1]))
                        user_now = random_list[received_id_num-1][2]
                        find_created = 0 # find if the chatroom is created
                        chatroom_lock.acquire()
                        for i in range(len(chatroom_info)):
                            # find the user already create the room
                            if chatroom_info[i][0] == user_now:
                                find_created = 1
                                break

                            # the input address is already used by other user
                            elif chatroom_info[i][2] == request_addr:
                                find_created = 2
                                break

                        if find_created == 0: # chat room doesn't created
                            # format : [user name, open or close, (creator IP, creator port)]
                            chatroom_info.append([user_now, True, request_addr])
                            send_msg = "start to create chatroom...\n"

                        elif find_created == 1: # user already create chatroom
                            send_msg = "User has already created the chatroom.\n"

                        elif find_created == 2: # the address is already used by other client
                            send_msg = "IP address and port is already used by other user.\n"

                        chatroom_lock.release()
                    else:
                        send_msg = "Port overflow, port number should be 0 ~ 65535\n"
                else:
                    send_msg = "Usage: create-chatroom <port>\n"
            else:
                send_msg = "Please login first.\n"

            client_socket.send(send_msg.encode())
            check_msg = str(client_socket.recv(buf_size),encoding='utf-8')

            if send_msg == "start to create chatroom...\n":
                # send the information of user to local user
                user_msg = ""
                for i in range(len(client_list)):
                    if client_list[i][0] == user_now:
                        user_msg = str(client_list[i][0]) + " " + str(client_list[i][1]) + " " + str(client_list[i][2])
                        break

                client_socket.send(user_msg.encode())


        if command == "list-chatroom":
            send_msg = ""
            if received_id_num != 0:
                if len(line) == 2:
                    if len(chatroom_info) != 0:
                        chatroom_lock.acquire()
                        send_msg += "Chatroom_name             Status\n"
                        for i in range(len(chatroom_info)):
                            if chatroom_info[i][1] == True:
                                send_msg = send_msg + str(chatroom_info[i][0]) + str(' '*(26-len(chatroom_info[i][0]))) + "open\n"  
                            else:
                                send_msg = send_msg + str(chatroom_info[i][0]) + str(' '*(26-len(chatroom_info[i][0]))) + "close\n"

                        chatroom_lock.release()
                    else:
                        send_msg = "No chatroom now\n"
                else:
                    send_msg = "Usage: list-chatroom\n"
            else:
                send_msg = "Please login first.\n"

            client_socket.send(send_msg.encode())


        if command == "join-chatroom":
            send_msg = ""
            chatroom_ip = ""
            chatroom_port = -1
            if received_id_num != 0:
                if len(line) == 3:
                    chatroom_name = str(line[1])
                    find_chatroom = 0
                    chatroom_status = 0 
                    
                    chatroom_lock.acquire()
                    for i in range(len(chatroom_info)):
                        if chatroom_name == chatroom_info[i][0]: # chatroom created
                            find_chatroom = 1
                            if chatroom_info[i][1] == True: # chat room open
                                chatroom_status = 1
                                chatroom_ip = chatroom_info[i][2][0]
                                chatroom_port = chatroom_info[i][2][1]
                                break

                    chatroom_lock.release()

                    if find_chatroom == 0 or chatroom_status == 0:
                        send_msg = "The chatroom does not exist or the chatroom is close.\n"
                    else:
                        send_msg = "success\n"
                else:
                    send_msg =  "Usage: join-chatroom <chatroom_name>\n"
            else:
                send_msg = "Please login first.\n"

            client_socket.send(send_msg.encode())
            client_socket.recv(buf_size)

            if send_msg == "success\n":

                # send the user information to local user
                user_now = random_list[received_id_num-1][2]
                user_msg = ""
                for i in range(len(client_list)):
                    if client_list[i][0] == user_now:
                        user_msg = str(client_list[i][0]) + " " + str(client_list[i][1]) + " " + str(client_list[i][2])
                        break

                client_socket.send((user_msg + " " + str(chatroom_ip) + " " + str(chatroom_port)).encode())


        if command == "restart-chatroom":
            send_msg = ""
            if received_id_num != 0:
                if len(line) == 2:
                    user_now = random_list[received_id_num-1][2]
                    find_created = 0
                    chatroom_idx = -1

                    chatroom_lock.acquire()

                    for i in range(len(chatroom_info)):
                        if chatroom_info[i][0] == user_now:
                            find_created = 1
                            chatroom_idx = i
                            break
                    
                    if find_created == 1:
                        if chatroom_info[chatroom_idx][1] == False: # chatroom is not running
                            chatroom_info[chatroom_idx][1] = True
                            send_msg = "start to create chatroom...\n"

                        else:
                            send_msg = "Your chatroom is still running.\n"
                    else:
                        send_msg = "Please create-chatroom first.\n"

                    chatroom_lock.release()

                else:
                    send_msg = "Usage: restart-chatroom.\n"
            else:
                send_msg = "Please login first.\n"
            
            client_socket.send(send_msg.encode())


        if command == "exit":
            if received_id_num != 0:
                logout_name = random_list[received_id_num-1][2] # get the logout account used by this user

                random_list[received_id_num-1][1] = False # set this random number to available
                random_list[received_id_num-1].pop(-1) #remove the account which occupy this random number

                for client_now in client_list:
                    if client_now[0] == logout_name:
                        client_now[3].remove(received_id_num) # remove the random number used by logout user
                        if len(client_now[3]) == 1: # if no user use this account, set this value to false
                            client_now[3][0] = False 
                        break

                client_socket.close()
                break

            else:
                client_socket.close()
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