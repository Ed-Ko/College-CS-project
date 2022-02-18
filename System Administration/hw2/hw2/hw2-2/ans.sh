#!/bin/sh



entrance_page(){
exec 3>&1
choice=$(dialog --title "System Info Panel" --menu "Please select the command you want to use" 20 35 10 1 "POST ANNOUNCEMENT" 2 "USER LIST" 2>&1 1>&3)
result=$?
exec 3>&-
# what if cancel in entrance page?
if [ "$result" -eq 0 ] ; then # select ok

  if [ "$choice" -eq 1 ] ; then # go to post announcement
    post_announcement
  elif [ "$choice" -eq 2 ] ; then # go to user list
    user_list
  fi
elif [ "$result" -eq 1 ] ; then # select cancel
  exit_page
fi
}


exit_page(){
exec 3>&1
choice=$(dialog --ok-label "Yes" --extra-button --extra-label "NO"  --title "Exit the system" --msgbox "Are you sure to exit the system?" 20 50 2>&1 1>&3)
result=$?
exec 3>&-

if [ "$result" -eq 0 ] ; then # select yes, exit the system
  rm port_tmp.txt
  rm id_file.txt
  rm login_tmp.txt
  rm sudo_tmp.txt
  echo "exit the system"
elif [ "$result" -eq 3 ] ; then # select no, go back to system info panel
  entrance_page
fi
}



post_announcement(){
  cat /etc/passwd | sed '1,2d' | sed '/nologin/d' | awk -F ":" '{print $3 ";" $1 ";" "0"}' | sed "s/ /_/g" | sed "s/;/ /g" > user_tmp.txt
  online_user=$(who | awk '{print $1}')
  echo "$online_user" > online_user.txt
  while read line ; do
    str_in="$line"
    str_replace="$line"'[*]'
    find=$(grep -q "$line" user_tmp.txt;echo $?)
    if [ "$find" -eq 0 ] ; then
      new_list=$(cat user_tmp.txt | sed "s/$str_in/$str_replace/")
      echo "$new_list" > user_tmp.txt

    elif [ "$find" -eq 1 ] ; then
      echo "user online not found"
    fi
done < "online_user.txt"

exec 3>&1
choice=$(dialog --title "POST ANNOUNCEMENT" --extra-button --extra-label "all" --checklist "Please choose who you want to post" 20 50 10 --file user_tmp.txt 2>&1 1>&3)
result=$?
exec 3>&-
if [ "$result" -eq 3 ] ; then # select all
  rm user_tmp.txt
  rm online_user.txt
  typing_msg "1" # use 1 to indicate that send message to all users online
elif [ "$result" -eq 0 ] ; then # select ok
  rm user_tmp.txt
  rm online_user.txt
  typing_msg "0" "$choice" # use 0 to indicate that the message is send to selected user
elif [ "$result" -eq 1 ] ; then # select cancel
  rm user_tmp.txt
  rm online_user.txt
  entrance_page
fi


}
typing_msg(){
exec 3>&1
choice=$(dialog --title "Post an announcement" --inputbox "Enter your message!" 20 50 2>&1 1>&3)
result=$?
exec 3>&-
if [ "$result" -eq 0 ] ; then # select ok
  msg="$choice"
  # broadcast message
  if [ "$1" -eq 0 ] ; then    # send msg to selected user
    user_num=$(echo "$2" | awk '{print NF}')


      counter=1
      while [ "$counter" -le "$user_num" ] ; do
         # get user name by id
         user_id=$(echo "$2" | awk -v count="$counter" '{print $count}')

         # if selected user is root(uid=0), just use root

         if [ "$user_id" -eq 0 ] ; then
                 current_user='root'
         else
                 current_user=$(cat /etc/passwd | sed '1,2d' | sed '/nologin/d' | awk -F ":" '{print $3 ";" $1 ";" "0"}' | sed "s/ /_/g" | sed "s/;/ /g" | grep "$user_id" | awk '{print $2}')
         fi
         echo "$msg" | sudo write "$current_user" ##################################### sudo in this line should be removed formally
         counter=$(($counter+1))
      done





   elif [ "$1" -eq 1 ] ; then  # send msg to all online user
    echo "$msg" > msg_tmp.txt
    sudo wall "msg_tmp.txt"    ######################################################## sudo in this line should be removed formally
    rm msg_tmp.txt

   fi
   entrance_page

elif [ "$result" -eq 1 ] ; then
  entrance_page

fi

}

