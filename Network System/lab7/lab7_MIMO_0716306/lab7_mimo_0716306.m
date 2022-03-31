%% BPSK transmission over AWGN channel
close all;clear all;clc;           
dist=100:100:400;       % distance in meters
PtdBm=10;               % transmit power in dBm
PndBm=-85;              % noise power in dBm
Pt=10^(PtdBm/10)/1000;  % transmit power in watt
Pn=10^(PndBm/10)/1000;  % noise power in watt
Bit_Length=1e3;         % number of bits transmitted
MODORDER = [1,2,4];     % modulation orders

%% Friss Path Loss Model
Gt=1;
Gr=1;
freq=2.4e9;
lambda=3e8/freq;
Pr=Pt*Gt*Gr*(lambda./(4*pi*dist)).^2;
PrdBm=log10(Pr*1000)*10;
SNRdB=PrdBm - PndBm
SNR=10.^(SNRdB/10);
NumStream = 2;  % MIMO: Number of streams

%% Generate bit streams
tx_data = randi(2, 1, Bit_Length) - 1;          

% MIMO: update NumSym
NumSym(MODORDER) = length(tx_data)./MODORDER;

%% Constellation points
% BPSK: {1,0} -> {1+0i, -1+0i}
% QPSK: {11,10,01,00} -> {1+i, -1+i, -1-i, 1-i} * scaling factor
% 16QAM: {1111,1110,1101,1100,1011,1010,1001,1000,0111,0110,0101,0100,0011,0110,0001,0000}
% -> {3a+3ai,3a+ai,a+3ai,a+ai,-a+3ai, -a+ai,-3a+3ai,-3a+ai,3a-ai,3a-3ai,a-ai,a-3i,-a-ai,-a-3ai,-3a-ai,-3a-3ai}


BPSKBit = [0; 1];
BPSK = [-1+0i; 1+0i];
QPSKBit = [0 0; 0 1; 1 0; 1 1];
QPSK = [1-i, -1-i, -1+i, 1+i]./sqrt(2);
QAMBit = [1 1 1 1; 1 1 1 0; 1 1 0 1; 1 1 0 0; 1 0 1 1; 1 0 1 0; 1 0 0 1; 1 0 0 0; 0 1 1 1; 0 1 1 0; 0 1 0 1; 0 1 0 0; 0 0 1 1; 0 0 1 0; 0 0 0 1; 0 0 0 0];
QAM = [3+3i, 3+i, 1+3i, 1+1i, -1+3i, -1+i, -3+3i, -3+i, 3-i, 3-3i, 1-i, 1-3i, -1-i, -1-3i, -3-i, -3-3i]./sqrt(10);
IQPoint(4,:) = QAM;
IQPoint(2,1:4) = QPSK;
IQPoint(1,1:2) = BPSK;

