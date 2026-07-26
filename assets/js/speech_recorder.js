/* ============================================================
   AI Meeting Minutes Summarizer - Web Speech API Recorder
   ============================================================ */

const SpeechRecorder = {
  recognition: null,
  isRecording: false,
  secondsElapsed: 0,
  timerInterval: null,
  targetTextarea: null,

  init(textareaId, micBtnId, timerDisplayId) {
    this.targetTextarea = document.getElementById(textareaId);
    const micBtn = document.getElementById(micBtnId);
    const timerDisplay = document.getElementById(timerDisplayId);

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
      if (micBtn) {
        micBtn.style.display = 'none';
        App.showToast('Web Speech API is not supported in this browser. You can still paste or type transcripts.', 'info');
      }
      return;
    }

    this.recognition = new SpeechRecognition();
    this.recognition.continuous = true;
    this.recognition.interimResults = true;
    this.recognition.lang = 'en-US';

    this.recognition.onstart = () => {
      this.isRecording = true;
      if (micBtn) micBtn.classList.add('active');
      this.startTimer(timerDisplay);
      App.showToast('Voice recording started. Speak clearly...', 'info');
    };

    this.recognition.onresult = (event) => {
      let finalTranscript = '';
      for (let i = event.resultIndex; i < event.results.length; ++i) {
        if (event.results[i].isFinal) {
          finalTranscript += event.results[i][0].transcript + ' ';
        }
      }
      if (finalTranscript && this.targetTextarea) {
        this.targetTextarea.value += (this.targetTextarea.value ? '\n' : '') + finalTranscript.trim();
        this.targetTextarea.scrollTop = this.targetTextarea.scrollHeight;
      }
    };

    this.recognition.onerror = (event) => {
      console.error('Speech recognition error:', event.error);
      this.stop();
    };

    this.recognition.onend = () => {
      if (this.isRecording) {
        // Auto restart if continuous stream drops
        try { this.recognition.start(); } catch (e) { this.stop(); }
      }
    };

    if (micBtn) {
      micBtn.addEventListener('click', () => {
        if (this.isRecording) {
          this.stop();
        } else {
          this.start();
        }
      });
    }
  },

  start() {
    if (this.recognition && !this.isRecording) {
      try {
        this.recognition.start();
      } catch (e) {
        console.error(e);
      }
    }
  },

  stop() {
    this.isRecording = false;
    if (this.recognition) {
      try { this.recognition.stop(); } catch(e){}
    }
    const micBtn = document.querySelector('.pulse-mic');
    if (micBtn) micBtn.classList.remove('active');
    this.stopTimer();
    App.showToast('Voice recording paused.', 'success');
  },

  startTimer(displayEl) {
    this.secondsElapsed = 0;
    this.stopTimer();
    this.timerInterval = setInterval(() => {
      this.secondsElapsed++;
      const mins = String(Math.floor(this.secondsElapsed / 60)).padStart(2, '0');
      const secs = String(this.secondsElapsed % 60).padStart(2, '0');
      if (displayEl) displayEl.textContent = `${mins}:${secs}`;
    }, 1000);
  },

  stopTimer() {
    if (this.timerInterval) {
      clearInterval(this.timerInterval);
      this.timerInterval = null;
    }
  }
};
