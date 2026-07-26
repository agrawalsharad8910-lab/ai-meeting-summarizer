<?php
require_once __DIR__ . '/config/db.php';
$meetingId = intval($_GET['id'] ?? 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Meeting Summary | MinuteAI</title>
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
      <a href="new_meeting.php" class="nav-link">➕ New Meeting</a>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      
      <!-- Top Action Header -->
      <div class="page-header">
        <div>
          <span class="badge badge-indigo" id="viewDept">Engineering</span>
          <span class="badge badge-emerald" id="viewSentiment">Productive</span>
          <h2 id="viewTitle" style="margin-top: 8px;">Meeting Minutes Details</h2>
          <p style="color: var(--text-secondary); font-size: 0.85rem;" id="viewMeta">Loading...</p>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
          <button class="btn btn-outline btn-sm" onclick="exportMD()">📄 Markdown</button>
          <button class="btn btn-outline btn-sm" onclick="exportTXT()">📝 Text</button>
          <button class="btn btn-primary btn-sm" onclick="printDoc()">🖨️ Print / PDF</button>
          <button class="btn btn-danger btn-sm" onclick="deleteCurrentMeeting()">🗑️ Delete</button>
        </div>
      </div>

      <!-- Executive Summary Highlight Box -->
      <div class="glass-card" style="padding: 24px; margin-bottom: 24px; border-left: 4px solid var(--primary);">
        <h4 style="margin-bottom: 8px; color: var(--primary);">📌 Executive Summary</h4>
        <p id="viewSummary" style="font-size: 1.05rem; line-height: 1.7; color: var(--text-primary);">
          Loading summary...
        </p>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        
        <!-- Left: Action Items & Checklist -->
        <div class="glass-card" style="padding: 24px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3>✅ Action Items & Deliverables</h3>
            <button class="btn btn-outline btn-sm" onclick="openAddActionModal()">+ Add Task</button>
          </div>
          
          <div id="actionItemsList" class="checklist-group">
            <!-- Action items loaded via JS -->
          </div>
        </div>

        <!-- Right: Key Discussion Points & Decisions -->
        <div class="glass-card" style="padding: 24px;">
          <h3 style="margin-bottom: 16px;">💡 Key Discussion Points</h3>
          <ul id="keyPointsList" style="padding-left: 20px; color: var(--text-secondary); margin-bottom: 28px; line-height: 1.8;">
            <!-- Key points loaded via JS -->
          </ul>

          <h3 style="margin-bottom: 16px;">🎯 Key Decisions Made</h3>
          <ul id="keyDecisionsList" style="padding-left: 20px; color: var(--text-secondary); line-height: 1.8;">
            <!-- Key decisions loaded via JS -->
          </ul>
        </div>
      </div>

      <!-- Raw Transcript Expandable Box -->
      <div class="glass-card" style="padding: 24px; margin-top: 24px;">
        <h4 style="margin-bottom: 12px;">📜 Full Raw Meeting Transcript</h4>
        <pre id="viewTranscript" style="white-space: pre-wrap; font-family: monospace; background: var(--bg-secondary); padding: 16px; border-radius: var(--radius-sm); color: var(--text-secondary); max-height: 300px; overflow-y: auto;"></pre>
      </div>

    </main>
  </div>

  <!-- Add Action Item Modal -->
  <div class="modal-overlay" id="actionModal">
    <div class="modal-container">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3>Add New Action Item</h3>
        <button class="btn btn-outline btn-sm" onclick="closeAddActionModal()">✕</button>
      </div>

      <form onsubmit="handleSaveActionItem(event)">
        <div class="form-group">
          <label class="form-label">Task Description</label>
          <input type="text" id="newTask" class="form-control" placeholder="e.g. Prepare API documentation" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group">
            <label class="form-label">Assignee</label>
            <input type="text" id="newAssignee" class="form-control" placeholder="Sarah" required>
          </div>

          <div class="form-group">
            <label class="form-label">Due Date</label>
            <input type="date" id="newDueDate" class="form-control" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Save Action Item</button>
      </form>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script src="assets/js/exporter.js"></script>
  <script>
    const meetingId = <?php echo $meetingId; ?>;
    let currentMeetingData = null;

    document.addEventListener('DOMContentLoaded', () => {
      loadMeetingDetail();
    });

    async function loadMeetingDetail() {
      try {
        const res = await fetch(`api/meetings.php?action=get&id=${meetingId}`);
        const data = await res.json();

        if (data.success) {
          currentMeetingData = data.data;
          renderMeetingDetail(currentMeetingData);
        } else {
          App.showToast(data.message, 'error');
        }
      } catch (err) {
        console.error(err);
      }
    }

    function renderMeetingDetail(m) {
      document.getElementById('viewTitle').textContent = m.title;
      document.getElementById('viewDept').textContent = m.department || 'General';
      document.getElementById('viewSentiment').textContent = m.sentiment || 'Productive';
      document.getElementById('viewMeta').textContent = `📅 Date: ${m.meeting_date} | ⏱️ Duration: ${m.duration_minutes || 30} mins | 📝 Words: ${m.word_count || 150}`;
      document.getElementById('viewSummary').textContent = m.executive_summary || 'No summary available.';
      document.getElementById('viewTranscript').textContent = m.raw_transcript || 'No transcript text available.';

      // Render Key Points
      const kpList = document.getElementById('keyPointsList');
      const points = Array.isArray(m.key_points) ? m.key_points : [];
      kpList.innerHTML = points.map(pt => `<li>${escapeHtml(pt)}</li>`).join('');

      // Render Key Decisions
      const kdList = document.getElementById('keyDecisionsList');
      const decisions = Array.isArray(m.key_decisions) ? m.key_decisions : [];
      kdList.innerHTML = decisions.map(dec => `<li>${escapeHtml(dec)}</li>`).join('');

      // Render Action Items Checklist
      renderActionItems(m.action_items || []);
    }

    function renderActionItems(items) {
      const container = document.getElementById('actionItemsList');
      if (items.length === 0) {
        container.innerHTML = `<p style="color: var(--text-muted); font-size: 0.9rem;">No action items assigned for this meeting.</p>`;
        return;
      }

      container.innerHTML = items.map(item => `
        <div class="checklist-item ${item.status === 'completed' ? 'completed' : ''}">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="checkbox-custom" onclick="toggleTaskStatus(${item.id}, '${item.status}')">
              ${item.status === 'completed' ? '✓' : ''}
            </div>
            <div>
              <span class="task-text">${escapeHtml(item.task)}</span>
              <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                👤 ${escapeHtml(item.assignee || 'Unassigned')} | 📅 Due: ${item.due_date || 'N/A'}
              </div>
            </div>
          </div>

          <button class="btn btn-outline btn-sm" onclick="deleteTask(${item.id})" style="padding: 4px 8px; font-size: 0.75rem;">✕</button>
        </div>
      `).join('');
    }

    async function toggleTaskStatus(id, currentStatus) {
      const newStatus = (currentStatus === 'completed') ? 'pending' : 'completed';
      try {
        const res = await fetch('api/action_items.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'toggle', id: id, status: newStatus })
        });
        const data = await res.json();
        if (data.success) {
          loadMeetingDetail(); // reload checklist
        }
      } catch (e) {
        App.showToast('Failed to update task', 'error');
      }
    }

    function openAddActionModal() {
      document.getElementById('actionModal').classList.add('active');
    }

    function closeAddActionModal() {
      document.getElementById('actionModal').classList.remove('active');
    }

    async function handleSaveActionItem(e) {
      e.preventDefault();
      const payload = {
        action: 'add',
        meeting_id: meetingId,
        task: document.getElementById('newTask').value,
        assignee: document.getElementById('newAssignee').value,
        due_date: document.getElementById('newDueDate').value
      };

      try {
        const res = await fetch('api/action_items.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          closeAddActionModal();
          App.showToast('Action item added!', 'success');
          loadMeetingDetail();
        }
      } catch (err) {
        App.showToast('Failed to add action item', 'error');
      }
    }

    async function deleteTask(id) {
      try {
        await fetch(`api/action_items.php?action=delete&id=${id}`);
        App.showToast('Item deleted', 'info');
        loadMeetingDetail();
      } catch (e) {}
    }

    async function deleteCurrentMeeting() {
      if (confirm('Are you sure you want to delete this meeting summary?')) {
        await fetch(`api/meetings.php?action=delete&id=${meetingId}`);
        App.showToast('Meeting deleted', 'success');
        setTimeout(() => window.location.href = 'dashboard.php', 600);
      }
    }

    function exportMD() { SummaryExporter.exportAsMarkdown(currentMeetingData); }
    function exportTXT() { SummaryExporter.exportAsText(currentMeetingData); }
    function printDoc() { SummaryExporter.printSummary(currentMeetingData); }

    function escapeHtml(text) {
      if (!text) return '';
      return text.replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
      });
    }
  </script>
</body>
</html>
