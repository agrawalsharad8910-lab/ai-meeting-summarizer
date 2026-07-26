<?php
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Meeting Summarizer | MinuteAI</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="navbar">
    <div class="brand-logo">
      <div class="brand-icon">⚡</div>
      <span>Minute<span class="gradient-text">AI</span></span>
    </div>
    <div class="nav-actions">
      <button class="btn btn-outline" id="theme-toggle-btn">🌙 Theme</button>
      <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back to Dashboard</a>
    </div>
  </nav>

  <div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
      <a href="new_meeting.php" class="nav-link active">➕ New Meeting</a>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="page-header">
        <div>
          <h2>Create AI Meeting Summary</h2>
          <p style="color: var(--text-secondary); font-size: 0.9rem;">Record live audio, paste transcript text, or upload a text file.</p>
        </div>
        <button class="btn btn-outline btn-sm" onclick="loadSampleTranscript()">💡 Load Sample Demo Transcript</button>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px;">
        <!-- Main Form Column -->
        <div class="glass-card" style="padding: 28px;">
          
          <!-- Live Speech Recorder Box -->
          <div class="recorder-box" id="recorderContainer">
            <div class="pulse-mic" id="micBtn" title="Click to start/pause live voice recording">🎙️</div>
            <h4 id="recorderStatus" style="margin-bottom: 6px;">Click Microphone to Record Live Meeting Speech</h4>
            <p style="font-size: 0.85rem; color: var(--text-muted);">
              Recording Timer: <strong id="recTimer" style="color: var(--primary);">00:00</strong>
            </p>
          </div>

          <form id="meetingForm" onsubmit="handleProcessSummary(event)">
            <div class="form-group">
              <label class="form-label">Meeting Title</label>
              <input type="text" id="title" class="form-control" placeholder="e.g. Q3 Sprint Planning & Architecture Review" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label">Department</label>
                <select id="department" class="form-control">
                  <option value="Engineering">Engineering</option>
                  <option value="Product Management">Product Management</option>
                  <option value="Sales & Marketing">Sales & Marketing</option>
                  <option value="Design/UX">Design / UX</option>
                  <option value="General">General</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Meeting Date</label>
                <input type="date" id="meeting_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
            </div>

            <div class="form-group">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <label class="form-label" style="margin-bottom: 0;">Meeting Transcript</label>
                <input type="file" id="fileUpload" accept=".txt" style="display: none;" onchange="handleFileUpload(event)">
                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('fileUpload').click()">📁 Upload .txt File</button>
              </div>
              <textarea id="transcript" class="form-control" style="min-height: 220px;" placeholder="Paste meeting transcript here or use the live microphone recorder above...&#10;&#10;Example:&#10;Alex: Let's discuss database optimization for Sprint 3.&#10;Sarah: I will index the meeting_id column to speed up query performance.&#10;John: Excellent, please complete this by Friday." required></textarea>
            </div>

            <button type="submit" id="summarizeBtn" class="btn btn-primary btn-lg" style="width: 100%;">
              ⚡ Generate AI Summary & Minutes
            </button>
          </form>
        </div>

        <!-- Right Options & Settings Panel -->
        <div class="glass-card" style="padding: 24px; height: fit-content;">
          <h4 style="margin-bottom: 16px;">🤖 AI Engine Settings</h4>
          
          <div class="form-group">
            <label class="form-label">AI Processing Mode</label>
            <select id="aiMode" class="form-control" onchange="toggleApiKeyInput()">
              <option value="local">Smart Local NLP Engine (Offline / Fast)</option>
              <option value="gemini">Google Gemini 1.5 Flash API</option>
            </select>
          </div>

          <div class="form-group" id="apiKeyContainer" style="display: none;">
            <label class="form-label">Gemini API Key</label>
            <input type="password" id="apiKey" class="form-control" placeholder="AIzaSy..." value="">
            <small style="color: var(--text-muted); font-size: 0.75rem;">Optional: Enter key for Google Gemini model processing.</small>
          </div>

          <hr style="border-color: var(--border-color); margin: 20px 0;">

          <h5 style="margin-bottom: 10px;">Tips for Best AI Summaries:</h5>
          <ul style="padding-left: 18px; color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6;">
            <li>Use speaker names followed by colons (e.g. <code>Sarah: ...</code>).</li>
            <li>Include explicit action words like <em>"will build"</em>, <em>"need to"</em>, or <em>"assigned to"</em>.</li>
            <li>Mention explicit due dates (e.g. <em>"by Friday"</em>, <em>"next week"</em>).</li>
          </ul>
        </div>
      </div>
    </main>
  </div>

  <script src="assets/js/app.js"></script>
  <script src="assets/js/speech_recorder.js"></script>
  <script>
    let generatedAIResult = null;

    document.addEventListener('DOMContentLoaded', () => {
      SpeechRecorder.init('transcript', 'micBtn', 'recTimer');
    });

    function toggleApiKeyInput() {
      const mode = document.getElementById('aiMode').value;
      document.getElementById('apiKeyContainer').style.display = (mode === 'gemini') ? 'block' : 'none';
    }

    function handleFileUpload(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          document.getElementById('transcript').value = e.target.result;
          App.showToast('Transcript file uploaded successfully!', 'success');
        };
        reader.readAsText(file);
      }
    }

    function loadSampleTranscript() {
      document.getElementById('title').value = 'Project Alpha Architecture & Database Design';
      document.getElementById('department').value = 'Engineering';
      document.getElementById('transcript').value = 
`Alex: Welcome team. Today we need to finalize the database schema for our AI Summarizer project and decide on the API structure.
Sarah: I reviewed the proposed MySQL schema. We have users, meetings, action_items, and key_decisions tables. It looks solid.
John: What about the Gemini API integration? Are we handling fallbacks?
Alex: Yes, Sarah will implement the Gemini API integration in PHP along with an offline NLP fallback rules engine so the app works seamlessly even without an API key.
John: Perfect. I will build the frontend dashboard using CSS custom properties, glassmorphism UI, and dark mode support by Friday.
Alex: Great. We also agreed to use Web Speech API for real-time speech-to-text recording during live meetings.
Sarah: Sounds like a plan. I will complete the backend API endpoints by Thursday.
Alex: Meeting adjourned.`;
      App.showToast('Sample transcript loaded! Click Generate AI Summary.', 'info');
    }

    async function handleProcessSummary(e) {
      e.preventDefault();

      const btn = document.getElementById('summarizeBtn');
      btn.disabled = true;
      btn.innerHTML = '⚡ Processing AI Summary (Parsing Speech & Actions)...';

      const payload = {
        title: document.getElementById('title').value,
        department: document.getElementById('department').value,
        meeting_date: document.getElementById('meeting_date').value,
        transcript: document.getElementById('transcript').value,
        api_key: document.getElementById('apiKey').value
      };

      try {
        const res = await fetch('api/summarize.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
          generatedAIResult = data.data;
          // Auto-save meeting summary into MySQL database
          await autoSaveMeeting(payload, generatedAIResult);
        } else {
          App.showToast(data.message, 'error');
          btn.disabled = false;
          btn.innerHTML = '⚡ Generate AI Summary & Minutes';
        }
      } catch (err) {
        App.showToast('AI Summarizer server error', 'error');
        btn.disabled = false;
        btn.innerHTML = '⚡ Generate AI Summary & Minutes';
      }
    }

    async function autoSaveMeeting(formData, aiData) {
      const savePayload = {
        action: 'save',
        title: formData.title,
        department: formData.department,
        meeting_date: formData.meeting_date,
        duration_minutes: 30,
        raw_transcript: formData.transcript,
        executive_summary: aiData.executive_summary,
        key_points: aiData.key_points,
        sentiment: aiData.sentiment || 'Productive',
        word_count: aiData.word_count || 150,
        action_items: aiData.action_items,
        key_decisions: aiData.key_decisions
      };

      try {
        const res = await fetch('api/meetings.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(savePayload)
        });
        const saveRes = await res.json();
        if (saveRes.success) {
          App.showToast('Meeting summary generated and saved!', 'success');
          setTimeout(() => {
            window.location.href = `view_meeting.php?id=${saveRes.data.id}`;
          }, 800);
        }
      } catch (err) {
        App.showToast('Failed to save to database', 'error');
      }
    }
  </script>
</body>
</html>
