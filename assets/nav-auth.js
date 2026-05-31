/**
 * nav-auth.js — Non-redirecting session check for public/settings pages.
 * Handles both old-style navs (#nav-hacker-btn, #nav-customer-btn) and
 * new-style navs (#nav-login-btn, #nav-dashboard-btn, #nav-profile-btn, #nav-logout-btn).
 */
(function () {
    'use strict';

    function _apiBase() {
        var parts = location.pathname.replace(/\/[^/]*$/, '').split('/').filter(Boolean);
        return parts.length === 0 ? 'api/' : (parts.length === 1 ? '../api/' : '../../api/');
    }

    function _siteRoot() {
        var parts = location.pathname.replace(/\/[^/]*$/, '').split('/').filter(Boolean);
        return parts.length === 0 ? '' : (parts.length === 1 ? '../' : '../../');
    }

    window.ptjoNavLogout = function () {
        fetch(_apiBase() + 'auth/logout.php', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window._ptjoNavCSRF || '' },
            body: '{}'
        }).finally(function () { window.location.reload(); });
    };

    /** Call this from protected pages after PTJO.init() resolves. */
    window.ptjoUpdateNav = function (session) {
        if (!session || !session.user) return;
        window._ptjoNavCSRF = window.PTJO ? PTJO.csrf() : (session.csrf || '');
        var role = session.user.role;
        var root = _siteRoot();

        /* Hide legacy dual-login buttons (public pages still using them) */
        ['nav-hacker-btn', 'nav-customer-btn', 'nav-login-btn'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        });

        /* Show / wire profile button */
        var pBtn = document.getElementById('nav-profile-btn');
        if (pBtn) {
            pBtn.href = role === 'hacker'
                ? root + 'My_ProfileHacker/MyProfile.html'
                : (role === 'admin' ? root + 'AD_Page/AD.html' : root + 'My_ProfileCustomer/MyProfileC.html');
            pBtn.classList.remove('hidden');
        }

        /* Show / wire dashboard button */
        var dBtn = document.getElementById('nav-dashboard-btn');
        if (dBtn) {
            dBtn.href = role === 'hacker'
                ? root + 'Hacker_Dashboard/HackerDashboard.html'
                : root + 'Customer_Dashboard/Customer_Dashboard.html';
            dBtn.classList.remove('hidden');
        }

        /* Show logout button */
        var lBtn = document.getElementById('nav-logout-btn');
        if (lBtn) lBtn.classList.remove('hidden');
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (window._ptjoNavInitDone) return;

        fetch(_apiBase() + 'auth/session.php', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) return;
                window._ptjoNavCSRF = data.csrf;
                window.ptjoUpdateNav(data);
                document.dispatchEvent(new CustomEvent('ptjo:session', { detail: data }));
            })
            .catch(function () {});
    });
})();
