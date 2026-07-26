<?php
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI Meeting Minutes Summarizer | Smart Executive Assistant</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

  <!-- Top Navigation Bar -->
  <nav class="navbar">
    <div class="brand-logo">
      <div class="brand-icon">⚡</div>
      <span>Minute<span class="gradient-text">AI</span></span>
    </div>
    <div class="nav-actions">
      <button class="btn btn-outline" id="theme-toggle-btn">🌙 Theme</button>
      <button class="btn btn-primary" onclick="openAuthModal('login')">Sign In</button>
      <button class="btn btn-secondary" onclick="openAuthModal('register')">Register</button>
    </div>
  </nav>

  <!-- Hero Section -->
  <main class="main-content" style="max-width: 1100px; padding-top: 60px;">
    <section style="text-align: center; margin-bottom: 60px;">
      <span class="badge badge-indigo" style="margin-bottom: 16px; padding: 6px 16px;">🚀 AI-Powered Productivity Tool</span>
      <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 20px;">
        Transform Raw Meeting Transcripts into <br>
        <span class="gradient-text">Actionable Executive Minutes</span>
      </h1>
      <p style="font-size: 1.15rem; color: var(--text-secondary); max-width: 700px; margin: 0 auto 32px;">
        Automatically generate executive summaries, key discussion points, sentiment metrics, and track action items with assignees using Google Gemini AI or smart offline NLP.
      </p>
      
      <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
        <a href="dashboard.php" class="btn btn-primary btn-lg">Explore Live Dashboard →</a>
        <a href="new_meeting.php" class="btn btn-secondary btn-lg">🎙 Try Live Voice Transcription</a>
      </div>
    </section>

    <!-- Feature Grid -->
    <section class="meetings-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); margin-bottom: 60px;">
      <div class="glass-card" style="padding: 28px;">
        <div class="metric-icon indigo" style="margin-bottom: 16px;">🎙️</div>
        <h3 style="margin-bottom: 10px;">Live Speech-to-Text</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">
          Record audio directly in your browser using Web Speech API with real-time text transcription.
        </p>
      </div>

      <div class="glass-card" style="padding: 28px;">
        <div class="metric-icon cyan" style="margin-bottom: 16px;">⚡</div>
        <h3 style="margin-bottom: 10px;">Dual-Mode AI Engine</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">
          Connect your Google Gemini API key or use the built-in offline NLP engine for presentation-ready summaries anywhere.
        </p>
      </div>

      <div class="glass-card" style="padding: 28px;">
        <div class="metric-icon emerald" style="margin-bottom: 16px;">✅</div>
        <h3 style="margin-bottom: 10px;">Action Item Tracker</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">
          Automatically identify assigned tasks, due dates, and update progress directly in MySQL.
        </p>
      </div>
    </section>
  </main>

  <!-- Auth Modal -->
  <div class="modal-overlay" id="authModal">
    <div class="modal-container">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 id="modalTitle">Sign In to MinuteAI</h3>
        <button class="btn btn-outline btn-sm" onclick="closeAuthModal()">✕</button>
      </div>

      <!-- Login Form -->
      <form id="loginForm" onsubmit="handleAuthSubmit(event, 'login')">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" id="loginEmail" class="form-control" placeholder="alex@college.edu" value="alex@college.edu" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" id="loginPassword" class="form-control" placeholder="••••••••" value="password123" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
      </form>

      <!-- Register Form -->
      <form id="registerForm" style="display: none;" onsubmit="handleAuthSubmit(event, 'register')">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" id="regName" class="form-control" placeholder="Alex Morgan" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" id="regEmail" class="form-control" placeholder="alex@college.edu" required>
        </div>
        <div class="form-group">
          <label class="form-label">Role / Department</label>
          <input type="text" id="regRole" class="form-control" placeholder="Engineering Lead">
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" id="regPassword" class="form-control" placeholder="At least 6 characters" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
      </form>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    function openAuthModal(type) {
      const modal = document.getElementById('authModal');
      const title = document.getElementById('modalTitle');
      const loginForm = document.getElementById('loginForm');
      const regForm = document.getElementById('registerForm');

      modal.classList.add('active');
      if (type === 'login') {
        title.textContent = 'Sign In to MinuteAI';
        loginForm.style.display = 'block';
        regForm.style.display = 'none';
      } else {
        title.textContent = 'Create New Account';
        loginForm.style.display = 'none';
        regForm.style.display = 'block';
      }
    }

    function closeAuthModal() {
      document.getElementById('authModal').classList.remove('active');
    }

    async function handleAuthSubmit(e, type) {
      e.preventDefault();
      const payload = type === 'login' ? {
        action: 'login',
        email: document.getElementById('loginEmail').value,
        password: document.getElementById('loginPassword').value
      } : {
        action: 'register',
        full_name: document.getElementById('regName').value,
        email: document.getElementById('regEmail').value,
        role: document.getElementById('regRole').value,
        password: document.getElementById('regPassword').value
      };

      try {
        const res = await fetch('api/auth.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          App.showToast(data.message, 'success');
          setTimeout(() => window.location.href = 'dashboard.php', 600);
        } else {
          App.showToast(data.message, 'error');
        }
      } catch(err) {
        App.showToast('Authentication server connection error', 'error');
      }
    }
  </script>
</body>
</html>
