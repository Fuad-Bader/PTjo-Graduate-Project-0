/**
 * PTjo — Shared API client
 * Loaded by every protected page.
 * Provides:
 *  - Session check / auth guard (redirects to login if unauthenticated)
 *  - CSRF token management
 *  - Typed fetch wrappers for every API endpoint
 *  - Global `PTJO` object consumed by dashboard / profile pages
 */

(function (global) {
  'use strict';

  // ── Config ──────────────────────────────────────────────────────────────────
  // Base is relative to the PTjo root; each page sets window.PTJO_ROOT before
  // loading this script, or we auto-detect it from the current URL depth.
  function apiBase() {
    if (global.PTJO_API_BASE) return global.PTJO_API_BASE;
    // Auto: count how deep we are from the root and walk up
    const depth = location.pathname.replace(/\/[^/]*$/, '').split('/').filter(Boolean).length;
    const prefix = depth <= 1 ? '../' : '../../';
    return prefix + 'api/';
  }

  // ── Internal helpers ────────────────────────────────────────────────────────
  let _csrfToken = null;
  let _session   = null;   // { user, profile }

  function getCSRF() { return _csrfToken || ''; }

  async function _fetch(url, options = {}) {
    const defaults = {
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': getCSRF(),
        ...(options.headers || {}),
      },
    };
    const res = await fetch(url, { ...defaults, ...options, headers: { ...defaults.headers, ...(options.headers || {}) } });
    const json = await res.json();
    if (!res.ok && res.status === 401) {
      // Session expired — redirect to login
      window.location.href = PTJO.loginUrl();
      return null;
    }
    return json;
  }

  async function get(endpoint, params = {}) {
    const base = apiBase();
    let url = base + endpoint;
    const qs = new URLSearchParams(params).toString();
    if (qs) url += '?' + qs;
    return _fetch(url, { method: 'GET' });
  }

  async function post(endpoint, body = {}) {
    const base = apiBase();
    return _fetch(base + endpoint, {
      method: 'POST',
      body: JSON.stringify(body),
    });
  }

  // ── PTJO public API ─────────────────────────────────────────────────────────
  const PTJO = {

    // ── Auth ──────────────────────────────────────────────────────────────────

    loginUrl() {
      const depth = location.pathname.replace(/\/[^/]*$/, '').split('/').filter(Boolean).length;
      const prefix = depth <= 1 ? '../' : '../../';
      return prefix + 'LogIn/Login.html';
    },

    /**
     * Call on page load of every protected page.
     * resolvedRole: 'customer' | 'hacker' | 'admin' | null (no guard)
     * Returns { user, profile } or redirects.
     */
    async init(requiredRole = null) {
      try {
        const data = await get('auth/session.php');
        if (!data || !data.ok) {
          window.location.href = this.loginUrl();
          return null;
        }
        _csrfToken = data.csrf;
        _session   = { user: data.user, profile: data.profile };

        if (requiredRole && data.user.role !== requiredRole && data.user.role !== 'admin') {
          // Wrong role — send to correct dashboard
          if (data.user.role === 'hacker') {
            window.location.href = this.loginUrl().replace('LogIn/Login.html', 'Hacker_Dashboard/HackerDashboard.html');
          } else {
            window.location.href = this.loginUrl().replace('LogIn/Login.html', 'Customer_Dashboard/Customer_Dashboard.html');
          }
          return null;
        }
        return _session;
      } catch (e) {
        console.error('[PTJO] Session check failed:', e);
        window.location.href = this.loginUrl();
        return null;
      }
    },

    session() { return _session; },
    csrf()    { return _csrfToken; },

    async logout() {
      await post('auth/logout.php', {});
      window.location.href = this.loginUrl();
    },

    // ── Customer API ──────────────────────────────────────────────────────────

    customer: {
      async getProfile()             { return get('customer/get_profile.php'); },
      async updateProfile(data)      { return post('customer/update_profile.php', data); },
      async getWallet()              { return get('customer/get_wallet.php'); },
      async topupWallet(amount)      { return post('customer/topup_wallet.php', { amount }); },
      async getBounties(status)      { return get('customer/get_bounties.php', status ? { status } : {}); },
      async createBounty(data)       { return post('customer/create_bounty.php', data); },
      async cancelBounty(bountyId)   { return post('customer/cancel_bounty.php', { bounty_id: bountyId }); },
      async getPentesters(params)    { return get('customer/get_pentesters.php', params || {}); },
      async hirePentester(appId)     { return post('customer/hire_pentester.php', { application_id: appId }); },
      async getReports(params)       { return get('customer/get_reports.php', params || {}); },
      async approveReport(data)      { return post('customer/approve_report.php', data); },
      async getNotifications(unreadOnly) {
        return get('customer/get_notifications.php', unreadOnly ? { unread_only: '1' } : {});
      },
      async markNotificationRead(id, all) {
        return post('customer/mark_notification_read.php', all ? { mark_all: true } : { notification_id: id });
      },
      async addPaymentMethod(data)   { return post('customer/add_payment_method.php', data); },
      async deletePaymentMethod(id)  { return post('customer/delete_payment_method.php', { payment_method_id: id }); },
    },

    // ── Hacker API ────────────────────────────────────────────────────────────

    hacker: {
      async getProfile()             { return get('hacker/get_profile.php'); },
      async updateProfile(data)      { return post('hacker/update_profile.php', data); },
      async getWallet()              { return get('hacker/get_wallet.php'); },
      async getBounties(skill)       { return get('hacker/get_bounties.php', skill ? { skill } : {}); },
      async applyBounty(bountyId, note) {
        return post('hacker/apply_bounty.php', { bounty_id: bountyId, availability_note: note || '' });
      },
      async getEngagements(status)   { return get('hacker/get_engagements.php', status ? { status } : {}); },
      async updateEngagementStatus(engId, action, note) {
        return post('hacker/update_engagement_status.php', { engagement_id: engId, action, status_note: note || '' });
      },
      async getReports(params)       { return get('hacker/get_reports.php', params || {}); },
      async submitReport(data)       { return post('hacker/submit_report.php', data); },
      async getNotifications(unreadOnly) {
        return get('hacker/get_notifications.php', unreadOnly ? { unread_only: '1' } : {});
      },
      async markNotificationRead(id, all) {
        return post('hacker/mark_notification_read.php', all ? { mark_all: true } : { notification_id: id });
      },
      async addCertification(data)   { return post('hacker/add_certification.php', data); },
      async deleteCertification(id)  { return post('hacker/delete_certification.php', { certification_id: id }); },
    },

    // ── UI helpers ────────────────────────────────────────────────────────────

    /** Show a non-blocking toast message */
    toast(msg, type = 'info', duration = 3500) {
      let el = document.getElementById('ptjo-toast');
      if (!el) {
        el = document.createElement('div');
        el.id = 'ptjo-toast';
        el.style.cssText = [
          'position:fixed', 'bottom:1.5rem', 'right:1.5rem', 'z-index:9999',
          'padding:.75rem 1.25rem', 'border-radius:.75rem',
          'font-size:.9rem', 'font-weight:600',
          'display:flex', 'align-items:center', 'gap:.6rem',
          'box-shadow:0 8px 30px rgba(0,0,0,.4)',
          'transition:opacity .3s,transform .3s',
          'opacity:0', 'transform:translateY(8px)',
          'pointer-events:none',
        ].join(';');
        document.body.appendChild(el);
      }
      const styles = {
        success : { bg: '#0f766e', color: '#ccfbf1', icon: 'fa-check-circle' },
        error   : { bg: '#7f1d1d', color: '#fca5a5', icon: 'fa-exclamation-circle' },
        info    : { bg: '#1e3a5f', color: '#93c5fd', icon: 'fa-info-circle' },
        warning : { bg: '#78350f', color: '#fde68a', icon: 'fa-exclamation-triangle' },
      };
      const s = styles[type] || styles.info;
      el.style.background = s.bg;
      el.style.color = s.color;
      el.innerHTML = `<i class="fas ${s.icon}"></i> ${msg}`;
      requestAnimationFrame(() => {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
      });
      clearTimeout(el._timer);
      el._timer = setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(8px)';
      }, duration);
    },

    /** Render a simple loading spinner inside a container */
    showLoader(containerId) {
      const el = document.getElementById(containerId);
      if (el) el.innerHTML = '<div class="flex items-center justify-center py-12"><div class="w-8 h-8 border-4 border-teal-500 border-t-transparent rounded-full animate-spin"></div></div>';
    },

    /** Format a number as USD */
    formatUSD(n) {
      return '$' + parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },

    /** Relative time */
    timeAgo(isoStr) {
      if (!isoStr) return 'recently';
      const diff = Date.now() - new Date(isoStr).getTime();
      const m = Math.floor(diff / 60000);
      if (m < 1)  return 'just now';
      if (m < 60) return m + 'm ago';
      const h = Math.floor(m / 60);
      if (h < 24) return h + 'h ago';
      return Math.floor(h / 24) + 'd ago';
    },

    /** Severity badge colour */
    severityClass(label) {
      const map = {
        Critical : 'bg-red-600/20 text-red-400 border-red-600/40',
        High     : 'bg-orange-600/20 text-orange-400 border-orange-600/40',
        Medium   : 'bg-yellow-600/20 text-yellow-400 border-yellow-600/40',
        Low      : 'bg-blue-600/20 text-blue-400 border-blue-600/40',
        Info     : 'bg-gray-600/20 text-gray-400 border-gray-600/40',
      };
      return map[label] || map.Info;
    },

    /** Status badge colour */
    statusClass(status) {
      const map = {
        submitted     : 'bg-blue-600/20 text-blue-400 border-blue-600/40',
        under_review  : 'bg-purple-600/20 text-purple-400 border-purple-600/40',
        edit_requested: 'bg-yellow-600/20 text-yellow-400 border-yellow-600/40',
        approved      : 'bg-teal-600/20 text-teal-400 border-teal-600/40',
        paid          : 'bg-green-600/20 text-green-400 border-green-600/40',
        rejected      : 'bg-red-600/20 text-red-400 border-red-600/40',
        archived      : 'bg-gray-600/20 text-gray-400 border-gray-600/40',
        open          : 'bg-teal-600/20 text-teal-400 border-teal-600/40',
        assigned      : 'bg-blue-600/20 text-blue-400 border-blue-600/40',
        cancelled     : 'bg-red-600/20 text-red-400 border-red-600/40',
        completed     : 'bg-green-600/20 text-green-400 border-green-600/40',
        pending       : 'bg-yellow-600/20 text-yellow-400 border-yellow-600/40',
        in_progress   : 'bg-purple-600/20 text-purple-400 border-purple-600/40',
        accepted      : 'bg-teal-600/20 text-teal-400 border-teal-600/40',
        declined      : 'bg-red-600/20 text-red-400 border-red-600/40',
      };
      return map[status] || 'bg-gray-600/20 text-gray-400 border-gray-600/40';
    },
  };

  global.PTJO = PTJO;

})(window);
