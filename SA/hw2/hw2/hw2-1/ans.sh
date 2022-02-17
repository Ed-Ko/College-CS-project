sed "s/[[0-9]*]//g" | awk 'BEGIN{sudo_count=0;failed_logger="";failed_ip=""} { if($5=="sudo:"){match($0,/COMMAND=.*/);sudo_user[sudo_count]=$6;sudo_cmd[sudo_count] = substr($0,RSTART,RLENGTH);sudo_time[sudo_count] = ($1" "$2" "$3);sudo_count++} ; if($5=="sshd:" && $7=="PAM:"){ip_ip[$NF]=ip_ip[$NF]+1;failed_ip=$NF;failed_logger=$11;if($11!="illegal"){user_user[$11]=user_user[$11]+1;failed_logger=$11}};if($5=="syslogd:" && failed_logger!="illegal"){user_user[failed_logger] = user_user[failed_logger] + $9;ip_ip[failed_ip] = ip_ip[failed_ip] + $9};if($5=="syslogd:" && failed_logger=="illegal"){ip_ip[failed_ip] = ip_ip[failed_ip] + $9}} END{for(key in sudo_cmd){print "audit_sudo.txt" ";" sudo_user[key] " used sudo to do " sudo_cmd[key] " on " sudo_time[key]};for(key in ip_ip){print "audit_ip.txt" ";" key " failed to log in " ip_ip[key] " times"};for(key in user_user){print "audit_user.txt" ";" key " failed to log in " user_user[key] " times"}}' | sed "s/COMMAND=//g" | awk -F ";" '{print $2 > $1}'