user_list(){
cat /etc/passwd | sed '1,2d' | sed '/nologin/d' | awk -F ":" '{print $3 ";" $1 ";" "0"}' | sed "s/ /_/g" | sed "s/;/ /g" > user_tmp.txt
online_user=$(who | awk '{print $1}')
echo "$online_user" > online_user.txt
while read line ; do
  str_in="$line"
  str_replace="$line"'[*]'
  find=$(grep -q "$line" user_tmp.txt;echo $?)
    if [ "$find" -eq 0 ] ; then
      new_list=$(cat user_tmp.txt | sed "s/$str_in/$str_replace/")
      echo "$new_list" > user_tmp.txt
    elif [ "$find" -eq 1 ] ; then
      echo "user online not found"

    fi
done < "online_user.txt"
user_list=$(cat user_tmp.txt | awk '{print $1 " " $2}')
echo "$user_list" > user_tmp.txt

exec 3>&1
choice=$(dialog --ok-label "SELECT" --cancel-label "EXIT" --menu "User Info Panel" 20 50 10 --file user_tmp.txt 2>&1 1>&3)
result=$?
exec 3>&-

if [ "$result" -eq 0 ] ; then # select ok
   rm user_tmp.txt
   rm online_user.txt
   user_action "$choice" # selected user id as argument
elif [ "$result" -eq 1 ] ; then # select exit
   rm user_tmp.txt
   rm online_user.txt
   entrance_page
fi

}
user_action(){
  # get name of current user by user id
  current_user=""
  if [ "$1" -eq 0 ] ; then
    current_user='root'
  else
    current_user=$(cat /etc/passwd | sed '1,2d' | sed '/nologin/d' | awk -F ":" '{print $3 ";" $1 ";" "0"}' | sed "s/ /_/g" | sed "s/;/ /g" | grep "$1" | awk '{print $2}')
  fi

  # to see if chosen user is lock or not
  lock_state=""
  lock_or_not=$(sudo pw show user judge | grep -q 'LOCKED' ; echo "$?")
  if [ "$lock_or_not" -eq 0 ] ; then # selected user is locked, so action is unlock
    lock_state='UNLOCK'
  elif [ "$lock_or_not" -eq 1 ] ; then # selected user is not locked, so action is lock
    lock_state='LOCK'
  fi


exec 3>&1
choice=$(dialog --cancel-label "EXIT" --menu "User $current_user" 20 50 10 1 "$lock_state IT" 2 "GROUP INFO" 3 "PORT INFO" 4 "LOGIN HISTORY" 5 "SUDO LOG" 2>&1 1>&3)
result=$?
exec 3>&-

if [ "$result" -eq 0 ] ; then
  if [ "$choice" -eq 1 ] ; then # lock it
    lock_it "$1" "$current_user" "$lock_state"    # user id & user name & lock state as argument
  elif [ "$choice" -eq 2 ] ; then # group info
    group_info "$1"                               # user id as argument
  elif [ "$choice" -eq 3 ] ; then # port info
    port_info "$1"                                # user id as argument
  elif [ "$choice" -eq 4 ] ; then # login hisrtory
    login_history "$1"                            # user id as argument
  elif [ "$choice" -eq 5 ] ; then # sudo log
    sudo_log "$1"
  fi

elif [ "$result" -eq 1 ] ; then
  user_list

fi

}
lock_it(){
exec 3>&1
choice=$(dialog --title "$3 IT" --cancel-label "EXIT" --yesno "Are you sure you want to do this?" 20 50 2>&1 1>&3)
result=$?
exec 3>&-


if [ "$result" -eq 0 ] ; then # choose yes, go to lock success
  lock_success "$1" "$2" "$3"    # user id & user name & lock state as argument
elif [ "$result" -eq 1 ] ; then # choose no, back to user action
  user_action "$1"
fi

}
lock_success(){

  # execute lock or unlock
  if [ "$3" = 'LOCK' ] ; then # lock user
    sudo pw lock "$2"
  elif [ "$3" = 'UNLOCK' ] ; then # unlock user
    sudo pw unlock "$2"
  fi

  exec 3>&1
  choice=$(dialog --title "$3 IT" --msgbox "$3 SUCCEED!" 20 50 2>&1 1>&3)
  result=$?
  exec 3>&-

  if [ "$result" -eq 0 ] ; then # go back to user action
    user_action "$1"
  fi
}

