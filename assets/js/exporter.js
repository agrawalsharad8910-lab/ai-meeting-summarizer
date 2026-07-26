/* ============================================================
   AI Meeting Minutes Summarizer - Summary Exporter Utility
   ============================================================ */

const SummaryExporter = {
  exportAsMarkdown(meeting) {
    if (!meeting) return;

    let content = `# ${meeting.title}\n\n`;
    content += `**Department:** ${meeting.department || 'General'}\n`;
    content += `**Date:** ${meeting.meeting_date}\n`;
    content += `**Duration:** ${meeting.duration_minutes} minutes\n`;
    content += `**Sentiment:** ${meeting.sentiment || 'Neutral'}\n\n`;

    content += `## Executive Summary\n${meeting.executive_summary || 'No summary available.'}\n\n`;

    content += `## Key Discussion Points\n`;
    const points = Array.isArray(meeting.key_points) ? meeting.key_points : [];
    points.forEach(pt => {
      content += `- ${pt}\n`;
    });
    content += `\n`;

    content += `## Action Items\n`;
    const actions = Array.isArray(meeting.action_items) ? meeting.action_items : [];
    actions.forEach(item => {
      const statusMark = item.status === 'completed' ? '[x]' : '[ ]';
      content += `- ${statusMark} ${item.task} (Assignee: ${item.assignee || 'Unassigned'}, Due: ${item.due_date || 'N/A'})\n`;
    });
    content += `\n`;

    content += `## Key Decisions\n`;
    const decisions = Array.isArray(meeting.key_decisions) ? meeting.key_decisions : [];
    decisions.forEach(dec => {
      content += `- ${dec}\n`;
    });

    this.downloadFile(content, `${this.sanitizeFilename(meeting.title)}_Summary.md`, 'text/markdown');
  },

  exportAsText(meeting) {
    if (!meeting) return;

    let content = `MEETING MINUTES & AI SUMMARY\n`;
    content += `==========================================\n`;
    content += `Title: ${meeting.title}\n`;
    content += `Department: ${meeting.department || 'General'}\n`;
    content += `Date: ${meeting.meeting_date}\n`;
    content += `Duration: ${meeting.duration_minutes} minutes\n`;
    content += `Sentiment: ${meeting.sentiment || 'Neutral'}\n`;
    content += `==========================================\n\n`;

    content += `EXECUTIVE SUMMARY:\n${meeting.executive_summary || 'N/A'}\n\n`;

    content += `KEY DISCUSSION POINTS:\n`;
    const points = Array.isArray(meeting.key_points) ? meeting.key_points : [];
    points.forEach((pt, i) => content += `${i + 1}. ${pt}\n`);
    content += `\n`;

    content += `ACTION ITEMS:\n`;
    const actions = Array.isArray(meeting.action_items) ? meeting.action_items : [];
    actions.forEach((item, i) => {
      const status = item.status === 'completed' ? '[COMPLETED]' : '[PENDING]';
      content += `${i + 1}. ${status} ${item.task} | Assignee: ${item.assignee} | Due: ${item.due_date}\n`;
    });
    content += `\n`;

    content += `KEY DECISIONS MADE:\n`;
    const decisions = Array.isArray(meeting.key_decisions) ? meeting.key_decisions : [];
    decisions.forEach((dec, i) => content += `${i + 1}. ${dec}\n`);

    this.downloadFile(content, `${this.sanitizeFilename(meeting.title)}_Minutes.txt`, 'text/plain');
  },

  printSummary(meeting) {
    if (!meeting) return;

    const printWin = window.open('', '_blank', 'width=800,height=900');
    printWin.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Print - ${meeting.title}</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 40px; color: #333; line-height: 1.6; }
          h1 { border-bottom: 2px solid #6366f1; padding-bottom: 10px; color: #1e293b; }
          .meta { background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9em; }
          h2 { color: #4338ca; margin-top: 25px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
          ul { padding-left: 20px; }
          li { margin-bottom: 8px; }
          .completed { text-decoration: line-through; color: #64748b; }
        </style>
      </head>
      <body>
        <h1>${meeting.title}</h1>
        <div class="meta">
          <strong>Department:</strong> ${meeting.department} | 
          <strong>Date:</strong> ${meeting.meeting_date} | 
          <strong>Duration:</strong> ${meeting.duration_minutes} mins | 
          <strong>Sentiment:</strong> ${meeting.sentiment}
        </div>
        
        <h2>Executive Summary</h2>
        <p>${meeting.executive_summary}</p>
        
        <h2>Key Discussion Points</h2>
        <ul>
          ${(meeting.key_points || []).map(pt => `<li>${pt}</li>`).join('')}
        </ul>
        
        <h2>Action Items</h2>
        <ul>
          ${(meeting.action_items || []).map(item => `
            <li class="${item.status}">
              [${item.status === 'completed' ? '✓ DONE' : 'PENDING'}] <strong>${item.task}</strong> - <em>Assignee: ${item.assignee} (Due: ${item.due_date})</em>
            </li>
          `).join('')}
        </ul>
        
        <h2>Key Decisions</h2>
        <ul>
          ${(meeting.key_decisions || []).map(dec => `<li>${dec}</li>`).join('')}
        </ul>
        
        <script>
          window.onload = function() { window.print(); }
        </script>
      </body>
      </html>
    `);
    printWin.document.close();
  },

  downloadFile(content, filename, contentType) {
    const a = document.createElement('a');
    const blob = new Blob([content], { type: contentType });
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    a.click();
    URL.revokeObjectURL(a.href);
    App.showToast(`Exported as ${filename}`, 'success');
  },

  sanitizeFilename(name) {
    return (name || 'Meeting').replace(/[^a-z0-9]/gi, '_').toLowerCase();
  }
};
