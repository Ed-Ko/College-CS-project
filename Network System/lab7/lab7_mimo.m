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
% -> {3a+3ai,3a+ai,a+3ai,a+ai,-a+3ai,-3a+3ai,-3a+ai,3a-ai,3a-3ai,a-ai,a-3i,-a-ai,-a-3ai,-3a-ai,-3a-3ai}


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

% repeat 5 times
for round = 1:5
    
    %% MIMO channel: h dimension:  NumStream x NumStream
    h = (randn(NumStream, NumStream) + randn(NumStream, NumStream) * i);
    h = h ./ abs(h);
    
    % TODO1-channel correlation: cos(theta) = real(dot(h1,h2)) / (norm(h1)*norm(h2))
    % update theta
    theta = 0;
    % TODO2-noise amplification: |H_{i,:}|^2
    % update amp
    w = inv(h);
    amp(1,round) = 0;
    amp(2,round) = 0;
    
    for mod_order = MODORDER

        %% modulation
        if (mod_order == 1)
            % BPSK
            [ans ix] = ismember(tx_data', BPSKBit, 'rows'); 
            s = BPSK(ix).';
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
            y = 1;

            %% ZF equalization
            % TODO4: update x_ext = H^-1Y, s_ext = reshape(x_est)
            s_est = 1;

            %% demodulation
            % TODO: paste your demodulation code here


            % TODO: paste your code for calculating BER here
            SNR(round,d,mod_order)=Pr(d)/Pn;
            SNRdB(round,d,mod_order)=10*log10(SNR(round,d,mod_order))
            BER_simulated(round,d,mod_order)=0
            SNRdB_simulated(round,d,mod_order)=0

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