group_info(){

id "$1" > id_file.txt
group_info_now=$(cat id_file.txt | awk '{print $3}' | sed 's/groups=//' | sed 's/,/\n/' | sed 's/(/ /g' | sed 's/)//g' | awk '{print}')
echo "GROUP_ID GROUP_NAME" > id_file.txt
echo "$group_info_now" >> id_file.txt
msg_now=$(cat id_file.txt)

exec 3>&1
choice=$(dialog --ok-label "SELECT" --extra-button --extra-label "EXPORT" --title "GROUP" --msgbox "$msg_now" 20 50 2>&1 1>&3)
result=$?
exec 3>&-

if [ "$result" -eq 3 ] ; then # select export, go to group info export
  group_info_export "id_file.txt" "$1"  # export file name & user id as argument

elif [ "$result" -eq 0 ] ; then # select ok, go to user action
  user_action "$1"

fi


}
group_info_export(){
exec 3>&1
choice=$(dialog --title "Export to file" --inputbox "Enter the path" 20 50 2>&1 1>&3)
result=$?
exec 3>&-

# check if file path exist
if [ "$result" -eq 0 ] ; then # select ok
  file_path=$(echo "$choice" | sed '$s/\(.*\)\//\1 /' | awk '{print $1}')

  if [ -d "$file_path" ] ; then
    cp "$1" "$choice"
    rm "$1"
    group_info "$2"
  else

    group_path_not_exist "id_file.txt" "$2"

  fi

elif [ "$result" -eq 1 ] ; then
  group_info "$2" # user id as argument

fi
rm "$1"



}
group_path_not_exist(){
  exec 3>&1
  choice=$(dialog --title "ERROR" --msgbox "File path not exist, please make the directory" 20 50 2>&1 1>&3)
  result=$?
  exec 3>&-

  if [ "$result" -eq 0 ] ; then
    group_info_export "id_file.txt" "$2"
  fi


}

