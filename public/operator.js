(() => {

  const cfg = window.CAR_CONFIG;

  const $ = (id) => document.getElementById(id);


  // =====================================================
  // UI ELEMENTS
  // =====================================================

  const status = $('status');
  const incoming = $('incoming');
  const incomingBanner = $('incomingBanner');
  const active = $('active');

  const answer = $('answerBtn');
  const reject = $('rejectBtn');
  const hang = $('hangBtn');

  const timer = $('timer');
  const diag = $('diagnostics');
  const enableSoundBtn = $('enableSoundBtn');


  // =====================================================
  // WEBRTC / SOCKET VARIABLES
  // =====================================================

  let socket = null;
  let pc = null;
  let localStream = null;
  let roomId = null;

  let pendingCandidates = [];
  let remoteDescriptionSet = false;


  // =====================================================
  // TIMER
  // =====================================================

  let timerStart = null;
  let interval = null;


  // =====================================================
  // RINGTONE
  // =====================================================

  let ringtone = null;
  let soundEnabled = false;

  const RINGTONE_FILE =
    'sounds/my-ringtone.mp3';


  // =====================================================
  // PWA / NOTIFICATION
  // =====================================================

  let serviceWorkerRegistration = null;


  /*
  |--------------------------------------------------------------------------
  | INITIALIZE PWA
  |--------------------------------------------------------------------------
  */

  async function initPWA() {

    if (
      !('serviceWorker' in navigator)
    ) {

      logDiag(
        'PWA',
        'Service Worker unavailable',
        'diag-warn'
      );

      return;

    }


    try {

      serviceWorkerRegistration =
        await navigator.serviceWorker.ready;


      logDiag(
        'PWA',
        'Service Worker ready',
        'diag-ok'
      );

    } catch (e) {

      logDiag(
        'PWA',
        'Service Worker not ready',
        'diag-warn'
      );

    }


    /*
    |--------------------------------------------------------------------------
    | Notification Permission
    |--------------------------------------------------------------------------
    */

    if (
      'Notification' in window
    ) {

      logDiag(
        'Notifications',
        Notification.permission
      );

    }

  }


  /*
  |--------------------------------------------------------------------------
  | SHOW INCOMING CALL NOTIFICATION
  |--------------------------------------------------------------------------
  */

  async function showIncomingNotification() {

    if (
      !('Notification' in window)
    ) {

      logDiag(
        'Notification',
        'Browser unsupported',
        'diag-warn'
      );

      return;

    }


    if (
      Notification.permission !== 'granted'
    ) {

      logDiag(
        'Notification',
        'Permission not granted',
        'diag-warn'
      );

      return;

    }


    try {

      if (!serviceWorkerRegistration) {

        serviceWorkerRegistration =
          await navigator.serviceWorker.ready;

      }


      const operatorUrl =
        window.location.href;


      await serviceWorkerRegistration
        .showNotification(
          '🚨 Incoming Vehicle Call',
          {

            body:
              'Someone is trying to contact your vehicle.',

            icon:
              './icon-192.png',

            badge:
              './icon-192.png',

            tag:
              'vahan-sampark-incoming-call',

            requireInteraction:
              true,

            vibrate: [
              500,
              200,
              500,
              200,
              800
            ],

            data: {
              type:
                'incoming-call',

              url:
                operatorUrl
            }

          }
        );


      logDiag(
        'Notification',
        'Incoming call notification shown',
        'diag-ok'
      );

    } catch (e) {

      logDiag(
        'Notification',
        e.message,
        'diag-warn'
      );

    }

  }


  /*
  |--------------------------------------------------------------------------
  | CLOSE INCOMING NOTIFICATION
  |--------------------------------------------------------------------------
  */

  async function closeIncomingNotification() {

    if (
      !('serviceWorker' in navigator)
    ) {
      return;
    }


    try {

      const registration =
        await navigator.serviceWorker.ready;


      const notifications =
        await registration
          .getNotifications({
            tag:
              'vahan-sampark-incoming-call'
          });


      notifications.forEach(
        notification => {
          notification.close();
        }
      );

    } catch (e) {

      console.log(
        'Could not close notification:',
        e
      );

    }

  }


  // =====================================================
  // DIAGNOSTICS
  // =====================================================

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


  // =====================================================
  // STATUS
  // =====================================================

  function setStatus(text) {

    if (status) {
      status.textContent = text;
    }

  }


  // =====================================================
  // CALL HISTORY API
  // =====================================================

  async function callAPI(action) {

    if (!roomId) {
      return false;
    }


    try {

      const body =
        new URLSearchParams();


      body.append(
        'action',
        action
      );


      body.append(
        'room_id',
        roomId
      );


      body.append(
        'car_code',
        cfg.carCode
      );


      const response =
        await fetch(
          'call_api.php',
          {
            method: 'POST',

            headers: {
              'Content-Type':
                'application/x-www-form-urlencoded'
            },

            body:
              body.toString()
          }
        );


      if (!response.ok) {

        throw new Error(
          `HTTP ${response.status}`
        );

      }


      const data =
        await response.json();


      if (!data.success) {

        throw new Error(
          data.error ||
          'Call history update failed'
        );

      }


      logDiag(
        'Call history',
        action,
        'diag-ok'
      );


      return true;


    } catch (e) {

      logDiag(
        'Call history',
        e.message,
        'diag-warn'
      );


      return false;

    }

  }


  // =====================================================
  // RINGTONE INITIALIZATION
  // =====================================================

  function initAudio() {

    try {

      if (!ringtone) {

        ringtone =
          new Audio(
            RINGTONE_FILE
          );


        ringtone.loop =
          true;


        ringtone.preload =
          'auto';


        ringtone.volume =
          1.0;

      }


      ringtone.volume =
        1.0;


      soundEnabled =
        true;


      logDiag(
        'Ringtone',
        'Enabled',
        'diag-ok'
      );


      return true;


    } catch (e) {

      logDiag(
        'Ringtone',
        'Could not initialize',
        'diag-err'
      );


      return false;

    }

  }


  // =====================================================
  // START RINGING
  // =====================================================

  function startRinging() {

    stopRinging();


    if (!soundEnabled) {

      initAudio();

    }


    if (ringtone) {

      ringtone.currentTime =
        0;


      ringtone.volume =
        1.0;


      ringtone.play()
        .then(() => {

          logDiag(
            'Custom ringtone',
            'Playing at 100%',
            'diag-ok'
          );

        })
        .catch(() => {

          logDiag(
            'Custom ringtone',
            'Browser blocked playback - enable sound first',
            'diag-warn'
          );

        });

    }


    /*
    |--------------------------------------------------------------------------
    | PHONE VIBRATION
    |--------------------------------------------------------------------------
    */

    if (
      navigator.vibrate
    ) {

      navigator.vibrate([
        500,
        200,
        500,
        200,
        500
      ]);

    }

  }


  // =====================================================
  // STOP RINGING
  // =====================================================

  function stopRinging() {

    if (ringtone) {

      ringtone.pause();


      try {

        ringtone.currentTime =
          0;

      } catch (e) {}

    }


    if (
      navigator.vibrate
    ) {

      navigator.vibrate(0);

    }

  }


  // =====================================================
  // ENABLE SOUND BUTTON
  // =====================================================

  if (enableSoundBtn) {

    enableSoundBtn.addEventListener(
      'click',
      async () => {

        const enabled =
          initAudio();


        if (!enabled) {
          return;
        }


        try {

          await ringtone.play();


          ringtone.pause();


          ringtone.currentTime =
            0;


          ringtone.volume =
            1.0;


          enableSoundBtn.textContent =
            '🔊 Loud Ringtone Enabled';


          enableSoundBtn.classList.add(
            'sound-enabled'
          );


          logDiag(
            'Audio permission',
            'Unlocked',
            'diag-ok'
          );


        } catch (e) {

          logDiag(
            'Audio permission',
            'Browser blocked audio',
            'diag-warn'
          );

        }

      }
    );

  }


  // =====================================================
  // TIMER
  // =====================================================

  function fmt(seconds) {

    return String(
      Math.floor(
        seconds / 60
      )
    ).padStart(2, '0')
      + ':'
      + String(
        seconds % 60
      ).padStart(2, '0');

  }


  function tick() {

    if (!timerStart) {
      return;
    }


    if (timer) {

      timer.textContent =
        fmt(
          Math.floor(
            (
              Date.now() -
              timerStart
            ) / 1000
          )
        );

    }

  }


  function startTimer() {

    stopTimer();


    timerStart =
      Date.now();


    tick();


    interval =
      setInterval(
        tick,
        1000
      );

  }


  function stopTimer() {

    if (interval) {

      clearInterval(
        interval
      );

    }


    interval =
      null;

  }


  // =====================================================
  // WEBRTC PEER CONNECTION
  // =====================================================

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


    // ===================================================
    // ICE GATHERING
    // ===================================================

    p.onicegatheringstatechange =
      () => {

        logDiag(
          'ICE gathering',
          p.iceGatheringState
        );

      };


    // ===================================================
    // ICE CONNECTION
    // ===================================================

    p.oniceconnectionstatechange =
      () => {

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


    // ===================================================
    // SIGNALING STATE
    // ===================================================

    p.onsignalingstatechange =
      () => {

        logDiag(
          'Signaling state',
          p.signalingState
        );

      };


    // ===================================================
    // PEER CONNECTION STATE
    // ===================================================

    p.onconnectionstatechange =
      () => {

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


        if (
          c === 'connected'
        ) {

          setStatus(
            'Call connected'
          );

        }


        if (
          c === 'failed'
        ) {

          finish();

        }


        if (
          c === 'closed'
        ) {

          finish(
            false
          );

        }

      };


    // ===================================================
    // SEND ICE CANDIDATES
    // ===================================================

    p.onicecandidate =
      (event) => {

        if (
          event.candidate
        ) {

          logDiag(
            'Local ICE candidate',
            'Gathered'
          );


          if (
            socket &&
            roomId
          ) {

            socket.emit(
              'webrtc:ice',
              {
                roomId,

                candidate:
                  event.candidate
              }
            );

          }

        } else {

          logDiag(
            'ICE gathering',
            'Complete'
          );

        }

      };


    // ===================================================
    // RECEIVE VISITOR AUDIO
    // ===================================================

    p.ontrack =
      (event) => {

        logDiag(
          'Remote audio track',
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


          audio.autoplay =
            true;


          audio.playsInline =
            true;


          audio.controls =
            false;


          audio.muted =
            false;


          document.body.appendChild(
            audio
          );

        }


        audio.srcObject =
          event.streams[0];


        audio.volume =
          1.0;


        audio.muted =
          false;


        audio.play()
          .then(() => {

            logDiag(
              'Visitor audio',
              'Playing at 100%',
              'diag-ok'
            );

          })
          .catch(() => {

            logDiag(
              'Visitor audio',
              'Browser blocked playback',
              'diag-warn'
            );

          });

      };


    return p;

  }


  // =====================================================
  // START OPERATOR
  // =====================================================

  async function start() {

    clearDiag();


    /*
    |--------------------------------------------------------------------------
    | PWA INITIALIZATION
    |--------------------------------------------------------------------------
    */

    await initPWA();


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


    logDiag(
      'Microphone API',
      navigator.mediaDevices &&
      navigator.mediaDevices.getUserMedia
        ? 'Available'
        : 'Unavailable',

      navigator.mediaDevices &&
      navigator.mediaDevices.getUserMedia
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

      // =================================================
      // MICROPHONE
      // =================================================

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


      // =================================================
      // SOCKET.IO
      // =================================================

      socket =
        io(
          cfg.signalUrl,
          {
            transports: [
              'polling'
            ],

            upgrade:
              false,

            timeout:
              10000,

            reconnection:
              true
          }
        );


      // =================================================
      // SOCKET CONNECTED
      // =================================================

      socket.on(
        'connect',
        () => {

          logDiag(
            'Signaling server',
            'Connected',
            'diag-ok'
          );


          socket.emit(
            'operator:join',
            {
              carCode:
                cfg.carCode
            }
          );


          setStatus(
            'Connected. Waiting for a visitor…'
          );

        }
      );


      // =================================================
      // SOCKET DISCONNECT
      // =================================================

      socket.on(
        'disconnect',
        (reason) => {

          logDiag(
            'Signaling server',
            `Disconnected: ${reason}`,
            'diag-warn'
          );


          setStatus(
            'Reconnecting to calling server…'
          );

        }
      );


      // =================================================
      // SOCKET RECONNECT
      // =================================================

      socket.io.on(
        'reconnect',
        () => {

          logDiag(
            'Signaling server',
            'Reconnected',
            'diag-ok'
          );


          socket.emit(
            'operator:join',
            {
              carCode:
                cfg.carCode
            }
          );


          setStatus(
            'Connected. Waiting for a visitor…'
          );

        }
      );


      // =================================================
      // SOCKET ERROR
      // =================================================

      socket.on(
        'connect_error',
        (e) => {

          logDiag(
            'Signaling error',
            e.message,
            'diag-err'
          );


          setStatus(
            'Could not connect to calling server.'
          );

        }
      );


      // =================================================
      // INCOMING CALL
      // =================================================

      socket.on(
        'visitor:calling',
        async (data) => {

          roomId =
            data.roomId;


          pendingCandidates =
            [];


          remoteDescriptionSet =
            false;


          logDiag(
            'Incoming call',
            'Received',
            'diag-ok'
          );


          setStatus(
            '🚨 Incoming private call'
          );


          if (incoming) {

            incoming.classList.remove(
              'hidden'
            );

          }


          if (incomingBanner) {

            incomingBanner.classList.remove(
              'hidden'
            );

          }


          // Existing ringtone

          startRinging();


          // New PWA notification

          await showIncomingNotification();

        }
      );


      // =================================================
      // RECEIVE OFFER
      // =================================================

      socket.on(
        'webrtc:offer',
        async (data) => {

          try {

            logDiag(
              'Offer',
              'Received',
              'diag-ok'
            );


            // ---------------------------------------------
            // Create PeerConnection
            // ---------------------------------------------

            pc =
              makePC();


            // ---------------------------------------------
            // Add operator microphone
            // ---------------------------------------------

            if (localStream) {

              localStream
                .getTracks()
                .forEach(
                  (track) => {

                    pc.addTrack(
                      track,
                      localStream
                    );

                  }
                );

            }


            logDiag(
              'Operator microphone',
              'Added',
              'diag-ok'
            );


            // ---------------------------------------------
            // Set visitor offer
            // ---------------------------------------------

            await pc.setRemoteDescription(
              data.offer
            );


            remoteDescriptionSet =
              true;


            logDiag(
              'Remote offer',
              'Set',
              'diag-ok'
            );


            // ---------------------------------------------
            // Add queued ICE
            // ---------------------------------------------

            for (
              const candidate
              of pendingCandidates
            ) {

              try {

                await pc.addIceCandidate(
                  candidate
                );

              } catch (e) {

                logDiag(
                  'Queued ICE error',
                  e.message,
                  'diag-warn'
                );

              }

            }


            pendingCandidates =
              [];


            // ---------------------------------------------
            // Create answer
            // ---------------------------------------------

            const answerDescription =
              await pc.createAnswer();


            await pc.setLocalDescription(
              answerDescription
            );


            socket.emit(
              'webrtc:answer',
              {
                roomId,

                answer:
                  answerDescription
              }
            );


            logDiag(
              'Answer',
              'Sent',
              'diag-ok'
            );


          } catch (e) {

            logDiag(
              'Offer error',
              e.message,
              'diag-err'
            );


            setStatus(
              'Could not establish audio connection.'
            );


            finish();

          }

        }
      );


      // =================================================
      // RECEIVE ICE
      // =================================================

      socket.on(
        'webrtc:ice',
        async (data) => {

          const candidate =
            data.candidate;


          if (
            !pc ||
            !remoteDescriptionSet
          ) {

            pendingCandidates.push(
              candidate
            );


            logDiag(
              'Remote ICE',
              'Queued'
            );


            return;

          }


          try {

            await pc.addIceCandidate(
              candidate
            );


            logDiag(
              'Remote ICE',
              'Added',
              'diag-ok'
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


      // =================================================
      // CALL ENDED BY VISITOR
      // =================================================

      socket.on(
        'call:ended',
        () => {

          logDiag(
            'Call',
            'Visitor ended the call'
          );


          closeIncomingNotification();


          finish(
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

    }

  }


  // =====================================================
  // ANSWER CALL
  // =====================================================

  async function accept() {

    if (!roomId) {
      return;
    }


    stopRinging();


    await closeIncomingNotification();


    initAudio();


    // ===================================================
    // DATABASE
    // ===================================================

    await callAPI(
      'connected'
    );


    // ===================================================
    // SIGNALING
    // ===================================================

    socket.emit(
      'call:accepted',
      {
        roomId
      }
    );


    // ===================================================
    // UI
    // ===================================================

    if (incoming) {

      incoming.classList.add(
        'hidden'
      );

    }


    if (incomingBanner) {

      incomingBanner.classList.add(
        'hidden'
      );

    }


    if (active) {

      active.classList.remove(
        'hidden'
      );

    }


    setStatus(
      'Connecting to visitor…'
    );


    startTimer();

  }


  // =====================================================
  // REJECT CALL
  // =====================================================

  async function rejectCall() {

    if (!roomId) {
      return;
    }


    stopRinging();


    await closeIncomingNotification();


    // ===================================================
    // DATABASE
    // ===================================================

    await callAPI(
      'rejected'
    );


    // ===================================================
    // SIGNALING
    // ===================================================

    if (socket) {

      socket.emit(
        'call:rejected',
        {
          roomId
        }
      );

    }


    // ===================================================
    // UI
    // ===================================================

    if (incoming) {

      incoming.classList.add(
        'hidden'
      );

    }


    if (incomingBanner) {

      incomingBanner.classList.add(
        'hidden'
      );

    }


    setStatus(
      'Call rejected. Waiting for a visitor…'
    );


    roomId =
      null;

  }


  // =====================================================
  // FINISH CALL
  // =====================================================

  async function finish(
    send = true
  ) {

    const endingRoom =
      roomId;


    stopRinging();


    await closeIncomingNotification();


    stopTimer();


    // ===================================================
    // DATABASE
    // ===================================================

    if (endingRoom) {

      await callAPI(
        'ended'
      );

    }


    // ===================================================
    // WEBRTC
    // ===================================================

    if (pc) {

      try {

        pc.close();

      } catch (e) {}

      pc =
        null;

    }


    // ===================================================
    // UI
    // ===================================================

    if (active) {

      active.classList.add(
        'hidden'
      );

    }


    if (incoming) {

      incoming.classList.add(
        'hidden'
      );

    }


    if (incomingBanner) {

      incomingBanner.classList.add(
        'hidden'
      );

    }


    setStatus(
      'Waiting for a visitor…'
    );


    // ===================================================
    // SIGNALING
    // ===================================================

    if (
      send &&
      socket &&
      endingRoom
    ) {

      socket.emit(
        'call:end',
        {
          roomId:
            endingRoom
        }
      );

    }


    roomId =
      null;


    pendingCandidates =
      [];


    remoteDescriptionSet =
      false;

  }


  // =====================================================
  // ANSWER BUTTON
  // =====================================================

  if (answer) {

    answer.addEventListener(
      'click',
      accept
    );

  }


  // =====================================================
  // REJECT BUTTON
  // =====================================================

  if (reject) {

    reject.addEventListener(
      'click',
      rejectCall
    );

  }


  // =====================================================
  // HANG UP BUTTON
  // =====================================================

  if (hang) {

    hang.addEventListener(
      'click',
      () => {

        finish();

      }
    );

  }


  // =====================================================
  // START OPERATOR
  // =====================================================

  start().catch(
    (e) => {

      console.error(
        'Operator startup error:',
        e
      );

    }
  );

})();