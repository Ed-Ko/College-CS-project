import pandas as pd
import numpy as np
import csv
import math

csvfile = open('student-mat.csv',newline = '')
input_data = csv.reader(csvfile, delimiter = ';')

math_data = []
for row in input_data:
    math_data.append(row)

csvfile.close()

#get the entropy of all feature
import math

sum_list = {}
feature_entropy_list = {}
sort_list = {}
threshold_list = []
count = 0
math_data_binary = math_data
math_data_level = math_data
# change G3 of each student to pass/fail

for i in range(1,len(math_data_binary)):
    if math_data_binary[i][len(math_data_binary[0])-1].isnumeric():
        if int(math_data_binary[i][len(math_data_binary[0])-1]) >= 10: # G3>=10, pass
            math_data_binary[i][len(math_data_binary[0])-1] = "pass"
        else:
            math_data_binary[i][len(math_data_binary[0])-1] = "fail"

#change G3 of each student to 5 level
for i in range(1,len(math_data_level)):
    if math_data_level[i][len(math_data_level[0])-1].isnumeric():
        if int(math_data_level[i][len(math_data_level[0])-1]) <= 9: # G3<=9, level : fail
            math_data_level[i][len(math_data_level[0])-1] = "fail"
        elif int(math_data_level[i][len(math_data_level[0])-1]) >= 10 and int(math_data_level[i][len(math_data_level[0])-1]) <= 11:
            math_data_level[i][len(math_data_level[0])-1] = "sufficient"
        elif int(math_data_level[i][len(math_data_level[0])-1]) >= 11 and int(math_data_level[i][len(math_data_level[0])-1]) <= 13:
            math_data_level[i][len(math_data_level[0])-1] = "satisfactory"
        elif int(math_data_level[i][len(math_data_level[0])-1]) >= 14 and int(math_data_level[i][len(math_data_level[0])-1]) <= 15:
            math_data_level[i][len(math_data_level[0])-1] = "good" 
        elif int(math_data_level[i][len(math_data_level[0])-1]) >= 16 and int(math_data_level[i][len(math_data_level[0])-1]) <= 20:
            math_data_level[i][len(math_data_level[0])-1] = "excellent"



#calculate the sum of different value of each feature, if data is numeric then extract it to a list for computing the threshold

# binary classification
# initialize the temporary lists
for i in range(len(math_data_binary[1])-1):
    if math_data_binary[1][i].isnumeric():
        sort_list[math_data_binary[0][i]] = []
    else:
        sum_list[   [0][i]] = {}


for i in range(1,len(math_data_binary)):
    count += 1
    for j in range(len(math_data_binary[i])):
        if j is len(math_data_binary[0])-1:
            if math_data_binary[i][j] == "fail": #not passed
                for k in range(len(math_data_binary[0])):
                    if math_data_binary[0][k] in sort_list:
                        sort_list[math_data_binary[0][k]][i-1][1] = 1
                    elif math_data_binary[0][k] in sum_list:
                        sum_list[math_data_binary[0][k]][math_data_binary[i][k]][1] += 1
                            
            else: # passed
                for k in range(len(math_data_binary[0])):
                    if math_data_binary[0][k] in sort_list:
                        sort_list[math_data_binary[0][k]][i-1][2] = 1
                    elif math_data_binary[0][k] in sum_list:
                        sum_list[math_data_binary[0][k]][math_data_binary[i][k]][2] += 1

        else:
            if math_data_binary[i][j].isnumeric():
                sort_list[math_data_binary[0][j]].append([int(math_data_binary[i][j]),0,0])
            else:
                if math_data_binary[i][j] in sum_list[math_data_binary[0][j]]:
                    sum_list[math_data_binary[0][j]][math_data_binary[i][j]][0] += 1
                else:
                    sum_list[math_data_binary[0][j]][math_data_binary[i][j]] = [1,0,0]

#calculate the entropy of each feature

# binary classification
for label,value in sum_list.items():
    feature_entropy_list[label] = {}
    for key,num in sum_list[label].items():
        temp_proba1 = sum_list[label][key][1] / sum_list[label][key][0]
        temp_proba2 = sum_list[label][key][2] / sum_list[label][key][0]
        sum_entropy = -1 * ((temp_proba1 * math.log(temp_proba1,2)) + (temp_proba2 * math.log(temp_proba2,2)))
        feature_entropy_list[label][key] = sum_entropy
        


# decision tree construction : using C4.5 algorithm

# decision tree : node information
            #test
class node:
    def __init__(self,child_nodes,dataset,criteria,info_gain):
        self.child_nodes = [] # use list to store the child of current node
        self.dataset = dataset
        self.criteria = None # the label used in this node
        self.info_gain = None # the information gain of this feature

def DTconstruct_binary(dataset):
    # 1. computing normalized information gain(also called infromation gain ratio) for each attribute
    # for the complexity of information, I use entropy here
    info_gain_list = []
    remainder_list = []
    temp_feature_entropy_list = [{} for i in range(0,32)] # store temparary entropy 
    temp_dict = [{} for i in range(0,32)] #used for store the number to calculate temperary probability(no G3)
    for i in range(len(dataset)):
        for j in range(len(dataset[i])):
            
            if j is 32: # G3
                if int(dataset[i][j]) < 10: # the critiria of binary classification
                    for k in range(len(temp_dict)-1): #increase all the number if G3<10 of values of each feature
                        temp_dict[k][dataset[i][k]][1] += 1
                else:
                    for k in range(len(temp_dict)-1): #increase all the number if G3>=10 of values of each feature
                        temp_dict[k][dataset[i][k]][2] += 1

            else: #calculate the appearance of different value of each feature
                if dataset[i][j] in temp_dict[j]:
                    temp_dict[j][dataset[i][j]][0] += 1
                else:
                    temp_dict[j][dataset[i][j]] = [1,0,0] #index 0 is for the number of this value
                                                          #index 1 is for the number if G3<10
                                                          #index 2 is for the number if G3>=10
    
    for i in range(len(temp_dict)):
        temp_entropy = 0
        for key,value in temp_dict[i].items():
            temp_proba1 = temp_dict[i][key][1] / temp_dict[i][key][0]
            temp_proba2 = temp_dict[i][key][2] / temp_dict[i][key][0]
            print(key," -> ",temp_proba1," == ",temp_proba2)
            #temp_feature_entropy_list[i][key] = -1 * ((temp_proba1 * math.log(temp_proba1,2)) + (temp_proba2 * math.log(temp_proba2,2)))
    print(temp_feature_entropy_list)

        
math_data_copy = math_data[1:]
DTconstruct_binary(math_data_copy)

    
