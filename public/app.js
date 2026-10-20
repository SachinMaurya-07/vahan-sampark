(() => {

  const cfg = window.CAR_CONFIG;

  const $ = id => document.getElementById(id);

  const callBtn = $('callBtn');
  const hangBtn = $('hangBtn');
  const status = $('status');

  const panel = $('callPanel');
  const title = $('callTitle');
  const timerEl = $('timer');
  const diag = $('diagnostics');

  let socket;
  let pc;
  let localStream;
  let roomId;

  let timerStart;
  let timerInterval;

  let remoteDescriptionSet = false;
  let pendingCandidates = [];

  /*
  |--------------------------------------------------------------------------
  | CALL DATABASE
  |--------------------------------------------------------------------------
  */

  async function callAPI(action) {

    if (!roomId) return;

    try {

      const body = new URLSearchParams();

      body.append('action', action);
      body.append('room_id', roomId);
      body.append('car_code', cfg.carCode);

      await fetch('call_api.php', {
        method: 'POST',
        headers: {
          'Content-Type':
            'application/x-www-form-urlencoded'
        },
        body
      });

      logDiag(
        'Call history',
        action,
        'diag-ok'
      );

    } catch (e) {

      logDiag(
        'Call history',
        'Database update failed',
        'diag-warn'
      );
    }
  }


  /*
  |--------------------------------------------------------------------------
  | STATUS
  |--------------------------------------------------------------------------
  */

  function setStatus(text) {

    if (status) {
      status.textContent = text;
    }
  }


  /*
  |--------------------------------------------------------------------------
  | DIAGNOSTICS
  |--------------------------------------------------------------------------
  */

  function logDiag(
    label,
    value,
    cls = ''
  ) {

    if (!diag) return;

    const row =
      document.createElement('div');

    row.className = cls;

    row.textContent =
      `${label}: ${value}`;

    diag.appendChild(row);
  }


  function clearDiag() {

    if (diag) {
      diag.innerHTML = '';
    }
  }


  /*
  |--------------------------------------------------------------------------
  | TIMER
  |--------------------------------------------------------------------------
  */

  function fmt(sec) {

    return String(
      Math.floor(sec / 60)
    ).padStart(2, '0')
    + ':'
    + String(sec % 60).padStart(2, '0');

  }


  function startTimer() {

    timerStart = Date.now();

    timerInterval =
      setInterval(() => {

        if (timerEl) {

          timerEl.textContent =
            fmt(
              Math.floor(
                (Date.now() - timerStart) / 1000
              )
            );

        }

      }, 1000);
  }


  function stopTimer() {

    clearInterval(timerInterval);

    timerInterval = null;
  }


  /*
  |--------------------------------------------------------------------------
  | WEBRTC
  |--------------------------------------------------------------------------
  */

  function makePC() {

    const p =
      new RTCPeerConnection({
        iceServers:
          cfg.iceServers || []
      });

    logDiag(
      'ICE servers',
      JSON.stringify(
        cfg.iceServers || []
      )
    );


    p.onicegatheringstatechange = () => {

      logDiag(
        'ICE gathering',
        p.iceGatheringState
      );

    };


    p.oniceconnectionstatechange = () => {

      const c =
        p.iceConnectionState;

      logDiag(
        'ICE connection',
        c,
        (
          c === 'connected' ||
          c === 'completed'
        )
          ? 'diag-ok'
          : c === 'failed'
          ? 'diag-err'
          : 'diag-warn'
      );

    };


    p.onsignalingstatechange = () => {

      logDiag(
        'Signaling state',
        p.signalingState
      );

    };


    p.onconnectionstatechange = () => {

      const c =
        p.connectionState;

      logDiag(
        'Peer connection',
        c,
        c === 'connected'
          ? 'diag-ok'
          : c === 'failed'
          ? 'diag-err'
          : 'diag-warn'
      );


      if (c === 'connected') {

        title.textContent =
          'Connected';

      }


      if (
        c === 'failed' ||
        c === 'closed'
      ) {

        endCall();

      }

    };


    p.onicecandidate = e => {

      if (e.candidate) {

        socket.emit(
          'webrtc:ice',
          {
            roomId,
            candidate: e.candidate
          }
        );

      }

    };


    p.ontrack = e => {

      logDiag(
        'Remote audio',
        'Received',
        'diag-ok'
      );


      let audio =
        document.getElementById(
          'remoteAudio'
        );


      if (!audio) {

        audio =
          document.createElement(
            'audio'
          );

        audio.id =
          'remoteAudio';

        audio.autoplay = true;
        audio.playsInline = true;

        document.body.appendChild(
          audio
        );

      }


      audio.srcObject =
        e.streams[0];


      audio.play()
        .then(() => {

          logDiag(
            'Remote audio',
            'Playing',
            'diag-ok'
          );

        })
        .catch(() => {

          logDiag(
            'Remote audio',
            'Browser blocked playback',
            'diag-warn'
          );

        });

    };


    return p;
  }


  /*
  |--------------------------------------------------------------------------
  | START CALL
  |--------------------------------------------------------------------------
  */

  async function init() {

    clearDiag();

    logDiag(
      'Browser',
      navigator.userAgent
    );


    logDiag(
      'Secure context',
      window.isSecureContext
        ? 'YES'
        : 'NO',
      window.isSecureContext
        ? 'diag-ok'
        : 'diag-err'
    );


    if (
      !navigator.mediaDevices ||
      !navigator.mediaDevices.getUserMedia
    ) {

      setStatus(
        'This browser cannot access the microphone. Use HTTPS.'
      );

      return;

    }


    try {

      setStatus(
        'Requesting microphone permission…'
      );


      localStream =
        await navigator.mediaDevices
          .getUserMedia({
            audio: true
          });


      logDiag(
        'Microphone',
        'Granted',
        'diag-ok'
      );


      socket =
        io(
          cfg.signalUrl,
          {
            transports: ['polling'],
            upgrade: false,
            timeout: 10000,
            reconnection: true
          }
        );


      /*
      |--------------------------------------------------------------------------
      | CONNECT
      |--------------------------------------------------------------------------
      */

      socket.on(
        'connect',
        async () => {

          roomId =
            'car-' +
            cfg.carCode +
            '-' +
            crypto.randomUUID();


          /*
          | Create DB session
          */

          await callAPI('create');


          socket.emit(
            'visitor:join',
            {
              carCode:
                cfg.carCode,

              roomId
            }
          );


          setStatus(
            'Calling the owner…'
          );

        }
      );


      /*
      |--------------------------------------------------------------------------
      | CONNECT ERROR
      |--------------------------------------------------------------------------
      */

      socket.on(
        'connect_error',
        e => {

          logDiag(
            'Signaling error',
            e.message,
            'diag-err'
          );

          setStatus(
            'Could not connect to calling server.'
          );

          callBtn.disabled = false;

        }
      );


      /*
      |--------------------------------------------------------------------------
      | OPERATOR READY
      |--------------------------------------------------------------------------
      */

      socket.on(
        'operator:ready',
        () => {

          logDiag(
            'Operator',
            'Available',
            'diag-ok'
          );

          setStatus(
            'Owner is available. Waiting for answer…'
          );

        }
      );


      /*
      |--------------------------------------------------------------------------
      | CALL ACCEPTED
      |--------------------------------------------------------------------------
      */

      socket.on(
        'call:accepted',
        async () => {

          panel.classList.remove(
            'hidden'
          );

          title.textContent =
            'Connecting…';

          startTimer();


          pc =
            makePC();


          localStream
            .getTracks()
            .forEach(
              track =>
                pc.addTrack(
                  track,
                  localStream
                )
            );


          const offer =
            await pc.createOffer();


          await pc.setLocalDescription(
            offer
          );


          socket.emit(
            'webrtc:offer',
            {
              roomId,
              offer
            }
          );

        }
      );


      /*
      |--------------------------------------------------------------------------
      | ANSWER
      |--------------------------------------------------------------------------
      */

      socket.on(
        'webrtc:answer',
        async ({ answer }) => {

          try {

            await pc.setRemoteDescription(
              answer
            );

            remoteDescriptionSet =
              true;


            for (
              const candidate
              of pendingCandidates
            ) {

              try {

                await pc.addIceCandidate(
                  candidate
                );

              } catch (e) {}

            }


            pendingCandidates = [];

          } catch (e) {

            logDiag(
              'Answer error',
              e.message,
              'diag-err'
            );

          }

        }
      );


      /*
      |--------------------------------------------------------------------------
      | ICE
      |--------------------------------------------------------------------------
      */

      socket.on(
        'webrtc:ice',
        async ({ candidate }) => {

          if (
            !pc ||
            !remoteDescriptionSet
          ) {

            pendingCandidates.push(
              candidate
            );

            return;

          }


          try {

            await pc.addIceCandidate(
              candidate
            );

          } catch (e) {

            logDiag(
              'Remote ICE error',
              e.message,
              'diag-err'
            );

          }

        }
      );


      /*
      |--------------------------------------------------------------------------
      | CALL ENDED
      |--------------------------------------------------------------------------
      */

      socket.on(
        'call:ended',
        () => {

          endCall(false);

        }
      );


      /*
      |--------------------------------------------------------------------------
      | CALL REJECTED
      |--------------------------------------------------------------------------
      */

      socket.on(
        'call:rejected',
        async () => {

          await callAPI(
            'rejected'
          );

          setStatus(
            'The owner did not answer.'
          );

          endCall(
            false,
            false
          );

        }
      );

    } catch (e) {

      logDiag(
        'Microphone error',
        e.message,
        'diag-err'
      );

      setStatus(
        'Microphone permission is required.'
      );

      callBtn.disabled = false;

    }

  }


  /*
  |--------------------------------------------------------------------------
  | END CALL
  |--------------------------------------------------------------------------
  */

  async function endCall(
    send = true,
    updateDatabase = true
  ) {

    stopTimer();


    if (
      updateDatabase &&
      roomId
    ) {

      await callAPI(
        'ended'
      );

    }


    localStream
      ?.getTracks()
      .forEach(
        track => track.stop()
      );


    if (pc) {

      pc.close();

      pc = null;

    }


    if (
      send &&
      socket &&
      roomId
    ) {

      socket.emit(
        'call:end',
        {
          roomId
        }
      );

    }


    panel.classList.add(
      'hidden'
    );


    setStatus(
      'Call ended.'
    );


    callBtn.disabled = false;

  }


  /*
  |--------------------------------------------------------------------------
  | BUTTON
  |--------------------------------------------------------------------------
  */

  callBtn?.addEventListener(
    'click',
    async () => {

      callBtn.disabled = true;

      await init();

    }
  );


  hangBtn?.addEventListener(
    'click',
    () => endCall()
  );

})();