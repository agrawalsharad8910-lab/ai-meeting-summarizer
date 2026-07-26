/* ============================================================
   AI Meeting Minutes Summarizer - Core JS Application Logic
   ============================================================ */

const App = {
  state: {
    user: null,
    meetings: [],
    currentMeeting: null,
    theme: localStorage.getItem('theme') || 'dark'
  },

  init() {
    this.applyTheme(this.state.theme);
    this.checkSession();
    this.bindGlobalEvents();
  },

  // Apply Theme Mode (Dark/Light)
  applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    this.state.theme = theme;
  },

  toggleTheme() {
    const newTheme = this.state.theme === 'dark' ? 'light' : 'dark';
    this.applyTheme(newTheme);
  },

  // Toast Notification System
  showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <div class="toast-icon">
        ${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}
      </div>
      <div class="toast-message">${message}</div>
    `;

    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  },

  // Check user auth session
  async checkSession() {
    try {
      const res = await fetch('api/auth.php?action=me');
      const data = await res.json();
      if (data.success) {
        this.state.user = data.data;
        this.updateUserUI();
      }
    } catch (e) {
      console.log('Session check fallback or offline');
    }
  },

  updateUserUI() {
    const userNames = document.querySelectorAll('.user-name-display');
    const userAvatars = document.querySelectorAll('.user-avatar-display');
    
    if (this.state.user) {
      userNames.forEach(el => el.textContent = this.state.user.full_name);
      userAvatars.forEach(el => el.textContent = this.state.user.full_name.charAt(0).toUpperCase());
    }
  },

  // Global Auth Modal Handler
  bindGlobalEvents() {
    const themeBtn = document.getElementById('theme-toggle-btn');
    if (themeBtn) {
      themeBtn.addEventListener('click', () => this.toggleTheme());
    }

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        await fetch('api/auth.php?action=logout');
        this.showToast('Logged out successfully', 'success');
        setTimeout(() => window.location.href = 'index.php', 800);
      });
    }
  },

  // Helper for formatting dates cleanly
  formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }
};

document.addEventListener('DOMContentLoaded', () => App.init());
