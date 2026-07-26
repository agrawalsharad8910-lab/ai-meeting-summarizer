<?php
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | AI Meeting Minutes Summarizer</title>
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
      <div class="user-profile">
        <div class="avatar user-avatar-display">A</div>
        <span class="user-name-display" style="font-size: 0.9rem; font-weight: 600;">Alex Morgan</span>
      </div>
      <a href="#" id="logout-btn" class="btn btn-outline btn-sm">Logout</a>
    </div>
  </nav>

  <div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <a href="dashboard.php" class="nav-link active">📊 Dashboard</a>
      <a href="new_meeting.php" class="nav-link">➕ New Meeting</a>
      <a href="index.php" class="nav-link">🏠 Home Showcase</a>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="page-header">
        <div>
          <h2>Meeting Summaries Overview</h2>
          <p style="color: var(--text-secondary); font-size: 0.9rem;">Manage, search, and review your AI-generated meeting minutes.</p>
        </div>
        <a href="new_meeting.php" class="btn btn-primary">➕ Summarize New Meeting</a>
      </div>

      <!-- Metrics Cards Grid -->
      <div class="metrics-grid">
        <div class="glass-card metric-card">
          <div class="metric-icon indigo">📝</div>
          <div>
            <div class="metric-val" id="metric-total-meetings">0</div>
            <div class="metric-lbl">Total Meetings Recorded</div>
          </div>
        </div>

        <div class="glass-card metric-card">
          <div class="metric-icon cyan">⏱️</div>
          <div>
            <div class="metric-val" id="metric-total-hours">0h</div>
            <div class="metric-lbl">Meeting Time Processed</div>
          </div>
        </div>

        <div class="glass-card metric-card">
          <div class="metric-icon emerald">✅</div>
          <div>
            <div class="metric-val" id="metric-completed-actions">0</div>
            <div class="metric-lbl">Action Items Completed</div>
          </div>
        </div>

        <div class="glass-card metric-card">
          <div class="metric-icon amber">🎯</div>
          <div>
            <div class="metric-val" id="metric-pending-actions">0</div>
            <div class="metric-lbl">Action Items Pending</div>
          </div>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="search-filter-bar">
        <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search meetings by title, topic, or department..." style="max-width: 380px;" onkeyup="filterMeetings()">
        
        <select id="deptFilter" class="form-control" style="max-width: 220px;" onchange="filterMeetings()">
          <option value="all">All Departments</option>
          <option value="Engineering">Engineering</option>
          <option value="Product Management">Product Management</option>
          <option value="Sales & Marketing">Sales & Marketing</option>
          <option value="General">General</option>
        </select>
      </div>

      <!-- Meetings List Container -->
      <div class="content-section">
        <div id="meetingsGrid" class="meetings-grid">
          <!-- Rendered via JavaScript -->
          <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-muted);">
            Loading meeting summaries...
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    let allMeetings = [];

    document.addEventListener('DOMContentLoaded', () => {
      fetchMeetings();
    });

    async function fetchMeetings() {
      try {
        const res = await fetch('api/meetings.php?action=list');
        const data = await res.json();
        if (data.success) {
          allMeetings = data.data;
          renderMetrics(allMeetings);
          renderMeetingsList(allMeetings);
        }
      } catch (err) {
        console.error('Failed to load meetings:', err);
      }
    }

    function renderMetrics(meetings) {
      let totalTime = 0;
      let completedActions = 0;
      let totalActions = 0;

      meetings.forEach(m => {
        totalTime += parseInt(m.duration_minutes || 0);
        completedActions += parseInt(m.completed_action_items || 0);
        totalActions += parseInt(m.total_action_items || 0);
      });

      document.getElementById('metric-total-meetings').textContent = meetings.length;
      document.getElementById('metric-total-hours').textContent = (totalTime / 60).toFixed(1) + 'h';
      document.getElementById('metric-completed-actions').textContent = completedActions;
      document.getElementById('metric-pending-actions').textContent = (totalActions - completedActions);
    }

    function renderMeetingsList(meetings) {
      const grid = document.getElementById('meetingsGrid');
      if (meetings.length === 0) {
        grid.innerHTML = `
          <div style="grid-column: 1 / -1; text-align: center; padding: 60px;" class="glass-card">
            <h3>No meeting summaries found</h3>
            <p style="color: var(--text-secondary); margin: 12px 0 20px;">Start by transcribing your first meeting.</p>
            <a href="new_meeting.php" class="btn btn-primary">+ Create New Summary</a>
          </div>
        `;
        return;
      }

      grid.innerHTML = meetings.map(m => `
        <div class="glass-card meeting-card">
          <div>
            <div class="meeting-card-header">
              <span class="badge badge-indigo">${m.department || 'General'}</span>
              <span class="badge badge-emerald">${m.sentiment || 'Productive'}</span>
            </div>
            
            <h3 class="meeting-title">${escapeHtml(m.title)}</h3>
            <div class="meeting-meta">
              <span>📅 ${App.formatDate(m.meeting_date)}</span>
              <span>⏱️ ${m.duration_minutes || 30} mins</span>
            </div>

            <p class="meeting-summary-preview">${escapeHtml(m.executive_summary || 'Click view details to read full summary.')}</p>
          </div>

          <div class="meeting-card-footer">
            <span style="font-size: 0.8rem; color: var(--text-muted);">
              ✅ ${m.completed_action_items || 0}/${m.total_action_items || 0} Tasks
            </span>
            <a href="view_meeting.php?id=${m.id}" class="btn btn-outline btn-sm">View Details →</a>
          </div>
        </div>
      `).join('');
    }

    function filterMeetings() {
      const search = document.getElementById('searchInput').value.toLowerCase();
      const dept = document.getElementById('deptFilter').value;

      const filtered = allMeetings.filter(m => {
        const matchesSearch = m.title.toLowerCase().includes(search) || 
                              (m.executive_summary && m.executive_summary.toLowerCase().includes(search));
        const matchesDept = dept === 'all' || m.department === dept;
        return matchesSearch && matchesDept;
      });

      renderMeetingsList(filtered);
    }

    function escapeHtml(text) {
      if (!text) return '';
      return text.replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
      });
    }
  </script>
</body>
</html>
