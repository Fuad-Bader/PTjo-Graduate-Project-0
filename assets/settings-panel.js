/**
 * PTjo – Navbar Settings Dropdown
 * Upgrades the language toggle button in every navbar into a
 * settings gear button with a clean dropdown list.
 * Works on every page — no HTML changes required.
 */
(function () {
    'use strict';

    // ── Root URL (same logic as before) ────────────────────────────────────
    var _script = document.currentScript;
    function getPrefix() {
        var src  = (_script || {}).src || '';
        var page = window.location.href;
        var m    = src.match(/^(.*?)assets\/settings-panel\.js/i);
        if (!m) return '../';
        var root    = m[1];
        var pageDir = page.substring(0, page.lastIndexOf('/') + 1);
        var rel     = pageDir.startsWith(root) ? pageDir.slice(root.length) : '';
        var depth   = rel.split('/').filter(Boolean).length;
        return depth > 0 ? '../'.repeat(depth) : '';
    }
    var P = getPrefix();

    // ── Detect current page ─────────────────────────────────────────────────
    var curPath = window.location.pathname.toLowerCase().replace(/\\/g, '/');
    function isPage(seg) { return curPath.indexOf(seg.toLowerCase()) !== -1; }

    // ── CSS ─────────────────────────────────────────────────────────────────
    var CSS = '\
#sp-wrap { position: relative; display: inline-flex; align-items: center; }\
\
#sp-btn {\
    display: inline-flex; align-items: center; gap: 6px;\
    padding: 6px 12px;\
    background: rgba(55,65,81,0.6);\
    border: 1px solid rgba(75,85,99,0.5);\
    border-radius: 8px;\
    color: #d1d5db;\
    font-size: 0.8rem; font-weight: 600;\
    cursor: pointer;\
    transition: background 0.2s, color 0.2s;\
    white-space: nowrap;\
    outline: none;\
}\
#sp-btn:hover { background: #374151; color: #fff; }\
#sp-btn.sp-open { background: #0d9488; color: #fff; border-color: #0d9488; }\
#sp-btn i { font-size: 0.85rem; transition: transform 0.4s; }\
#sp-btn.sp-open i { transform: rotate(90deg); }\
\
#sp-drop {\
    display: none;\
    position: absolute;\
    top: calc(100% + 10px);\
    right: 0;\
    min-width: 230px;\
    background: #1f2937;\
    border: 1px solid #374151;\
    border-radius: 12px;\
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);\
    z-index: 99999;\
    overflow: hidden;\
    animation: sp-fade-in 0.2s ease;\
}\
#sp-drop.sp-visible { display: block; }\
@keyframes sp-fade-in {\
    from { opacity: 0; transform: translateY(-6px); }\
    to   { opacity: 1; transform: translateY(0); }\
}\
\
.sp-drop-hdr {\
    display: flex; align-items: center; gap: 8px;\
    padding: 12px 16px;\
    background: linear-gradient(135deg, #0d9488, #0f766e);\
    font-size: 0.8rem; font-weight: 700; color: #fff;\
    text-transform: uppercase; letter-spacing: 0.07em;\
}\
.sp-drop-hdr i { font-size: 0.85rem; }\
\
.sp-section {\
    font-size: 0.6rem; font-weight: 700; color: #6b7280;\
    text-transform: uppercase; letter-spacing: 0.12em;\
    padding: 10px 16px 3px;\
}\
\
.sp-row {\
    display: flex; align-items: center; gap: 10px;\
    padding: 8px 16px;\
    color: #d1d5db; font-size: 0.83rem; font-weight: 500;\
    text-decoration: none;\
    cursor: pointer;\
    border-left: 3px solid transparent;\
    transition: background 0.15s, color 0.15s, border-color 0.15s;\
}\
.sp-row:hover { background: #111827; color: #2dd4bf; border-left-color: #0d9488; }\
.sp-row.sp-active { background: #0d9488; color: #fff; border-left-color: rgba(255,255,255,0.3); }\
\
.sp-row-ico {\
    width: 24px; height: 24px; border-radius: 6px;\
    background: #374151;\
    display: flex; align-items: center; justify-content: center;\
    flex-shrink: 0; font-size: 0.75rem; color: #2dd4bf;\
    transition: background 0.15s, color 0.15s;\
}\
.sp-row:hover .sp-row-ico,\
.sp-row.sp-active .sp-row-ico { background: rgba(255,255,255,0.2); color: #fff; }\
.sp-row-lbl { flex: 1; }\
\
.sp-lang-code {\
    font-size: 0.6rem; font-weight: 700;\
    padding: 2px 6px; border-radius: 20px;\
    background: #374151; color: #9ca3af;\
    transition: background 0.15s, color 0.15s;\
}\
.sp-row.sp-active .sp-lang-code { background: rgba(255,255,255,0.25); color: #fff; }\
\
.sp-cur-badge {\
    font-size: 0.6rem; font-weight: 700;\
    padding: 2px 6px; border-radius: 20px;\
    background: rgba(255,255,255,0.22); color: #fff;\
}\
.sp-divider { height: 1px; background: #374151; margin: 4px 12px; }\
';

    // ── Helpers ─────────────────────────────────────────────────────────────
    function getLang() { return localStorage.getItem('ptjo_lang') || 'en'; }

    function langRow(code, flag, label, langCode) {
        var on = getLang() === code ? ' sp-active' : '';
        return '<div class="sp-row' + on + '" id="sp-lang-' + code + '" onclick="PTjoSettings._setLang(\'' + code + '\')">'
             + '<span class="sp-row-ico">' + flag + '</span>'
             + '<span class="sp-row-lbl">' + label + '</span>'
             + '<span class="sp-lang-code">' + langCode + '</span>'
             + '</div>';
    }

    function navRow(href, icon, label, keyword) {
        var on  = keyword && isPage(keyword) ? ' sp-active' : '';
        var bdg = on ? '<span class="sp-cur-badge">Current</span>' : '';
        return '<a class="sp-row' + on + '" href="' + href + '">'
             + '<span class="sp-row-ico"><i class="fas ' + icon + '"></i></span>'
             + '<span class="sp-row-lbl">' + label + '</span>'
             + bdg
             + '</a>';
    }

    // ── Build dropdown HTML ──────────────────────────────────────────────────
    function buildDropdown() {
        return '<div class="sp-drop-hdr"><i class="fas fa-sliders-h"></i> Settings</div>'

             // Language
             + '<div class="sp-section">Language</div>'
             + langRow('ar', '🇸🇦', 'العربية', 'AR')
             + langRow('en', '🇺🇸', 'English',  'EN')

             + '<div class="sp-divider"></div>'

             + navRow(P + 'Settings/Settings.html', 'fa-cog', 'Settings', '/settings/');
    }

    // ── Inject CSS ───────────────────────────────────────────────────────────
    function injectCSS() {
        if (document.getElementById('sp-styles')) return;
        var el = document.createElement('style');
        el.id = 'sp-styles';
        el.textContent = CSS;
        document.head.appendChild(el);
    }

    // ── Inject Font Awesome if missing (e.g. Login page) ────────────────────
    function ensureFA() {
        if (document.querySelector('link[href*="font-awesome"]')) return;
        var l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css';
        l.crossOrigin = 'anonymous';
        document.head.appendChild(l);
    }

    // ── Main: find the language button and replace it ────────────────────────
    function init() {
        injectCSS();
        ensureFA();

        var langFlag = document.getElementById('lang-flag');
        if (!langFlag) return;

        var origBtn = langFlag.closest('button');
        if (!origBtn) return;

        // Build wrapper
        var wrap = document.createElement('div');
        wrap.id = 'sp-wrap';

        // Settings gear button (replaces the old lang toggle)
        var btn = document.createElement('button');
        btn.id = 'sp-btn';
        btn.title = 'Settings';
        btn.innerHTML = '<i class="fas fa-cog"></i>'
                      + '<span id="lang-flag">' + (getLang() === 'ar' ? '🇸🇦' : '🇺🇸') + '</span>'
                      + '<span id="lang-label" style="letter-spacing:0.05em">' + (getLang() === 'ar' ? 'AR' : 'EN') + '</span>'
                      + '<i class="fas fa-chevron-down" style="font-size:0.65rem;margin-left:2px"></i>';

        // Dropdown
        var drop = document.createElement('div');
        drop.id = 'sp-drop';
        drop.innerHTML = buildDropdown();

        wrap.appendChild(btn);
        wrap.appendChild(drop);

        // Replace original button
        origBtn.parentNode.replaceChild(wrap, origBtn);

        // Toggle on button click
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = drop.classList.toggle('sp-visible');
            btn.classList.toggle('sp-open', open);
        });

        // Close on outside click
        document.addEventListener('click', function () {
            drop.classList.remove('sp-visible');
            btn.classList.remove('sp-open');
        });

        // Don't close when clicking inside dropdown
        drop.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ── Public API ───────────────────────────────────────────────────────────
    window.PTjoSettings = {
        _setLang: function (lang) {
            localStorage.setItem('ptjo_lang', lang);

            if (window.PTjoLang && typeof window.PTjoLang.apply === 'function') {
                window.PTjoLang.apply(lang);
            }

            // Update the gear button flag + label
            var flag  = document.getElementById('lang-flag');
            var label = document.getElementById('lang-label');
            if (flag)  flag.textContent  = lang === 'ar' ? '🇸🇦' : '🇺🇸';
            if (label) label.textContent = lang === 'ar' ? 'AR'   : 'EN';

            // Update active highlight inside dropdown
            ['ar', 'en'].forEach(function (code) {
                var el = document.getElementById('sp-lang-' + code);
                if (el) el.classList.toggle('sp-active', code === lang);
            });

            // Close dropdown
            var drop = document.getElementById('sp-drop');
            var btn  = document.getElementById('sp-btn');
            if (drop) drop.classList.remove('sp-visible');
            if (btn)  btn.classList.remove('sp-open');
        }
    };

})();