port_info(){

# get user name by user id
if [ "$1" -eq 0 ] ; then
  current_user='root'
else
  current_user=$(cat /etc/passwd | sed '1,2d' | sed '/nologin/d' | awk -F ":" '{print $3 ";" $1 ";" "0"}' | sed "s/ /_/g" | sed "s/;/ /g" | grep "$1" | awk '{print $2}')
fi
sockstat -4l | grep "$current_user" | awk '{print $3 " " $5 "_" $6}' > port_tmp.txt

# check if this user use certain port

if [ -s 'port_tmp.txt' ] ; then # tmp file is not empty, this user has vertain port
  exec 3>&1
  choice=$(dialog --title "Port INFO(PID and Port)" --menu " " 20 50 10 --file port_tmp.txt 2>&1 1>&3)
  result=$?
  exec 3>&-

  if [ "$result" -eq 0 ] ; then # select ok
    process_state "$choice" "$1" # process id & user id as argument

  elif [ "$result" -eq 1 ] ; then # select cancel
    user_action "$1"

  fi

else                            # file is empty , this user doesn't use any port
  exec 3>&1
  choice=$(dialog --title "==== ERROR ====" --msgbox "This user doesn't use any port" 20 50 2>&1 1>&3)
  result=$?
  exec 3>&-

  user_action "$1"

fi

exec 3>&1
choice=$(dialog --title "Port INFO(PID and Port)" --menu " " 20 50 10 --file port_tmp.txt 2>&1 1>&3)
result=$?
exec 3>&-

if [ "$result" -eq 0 ] ; then # select ok
  process_state "$choice" "$1" # process id & user id as argument

elif [ "$result" -eq 1 ] ; then # select cancel
  user_action "$1"
fi

}
process_state(){

# get user name by user id
if [ "$1" -eq 0 ] ; then
  current_user='root'
else
  current_user=$(cat /etc/passwd | sed '1,2d' | sed '/nologin/d' | awk -F ":" '{print $3 ";" $1 ";" "0"}' | sed "s/ /_/g" | sed "s/;/ /g" | grep "$1" | awk '{print $2}')
fi

echo "USER $current_user" > port_tmp.txt

# get process info
ps l "$1" | sed '1d' | awk '{print "PID " $2 "\n" "PPID " $3 "\n" "STAT " $10}' >> port_tmp.txt
ps u "$1" | sed '1d' | awk -v str_tmp="" '{print "$CPU " $3 "\n" "%MEM " $4 ; for(i=11;i<=NF;i++){str_tmp=str_tmp" "$i} ; print "COMMAND " str_tmp}' >> port_tmp.txt

msg_now=$(cat port_tmp.txt)

exec 3>&1
choice=$(dialog --extra-button --extra-label "EXPORT" --title "PROCESS STATE: $1" --msgbox "$msg_now" 20 50 2>&1 1>&3)
result=$?
exec 3>&-

if [ "$result" -eq 3 ] ; then # select export
  port_info_export "port_tmp.txt" "$1" "$2"     # export file name & process id & user id &  as argument

elif [ "$result" -eq 0 ] ; then # select ok
  port_info "$2"                                # user id as argument
fi


}
port_info_export(){
exec 3>&1
choice=$(dialog --title "Export to file" --inputbox "Enter the path" 20 50 2>&1 1>&3)
result=$?
exec 3>&-

# check if path exist
if [ "$result" -eq 0 ] ; then
  file_path=$(echo "$choice" | sed '$s/\(.*\)\//\1 /' | awk '{print $1}')
  if [ -d "$file_path" ] ; then
        echo "file path exist"
        cp "$1" "$choice"
        rm "$1"
        process_state "$2" "$3" # process id & user id as argument

  else
        port_path_not_exist "port_tmp.txt" "$2" "$3"
  fi

elif [ "$result" -eq 1 ] ; then
  process_state "$2" "$3"

fi


}
port_path_not_exist(){
  exec 3>&1
  choice=$(dialog --title "ERROR" --msgbox "File path not exist, please make the directory" 20 50 2>&1 1>&3)
  result=$?
  exec 3>&-

  if [ "$result" -eq 0 ] ; then
    port_info_export "port_tmp.txt" "$2" "$3"
  fi
}


login_history(){

echo "DATE IP" > login_tmp.txt
last user1 | grep '[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*' | head -n 10 | awk '{print $4 " " $5 " " $6 " " $7 " " $3 }' >> login_tmp.txt
msg_now=$(cat login_tmp.txt)

exec 3>&1
choice=$(dialog --extra-button --extra-label "EXPORT" --title "LOGIN HISTORY" --msgbox "$msg_now" 20 50 2>&1 1>&3)
result=$?
exec 3>&-

if [ "$result" -eq 3 ] ; then # select export
login_history_export "login_tmp.txt" "$1"     # export file name & user id as argument

elif [ "$result" -eq 0 ] ; then # select ok
user_action "$1"                              # user id as argument

fi


}
login_history_export(){

exec 3>&1
choice=$(dialog --title "Export to file" --inputbox "Enter the path" 20 50 2>&1 1>&3)
result=$?
exec 3>&-

if [ "$result" -eq 0 ] ; then
  file_path=$(echo "$choice" | sed '$s/\(.*\)\//\1 /' | awk '{print $1}')
  if [ -d "$file_path" ] ; then
        cp "$1" "$choice"
        rm "$1"
        login_history "$2"
  else
        login_history_path_not_exist "login_tmp.txt" "$2"
  fi
elif [ "$result" -eq 1 ] ; then
        login_histroy "$2"
fi

rm "$1"


}
login_history_path_not_exist(){
  exec 3>&1
  choice=$(dialog --title "ERROR" --msgbox "File path not exist, please make the directory" 20 50 2>&1 1>&3)
  result=$?
  exec 3>&-

  if [ "$result" -eq 0 ] ; then
    login_history_export "login_tmp.txt" "$2"
  fi
}