n=(randn(NumStream,Bit_Length)+randn(NumStream, Bit_Length)*i)/sqrt(2);  % MIMO: AWGN noises
n=n*sqrt(Pn);
noise_sq = zeros(5,4,4);
x_prime_total = zeros(5,4,4,1000);
% repeat 5 times
for round = 1:5
    
    
    %% MIMO channel: h dimension:  NumStream x NumStream
    h = (randn(NumStream, NumStream) + randn(NumStream, NumStream) * i);
    h = h ./ abs(h);
    
    % TODO1-channel correlation: cos(theta) = real(dot(h1,h2)) / (norm(h1)*norm(h2))
    % update theta      
    dot_product = abs(real(dot(h(:,1),h(:,2))))/(sqrt(abs(h(1,1)).^2 + abs(h(2,1)).^2) * sqrt(abs(h(1,2)).^2 + abs(h(2,2)).^2));
    theta(1,round) = acosd(dot_product);
    
    
    % TODO2-noise amplification: |H_{i,:}|^2
    % update amp
    w = inv(h);
    amp(1,round) = abs(w(1,1) + w(1,2));
    amp(2,round) = abs(w(2,1) + w(2,2));;
    
    for mod_order = MODORDER

        %% modulation
        if (mod_order == 1)
            % BPSK
            [ans ix] = ismember(tx_data', BPSKBit, 'rows'); 
            s = BPSK(ix);
        elseif (mod_order == 2)
            % QPSK
            tx_data_reshape = reshape(tx_data, length(tx_data)/mod_order, mod_order);
            [ans ix] = ismember(tx_data_reshape, QPSKBit, 'rows');
            s = QPSK(ix);
        else
            % QAM
            tx_data_reshape = reshape(tx_data, length(tx_data)/mod_order, mod_order);
            [ans ix] = ismember(tx_data_reshape, QAMBit, 'rows');
            s = QAM(ix);
        end

        % MIMO: reshape to NumStream streams
        x = reshape(s, NumStream, length(s)/NumStream);


        % uncomment it if you want to plot the constellation points
        % figure('units','normalized','outerposition',[0 0 1 1])
        % sgtitle(sprintf('Modulation order: %d', mod_order)); 

        for d=1:length(dist)
            
            %% transmission with noise
            % TODO3: generate received signals
            % update Y = HX + N
            if mod_order == 1
                y = sqrt(Pr(d)) .* (h * x) + n(:,500);   
            elseif mod_order == 2
                y = sqrt(Pr(d)) .* (h * x) + n(:,250);
            elseif mod_order == 4
                y = sqrt(Pr(d)) .* (h * x) + n(:,125);
            end

            %% ZF equalization
            % TODO4: update x_est = H^-1Y, s_est = reshape(x_est)
            x_est = (w * y) ./ sqrt(Pr(d));
            s_est = reshape(x_est,1,length(s));

            %% demodulation
            % TODO: paste your demodulation code here
            %x_prime = zeros(1,1000);
            if mod_order == 1
                for a=1:length(s_est)
                    if real(s_est(a)) >= 0
                        x_prime(a) = 1;
                    else 
                        x_prime(a) = 0;
                    end
                end
                x_prime_total(round,mod_order,d,:) = x_prime;
            
            %QPSK
            elseif mod_order == 2
                bit_counter = 1;
                for a=1:length(s_est)
                    buffer = s_est(a);
                    err_dist = zeros(1,4);

                    err_dist(1) = abs(buffer - (1+1i)); % 11
                    err_dist(2) = abs(buffer - (-1+1i)); % 10
                    err_dist(3) = abs(buffer - (-1-1i)); % 01
                    err_dist(4) = abs(buffer - (1-1i)); % 00
                    
                    min_dist = err_dist(1);
                    min_idx = 1;
                    
                    for b=1:4
                        if min_dist > err_dist(b)
                            min_dist = err_dist(b);
                            min_idx = b;
                        end
                    end
                    
                    if min_idx == 1
                        x_prime(bit_counter) = 1;
                        x_prime(bit_counter+1) = 1;
                    elseif min_idx == 2
                        x_prime(bit_counter) = 1;
                        x_prime(bit_counter+1) = 0;
                    elseif min_idx == 3
                        x_prime(bit_counter) = 0;
                        x_prime(bit_counter+1) = 1;
                    elseif min_idx == 4
                        x_prime(bit_counter) = 0;
                        x_prime(bit_counter+1) = 0;
                    end
                    
                    bit_counter = bit_counter + 2;
                end
                x_prime_total(round,mod_order,d,:) = x_prime;
                
            % 16 QAM
            elseif mod_order == 4
                bit_counter = 1;
                for a=1:length(s_est)
                    buffer = s_est(a) .* sqrt(10);
                    err_dist = zeros(1,16);

                    err_dist(1) = abs(buffer - (3+3i)); % 1111
                    err_dist(2) = abs(buffer - (3+1i)); % 1110
                    err_dist(3) = abs(buffer - (1+3i)); % 1101
                    err_dist(4) = abs(buffer - (1+1i)); % 1100

                    err_dist(5) = abs(buffer - (-1+3i)); % 1011
                    err_dist(6) = abs(buffer - (-1+1i)); % 1010
                    err_dist(7) = abs(buffer - (-3+3i)); % 1001
                    err_dist(8) = abs(buffer - (-3+1i)); % 1000
                    
                    err_dist(9) = abs(buffer - (3-1i)); % 0111
                    err_dist(10) = abs(buffer - (3-3i)); % 0110
                    err_dist(11) = abs(buffer - (1-1i)); % 0101
                    err_dist(12) = abs(buffer - (1-3i)); % 0100
                    
                    err_dist(13) = abs(buffer - (-1-1i)); % 0011
                    err_dist(14) = abs(buffer - (-1-3i)); % 0010
                    err_dist(15) = abs(buffer - (-3-1i)); % 0001
                    err_dist(16) = abs(buffer - (-3-3i)); % 0000
                    
                    min_dist = err_dist(1);
                    min_idx = 1;
                    for b=1:16
                        if min_dist > err_dist(b)
                            min_dist = err_dist(b);
                            min_idx = b;
                        end
                    end
                    
                    if min_idx == 1 % 1111
                        x_prime(bit_counter) = 1;
                        x_prime(bit_counter+1) = 1;
                        x_prime(bit_counter+2) = 1;
                        x_prime(bit_counter+3) = 1;
                    elseif min_idx == 2 % 1110
                        x_prime(bit_counter) = 1;
                        x_prime(bit_counter+1) = 1;
                        x_prime(bit_counter+2) = 1;
                        x_prime(bit_counter+3) = 0;
                    elseif min_idx == 3 % 1101
                        x_prime(bit_counter) = 1;
                        x_prime(bit_counter+1) = 1;
                        x_prime(bit_counter+2) = 0;
                        x_prime(bit_counter+3) = 1;
                    elseif min_idx == 4 % 1100
                        x_prime(bit_counter) = 1;
                        x_prime(bit_counter+1) = 1;
                        x_prime(bit_counter+2) = 0;
                        x_prime(bit_counter+3) = 0;
                        
                    elseif min_idx == 5 % 1011
                        x_prime(bit_counter) = 1;
                        x_prime(bit_counter+1) = 0;
                        x_prime(bit_counter+2) = 1;
                        x_prime(bit_counter+3) = 1;
                    elseif min_idx == 6 % 1010
                        x_prime(bit_counter) = 1;
                        x_prime(bit_counter+1) = 0;
                        x_prime(bit_counter+2) = 1;
                        x_prime(bit_counter+3) = 0;
                    elseif min_idx == 7 % 1001
                        x_prime(bit_counter) = 1;
                        x_prime(bit_counter+1) = 0;
                        x_prime(bit_counter+2) = 0;
                        x_prime(bit_counter+3) = 1;
                    elseif min_idx == 8 % 1000
                        x_prime(bit_counter) = 1;
                        x_prime(bit_counter+1) = 0;
                        x_prime(bit_counter+2) = 0;
                        x_prime(bit_counter+3) = 0;
                        
                    elseif min_idx == 9 % 0111
                        x_prime(bit_counter) = 0;
                        x_prime(bit_counter+1) = 1;
                        x_prime(bit_counter+2) = 1;
                        x_prime(bit_counter+3) = 1;
                    elseif min_idx == 10 % 0110
                        x_prime(bit_counter) = 0;
                        x_prime(bit_counter+1) = 1;
                        x_prime(bit_counter+2) = 1;
                        x_prime(bit_counter+3) = 0;
                    elseif min_idx == 11 % 0101
                        x_prime(bit_counter) = 0;
                        x_prime(bit_counter+1) = 1;
                        x_prime(bit_counter+2) = 0;
                        x_prime(bit_counter+3) = 1;
                    elseif min_idx == 12 % 0100
                        x_prime(bit_counter) = 0;
                        x_prime(bit_counter+1) = 1;
                        x_prime(bit_counter+2) = 0;
                        x_prime(bit_counter+3) = 0;
                        
                    elseif min_idx == 13 % 0011
                        x_prime(bit_counter) = 0;
                        x_prime(bit_counter+1) = 0;
                        x_prime(bit_counter+2) = 1;
                        x_prime(bit_counter+3) = 1;
                    elseif min_idx == 14 % 0010
                        x_prime(bit_counter) = 0;
                        x_prime(bit_counter+1) = 0;
                        x_prime(bit_counter+2) = 1;
                        x_prime(bit_counter+3) = 0;
                    elseif min_idx == 15 % 0001
                        x_prime(bit_counter) = 0;
                        x_prime(bit_counter+1) = 0;
                        x_prime(bit_counter+2) = 0;
                        x_prime(bit_counter+3) = 1;
                    elseif min_idx == 16 % 0000
                        x_prime(bit_counter) = 0;
                        x_prime(bit_counter+1) = 0;
                        x_prime(bit_counter+2) = 0;
                        x_prime(bit_counter+3) = 0;
                        
                    end
                    
                    bit_counter = bit_counter + 4;
                end
                x_prime_total(round,mod_order,d,:) = x_prime;
            end
            
            SNR(round,d,mod_order)=Pr(d)/Pn;
            SNRdB(round,d,mod_order)=10*log10(SNR(round,d,mod_order));
            BER_simulated(round,d,mod_order)=0;
            SNRdB_simulated(round,d,mod_order)=0;
            
            % TODO: paste your code for calculating BER here
            for a=1:length(tx_data)
                if x_prime(a) ~= tx_data(a)
                    BER_simulated(round,d,mod_order) = BER_simulated(round,d,mod_order) + (1/Bit_Length);
                end
            end
            
            
            for a=1:length(s_est)
                noise_sq(round,mod_order,d) = noise_sq(round,mod_order,d) + abs((s_est(a) - s(a))).^2;
            end
            SNRdB_simulated(round,d,mod_order) = 10*log10(1/(noise_sq(round,mod_order,d)/1000));

            %{
            subplot(2, 2, d)
            hold on;

            plot(s_est,'bx'); 
            plot(s,'ro');
            hold off;
            xlim([-2,2]);
            ylim([-2,2]);
            title(sprintf('Constellation points d=%d', dist(d)));
            legend('decoded samples', 'transmitted samples');
            grid
            %}
        end
        % filename = sprintf('IQ_%d.jpg', mod_order);
        % saveas(gcf,filename,'jpg')
    end
end

%% TODO5: analyze how channel correlation impacts ZF in your report
figure('units','normalized','outerposition',[0 0 1 1])
hold on;
bar(dist,SNRdB_simulated(:,:,1));
plot(dist,SNRdB(1,:,1),'bx-', 'Linewidth', 1.5);
hold off;
title('SNR');
xlabel('Distance [m]');
ylabel('SNR [dB]');
legend('simu-1', 'simu-2', 'simu-3', 'simu-4', 'simu-5', 'siso-theory');
axis tight 
grid
saveas(gcf,'SNR.jpg','jpg')

figure('units','normalized','outerposition',[0 0 1 1])
hold on;
bar(1:5, theta);
hold off;
title('channel angle');
xlabel('Iteration index');
ylabel('angle [degree]');
axis tight 
grid
saveas(gcf,'angle.jpg','jpg')

figure('units','normalized','outerposition',[0 0 1 1])
hold on;
bar(1:5, amp);
hold off;
title('Amplification');
xlabel('Iteration index');
ylabel('noise amplification');
legend('x1', 'x2');
axis tight 
grid
saveas(gcf,'amp.jpg','jpg')

figure('units','normalized','outerposition',[0 0 1 1])
hold on;
plot(dist,mean(BER_simulated(:,:,1),1),'bo-','linewidth',2.0);
plot(dist,mean(BER_simulated(:,:,2),1),'rv--','linewidth',2.0);
plot(dist,mean(BER_simulated(:,:,4),1),'mx-.','linewidth',2.0);
hold off;
title('BER');
xlabel('Distance [m]');
ylabel('BER');
legend('BPSK','QPSK','16QAM');
axis tight 
grid
saveas(gcf,'BER.jpg','jpg')
return;
