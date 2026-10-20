(() => {
  const cfg=window.CAR_CONFIG;
  const $=id=>document.getElementById(id);
  const status=$('status'),incoming=$('incoming'),incomingBanner=$('incomingBanner'),active=$('active'),answer=$('answerBtn'),reject=$('rejectBtn'),hang=$('hangBtn'),timer=$('timer'),diag=$('diagnostics');
  let socket,pc,localStream,roomId,pendingCandidates=[],timerStart,interval,remoteDescriptionSet=false;
  let ringCtx=null,ringTimer=null,ringEnabled=false;

  function logDiag(label,value,cls=''){
    if(!diag)return;
    const row=document.createElement('div');row.className=cls;row.textContent=`${label}: ${value}`;diag.appendChild(row);
  }
  function fmt(s){return String(Math.floor(s/60)).padStart(2,'0')+':'+String(s%60).padStart(2,'0');}
  function tick(){timer.textContent=fmt(Math.floor((Date.now()-timerStart)/1000));}
  function startTimer(){timerStart=Date.now();interval=setInterval(tick,1000);}
  function stopTimer(){clearInterval(interval);}


  function startRinging(){
    stopRinging();
    try{
      ringCtx=ringCtx || new (window.AudioContext||window.webkitAudioContext)();
      if(ringCtx.state==='suspended') ringCtx.resume().catch(()=>{});
      const beep=()=>{
        if(!ringCtx)return;
        const osc=ringCtx.createOscillator(), gain=ringCtx.createGain();
        osc.type='sine'; osc.frequency.value=880;
        gain.gain.setValueAtTime(0.0001,ringCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.12,ringCtx.currentTime+0.03);
        gain.gain.exponentialRampToValueAtTime(0.0001,ringCtx.currentTime+0.35);
        osc.connect(gain); gain.connect(ringCtx.destination); osc.start(); osc.stop(ringCtx.currentTime+0.4);
        if(navigator.vibrate) navigator.vibrate([300,150,300]);
      };
      beep(); ringTimer=setInterval(beep,1800); ringEnabled=true;
    }catch(e){ logDiag('Ringing sound', 'browser blocked audio', 'diag-warn'); }
  }
  function stopRinging(){
    if(ringTimer){clearInterval(ringTimer);ringTimer=null;}
    if(navigator.vibrate) navigator.vibrate(0);
    ringEnabled=false;
  }

  function makePC(){
    const p=new RTCPeerConnection({iceServers:cfg.iceServers||[]});
    logDiag('ICE servers',JSON.stringify(cfg.iceServers||[]));
    p.onicegatheringstatechange=()=>logDiag('ICE gathering',p.iceGatheringState);
    p.oniceconnectionstatechange=()=>{const c=p.iceConnectionState;logDiag('ICE connection',c,c==='connected'||c==='completed'?'diag-ok':c==='failed'?'diag-err':'diag-warn');};
    p.onsignalingstatechange=()=>logDiag('Signaling state',p.signalingState);
    p.onconnectionstatechange=()=>{const c=p.connectionState;logDiag('Peer connection',c,c==='connected'?'diag-ok':c==='failed'?'diag-err':'diag-warn');if(c==='connected')status.textContent='Call connected';if(['failed','closed'].includes(c))finish(false);};
    p.onicecandidate=e=>{if(e.candidate){logDiag('Local ICE candidate','gathered');socket.emit('webrtc:ice',{roomId,candidate:e.candidate});}else logDiag('ICE gathering','complete');};
    p.ontrack=e=>{logDiag('Remote audio track','received','diag-ok');let a=document.getElementById('remoteAudio');if(!a){a=document.createElement('audio');a.id='remoteAudio';a.autoplay=true;a.playsInline=true;document.body.appendChild(a);}a.srcObject=e.streams[0];a.play().then(()=>logDiag('Remote audio playback','playing','diag-ok')).catch(()=>logDiag('Remote audio playback','blocked by browser','diag-warn'));};
    return p;
  }

  async function start(){
    if(!navigator.mediaDevices?.getUserMedia){status.textContent='This browser cannot access the microphone. Use HTTPS.';return;}
    try{
      logDiag('Secure context',window.isSecureContext?'YES':'NO',window.isSecureContext?'diag-ok':'diag-err');
      localStream=await navigator.mediaDevices.getUserMedia({audio:true});logDiag('Microphone','granted','diag-ok');
      socket=io(cfg.signalUrl,{transports:['polling'],upgrade:false,timeout:10000,reconnection:true});
      socket.on('connect',()=>{logDiag('Signaling server','connected','diag-ok');socket.emit('operator:join',{carCode:cfg.carCode});status.textContent='Connected to calling server. Waiting for a visitor…';});
      socket.on('connect_error',e=>{logDiag('Signaling error',e.message,'diag-err');status.textContent='Could not connect to calling server.';});
      socket.on('visitor:calling',d=>{roomId=d.roomId;pendingCandidates=[];remoteDescriptionSet=false;status.textContent='Incoming private call…';incoming.classList.remove('hidden');incomingBanner?.classList.remove('hidden');startRinging();});
      socket.on('webrtc:offer',async d=>{
        try{
          logDiag('Offer','received','diag-ok');
          pc=makePC();localStream.getTracks().forEach(t=>pc.addTrack(t,localStream));logDiag('Local audio track','added','diag-ok');
          await pc.setRemoteDescription(d.offer);remoteDescriptionSet=true;logDiag('Remote offer','set','diag-ok');
          for(const c of pendingCandidates){try{await pc.addIceCandidate(c)}catch(e){}}pendingCandidates=[];
          const ans=await pc.createAnswer();await pc.setLocalDescription(ans);socket.emit('webrtc:answer',{roomId,answer:ans});logDiag('Answer','sent','diag-ok');
        }catch(e){logDiag('Offer error',e.message,'diag-err');status.textContent='Could not establish audio connection.';finish(false);}
      });
      socket.on('webrtc:ice',async({candidate})=>{if(!pc||!remoteDescriptionSet){pendingCandidates.push(candidate);logDiag('Remote ICE','queued');return;}try{await pc.addIceCandidate(candidate);logDiag('Remote ICE','added','diag-ok')}catch(e){logDiag('Remote ICE error',e.message,'diag-err')}});
      socket.on('call:ended',()=>finish(false));
    }catch(e){logDiag('Microphone error',e.message,'diag-err');status.textContent='Microphone permission is required.';}
  }

  function accept(){if(!roomId)return;stopRinging();socket.emit('call:accepted',{roomId});incoming.classList.add('hidden');incomingBanner?.classList.add('hidden');active.classList.remove('hidden');status.textContent='Connecting to visitor…';startTimer();}
  function rejectCall(){stopRinging();socket.emit('call:rejected',{roomId});incoming.classList.add('hidden');incomingBanner?.classList.add('hidden');status.textContent='Call rejected';}
  function finish(send=true){stopRinging();stopTimer();pc?.close();pc=null;active.classList.add('hidden');incoming.classList.add('hidden');incomingBanner?.classList.add('hidden');status.textContent='Waiting for a visitor…';if(send)socket?.emit('call:end',{roomId});}
  answer.addEventListener('click',accept);reject.addEventListener('click',rejectCall);hang.addEventListener('click',()=>finish());
  start().catch(e=>console.error(e));
})();