sudo_log(){
# get name of current user by user id
if [ "$1" -eq 0 ] ; then
  current_user='root'
else
  current_user=$(cat /etc/passwd | sed '1,2d' | sed '/nologin/d' | awk -F ":" '{print $3 ";" $1 ";" "0"}' | sed "s/ /_/g" | sed "s/;/ /g" | grep "$1" | awk '{print $2}')
fi


# get today time
  today_month=$(date +"%m %d" | awk '{print $1}')
  today_day=$(date +"%m %d" | awk '{print $2}')

# get date in auth.log and store in file
  sudo cat /var/log/auth.log | grep "$current_user" | grep sudo | sed -e "s/Jan/1/g" -e "s/Feb/2/g" -e "s/Mar/3/g" -e "s/Apr/4/g" -e "s/May/5/g" -e "s/June/6/g" -e "s/July/7/g" -e "s/Aug/8/g" -e "s/Sep/9/g" -e "s/Oct/10/g" -e "s/Nov/11/g" -e "s/Dec/12/g" | awk '{print $1 " " $2}' > date_tmp.txt ############### sudo in this line should be removed formally
# choose the log in 30 days
  spec_line_num=1
  while read line ; do
    month=$(echo "$line" | awk '{print $1}')
    day=$(echo "$line" | awk '{print $2}')

    date_interval=$((($today_month-$month)*30+$today_day-$day))
    if [ $(($date_interval)) -le 30 ] ; then
      break
    fi
    spec_line_num=$((spec_line_num + 1))
  done < "date_tmp.txt"

# get the log
  sudo cat /var/log/auth.log | grep "$current_user" | grep sudo | tail +"$spec_line_num"  | awk '{match($0,/COMMAND=.*/);print $6 " used sudo to do " substr($0,RSTART,RLENGTH) " on " $1 " " $2 " " $3}' | sed "s/COMMAND=//g" > sudo_tmp.txt
  msg_now=$(cat sudo_tmp.txt)


  exec 3>&1
  choice=$(dialog --extra-button --extra-label "EXPORT" --title "SUDO LOG" --msgbox "$msg_now" 20 50 2>&1 1>&3)
  result=$?
  exec 3>&-

  rm date_tmp.txt

if [ "$result" -eq 3 ] ; then # select export
  sudo_log_export "sudo_tmp.txt" "$1"       # export file name & user id as argument

elif [ "$result" -eq 0 ] ; then # select ok
  user_action "$1"                          # user id as argument

fi

}
sudo_log_export(){
exec 3>&1
choice=$(dialog --title "Export to file" --inputbox "Enter the path" 20 50 2>&1 1>&3)
result=$?
exec 3>&-

if [ "$result" -eq 0 ] ; then
  file_path=$(echo "$choice" | sed '$s/\(.*\)\//\1 /' | awk '{print $1}')
  if [ -d "$file_path" ] ; then
        cp "$1" "$choice"
        rm "$1"
        sudo_log "$2"
  else
        sudolog_path_not_exist "sudo_tmp.txt" "$2"
  fi
elif [ "$result" -eq 1 ] ; then
        sudo_log "$2"
fi
rm "$1"



}
sudolog_path_not_exist(){
  exec 3>&1
  choice=$(dialog --title "ERROR" --msgbox "File path not exist, please make the directory" 20 50 2>&1 1>&3)
  result=$?
  exec 3>&-

  if [ "$result" -eq 0 ] ; then
    sudo_log_export "sudo_tmp.txt" "$2"
  fi
}

entrance_page