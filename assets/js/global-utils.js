/**
 * TS SERVICE - Global JavaScript Utilities
 * Utility functions per tutto il gestionale
 * Versione: 2.0
 */

const TSService = {
  /* ================================
     TOAST NOTIFICATIONS
     ================================ */
  toast: {
    container: null,
    
    init() {
      if (!this.container) {
        this.container = document.createElement('div');
        this.container.className = 'ts-toast-container';
        document.body.appendChild(this.container);
      }
    },
    
    show(message, type = 'success', duration = 4000) {
      this.init();
      
      const icons = {
        success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
        error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
      };
      
      const toast = document.createElement('div');
      toast.className = `ts-toast ts-toast-${type}`;
      toast.innerHTML = `
        <span class="ts-toast-icon">${icons[type] || icons.info}</span>
        <span class="ts-toast-message">${message}</span>
      `;
      
      this.container.appendChild(toast);
      
      // Trigger animation
      requestAnimationFrame(() => {
        toast.classList.add('show');
      });
      
      // Auto dismiss
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
      }, duration);
      
      return toast;
    },
    
    success(message, duration) {
      return this.show(message, 'success', duration);
    },
    
    error(message, duration) {
      return this.show(message, 'error', duration);
    },
    
    warning(message, duration) {
      return this.show(message, 'warning', duration);
    },
    
    info(message, duration) {
      return this.show(message, 'info', duration);
    }
  },
  
  /* ================================
     MODAL DIALOGS
     ================================ */
  modal: {
    create(options = {}) {
      const {
        title = '',
        content = '',
        size = 'default', // 'small', 'default', 'large'
        buttons = [],
        closable = true,
        onClose = null
      } = options;
      
      const overlay = document.createElement('div');
      overlay.className = 'ts-modal-overlay';
      
      const sizeClass = size === 'small' ? 'max-width: 400px' : 
                       size === 'large' ? 'max-width: 700px' : '';
      
      overlay.innerHTML = `
        <div class="ts-modal" style="${sizeClass}">
          <div class="ts-modal-header">
            <h3>${title}</h3>
            ${closable ? `<button class="ts-modal-close" data-action="close">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>` : ''}
          </div>
          <div class="ts-modal-body">${content}</div>
          ${buttons.length ? `
            <div class="ts-modal-footer">
              ${buttons.map(btn => `
                <button class="ts-btn ${btn.class || 'ts-btn-secondary'}" data-action="${btn.action || 'close'}">
                  ${btn.text}
                </button>
              `).join('')}
            </div>
          ` : ''}
        </div>
      `;
      
      document.body.appendChild(overlay);
      
      // Event handlers
      overlay.addEventListener('click', (e) => {
        const action = e.target.closest('[data-action]')?.dataset.action;
        
        if (action === 'close' || (closable && e.target === overlay)) {
          this.close(overlay);
          if (onClose) onClose();
        }
        
        // Custom actions
        const btn = buttons.find(b => b.action === action);
        if (btn && btn.onClick) {
          btn.onClick(overlay);
        }
      });
      
      // ESC key
      const escHandler = (e) => {
        if (e.key === 'Escape' && closable) {
          this.close(overlay);
          if (onClose) onClose();
          document.removeEventListener('keydown', escHandler);
        }
      };
      document.addEventListener('keydown', escHandler);
      
      // Show modal
      requestAnimationFrame(() => {
        overlay.classList.add('active');
      });
      
      return overlay;
    },
    
    close(modal) {
      modal.classList.remove('active');
      setTimeout(() => modal.remove(), 300);
    },
    
    confirm(message, options = {}) {
      return new Promise((resolve) => {
        this.create({
          title: options.title || 'Conferma',
          content: `<p style="color: var(--ts-text-secondary); margin: 0;">${message}</p>`,
          size: 'small',
          closable: true,
          onClose: () => resolve(false),
          buttons: [
            {
              text: options.cancelText || 'Annulla',
              class: 'ts-btn-secondary',
              action: 'cancel',
              onClick: (modal) => {
                this.close(modal);
                resolve(false);
              }
            },
            {
              text: options.confirmText || 'Conferma',
              class: options.danger ? 'ts-btn-danger' : 'ts-btn-primary',
              action: 'confirm',
              onClick: (modal) => {
                this.close(modal);
                resolve(true);
              }
            }
          ]
        });
      });
    },
    
    alert(message, options = {}) {
      return new Promise((resolve) => {
        this.create({
          title: options.title || 'Avviso',
          content: `<p style="color: var(--ts-text-secondary); margin: 0;">${message}</p>`,
          size: 'small',
          closable: true,
          onClose: () => resolve(),
          buttons: [
            {
              text: 'OK',
              class: 'ts-btn-primary',
              action: 'ok',
              onClick: (modal) => {
                this.close(modal);
                resolve();
              }
            }
          ]
        });
      });
    }
  },
  
  /* ================================
     LOADING STATES
     ================================ */
  loader: {
    overlay: null,
    
    show(message = 'Caricamento...') {
      if (!this.overlay) {
        this.overlay = document.createElement('div');
        this.overlay.className = 'ts-page-loader';
        this.overlay.innerHTML = `
          <div class="ts-spinner" style="width: 50px; height: 50px;"></div>
          <p style="color: var(--ts-text-secondary); font-weight: 500;" class="loader-message">${message}</p>
        `;
        document.body.appendChild(this.overlay);
      } else {
        this.overlay.querySelector('.loader-message').textContent = message;
      }
      
      requestAnimationFrame(() => {
        this.overlay.classList.add('active');
      });
    },
    
    hide() {
      if (this.overlay) {
        this.overlay.classList.remove('active');
      }
    },
    
    // Inline button loading
    button(btn, loading = true) {
      if (loading) {
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = `<span class="ts-spinner ts-spinner-sm" style="border-top-color: currentColor;"></span>`;
        btn.disabled = true;
      } else {
        btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
        btn.disabled = false;
      }
    }
  },
  
  /* ================================
     FORM UTILITIES
     ================================ */
  form: {
    serialize(form) {
      const formData = new FormData(form);
      const data = {};
      for (const [key, value] of formData.entries()) {
        data[key] = value;
      }
      return data;
    },
    
    validate(form) {
      let isValid = true;
      const inputs = form.querySelectorAll('[required]');
      
      inputs.forEach(input => {
        input.classList.remove('error');
        if (!input.value.trim()) {
          input.classList.add('error');
          isValid = false;
        }
      });
      
      return isValid;
    },
    
    reset(form) {
      form.reset();
      form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    }
  },
  
  /* ================================
     AJAX HELPERS
     ================================ */
  async fetch(url, options = {}) {
    const defaultOptions = {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    };
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    if (mergedOptions.body && typeof mergedOptions.body === 'object') {
      if (mergedOptions.body instanceof FormData) {
        delete mergedOptions.headers['Content-Type'];
      } else {
        mergedOptions.body = JSON.stringify(mergedOptions.body);
      }
    }
    
    try {
      const response = await fetch(url, mergedOptions);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        return await response.json();
      }
      
      return await response.text();
    } catch (error) {
      console.error('Fetch error:', error);
      throw error;
    }
  },
  
  /* ================================
     UTILITY FUNCTIONS
     ================================ */
  utils: {
    // Debounce function
    debounce(func, wait = 300) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    },
    
    // Format currency
    formatCurrency(amount, currency = 'EUR') {
      return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: currency
      }).format(amount);
    },
    
    // Format date
    formatDate(date, options = {}) {
      const defaultOptions = {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      };
      return new Date(date).toLocaleDateString('it-IT', { ...defaultOptions, ...options });
    },
    
    // Format date with time
    formatDateTime(date) {
      return new Date(date).toLocaleString('it-IT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    
    // Copy to clipboard
    async copyToClipboard(text) {
      try {
        await navigator.clipboard.writeText(text);
        TSService.toast.success('Copiato negli appunti!');
        return true;
      } catch (err) {
        TSService.toast.error('Impossibile copiare');
        return false;
      }
    },
    
    // Scroll to element
    scrollTo(element, offset = 100) {
      const top = element.getBoundingClientRect().top + window.scrollY - offset;
      window.scrollTo({ top, behavior: 'smooth' });
    },
    
    // Generate unique ID
    generateId(prefix = 'ts') {
      return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
  },
  
  /* ================================
     TABLE UTILITIES
     ================================ */
  table: {
    // Add sorting to table headers
    enableSort(table) {
      const headers = table.querySelectorAll('th[data-sortable]');
      
      headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
          const column = header.dataset.column;
          const order = header.dataset.order === 'asc' ? 'desc' : 'asc';
          
          // Reset all headers
          headers.forEach(h => h.dataset.order = '');
          header.dataset.order = order;
          
          this.sort(table, column, order);
        });
      });
    },
    
    sort(table, column, order) {
      const tbody = table.querySelector('tbody');
      const rows = Array.from(tbody.querySelectorAll('tr'));
      
      rows.sort((a, b) => {
        const aVal = a.querySelector(`[data-column="${column}"]`)?.textContent || a.cells[column]?.textContent || '';
        const bVal = b.querySelector(`[data-column="${column}"]`)?.textContent || b.cells[column]?.textContent || '';
        
        const comparison = aVal.localeCompare(bVal, 'it', { numeric: true });
        return order === 'asc' ? comparison : -comparison;
      });
      
      rows.forEach(row => tbody.appendChild(row));
    },
    
    // Filter table rows
    filter(table, searchTerm) {
      const rows = table.querySelectorAll('tbody tr');
      const term = searchTerm.toLowerCase();
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
      });
    }
  },
  
  /* ================================
     ANIMATIONS
     ================================ */
  animate: {
    fadeIn(element, duration = 400) {
      element.style.opacity = 0;
      element.style.display = '';
      
      let start = null;
      const step = (timestamp) => {
        if (!start) start = timestamp;
        const progress = Math.min((timestamp - start) / duration, 1);
        element.style.opacity = progress;
        
        if (progress < 1) {
          window.requestAnimationFrame(step);
        }
      };
      
      window.requestAnimationFrame(step);
    },
    
    fadeOut(element, duration = 400) {
      let start = null;
      const step = (timestamp) => {
        if (!start) start = timestamp;
        const progress = Math.min((timestamp - start) / duration, 1);
        element.style.opacity = 1 - progress;
        
        if (progress < 1) {
          window.requestAnimationFrame(step);
        } else {
          element.style.display = 'none';
        }
      };
      
      window.requestAnimationFrame(step);
    },
    
    staggerIn(elements, delay = 100) {
      elements.forEach((el, i) => {
        el.style.opacity = 0;
        el.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
          el.style.transition = 'all 0.4s ease-out';
          el.style.opacity = 1;
          el.style.transform = 'translateY(0)';
        }, i * delay);
      });
    }
  },
  
  /* ================================
     INITIALIZATION
     ================================ */
  init() {
    // Add smooth scrolling
    document.documentElement.style.scrollBehavior = 'smooth';
    
    // Initialize toast container
    this.toast.init();
    
    // Auto-hide alerts after delay
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
      const delay = parseInt(alert.dataset.autoDismiss) || 5000;
      setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 300);
      }, delay);
    });
    
    // Add ripple effect to buttons
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.ts-btn');
      if (btn) {
        const rect = btn.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const ripple = document.createElement('span');
        ripple.style.cssText = `
          position: absolute;
          border-radius: 50%;
          background: rgba(255,255,255,0.3);
          width: 100px;
          height: 100px;
          left: ${x - 50}px;
          top: ${y - 50}px;
          transform: scale(0);
          animation: ripple 0.6s ease-out;
          pointer-events: none;
        `;
        
        btn.style.position = 'relative';
        btn.style.overflow = 'hidden';
        btn.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
      }
    });
    
    // Add CSS for ripple animation
    if (!document.getElementById('ts-ripple-style')) {
      const style = document.createElement('style');
      style.id = 'ts-ripple-style';
      style.textContent = `
        @keyframes ripple {
          to {
            transform: scale(4);
            opacity: 0;
          }
        }
      `;
      document.head.appendChild(style);
    }
    
    console.log('🚀 TS Service utilities initialized');
  }
};

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => TSService.init());
} else {
  TSService.init();
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
  module.exports = TSService;
}
