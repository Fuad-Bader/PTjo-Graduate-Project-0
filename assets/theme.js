const PTjoTheme = (() => {
    const KEY = 'ptjo_theme';

    function apply(theme) {
        const root = document.documentElement;
        if (theme === 'light') {
            root.classList.add('light-mode');
            root.setAttribute('data-theme', 'light');
        } else {
            root.classList.remove('light-mode');
            root.setAttribute('data-theme', 'dark');
        }
    }

    function set(theme) {
        localStorage.setItem(KEY, theme);
        apply(theme);
    }

    function current() {
        return localStorage.getItem(KEY) || 'dark';
    }

    function init() {
        apply(current());
        const savedFont = localStorage.getItem('ptjo_font_size');
        if (savedFont) {
            document.documentElement.style.fontSize = savedFont + 'px';
        }
    }

    return { set, current, init, apply };
})();

// Auto-apply on every page load
document.addEventListener('DOMContentLoaded', PTjoTheme.init);

(function injectLightModeStyles() {
    const style = document.createElement('style');
    style.id = 'ptjo-theme-styles';
    style.textContent = `

        /* ── Page foundations ───────────────────────────────────────── */
        [data-theme="light"] html,
        [data-theme="light"] body                           { background-color: #f3f4f6 !important; color: #111827 !important; }

        /* ── Sections / Main / Header / Footer ─────────────────────── */
        [data-theme="light"] section                        { background-image: none !important; background-color: transparent !important; }
        [data-theme="light"] main                           { background-image: none !important; background-color: transparent !important; }
        [data-theme="light"] header:not(nav header)         { background-image: none !important; background-color: #f9fafb !important; }
        [data-theme="light"] footer                         { background-color: #e5e7eb !important; background-image: none !important; color: #374151 !important; }

        /* ── Dark navy inline styles (data-dark-bg attribute) ───────── */
        [data-theme="light"] [data-dark-bg]                 { background: #f3f4f6 !important; background-image: none !important; }
        [data-theme="light"] [data-dark-text]               { color: #111827 !important; }

        /* ── Dark navy inline styles (attribute selector fallback) ─── */
        [data-theme="light"] [style*="#0f172a"]             { background-color: #f3f4f6 !important; background-image: none !important; }
        [data-theme="light"] [style*="#0d1117"]             { background-color: #f3f4f6 !important; background-image: none !important; }
        [data-theme="light"] [style*="#060d1a"]             { background-color: #f3f4f6 !important; background-image: none !important; }
        [data-theme="light"] [style*="#080f1e"]             { background-color: #f3f4f6 !important; background-image: none !important; }
        [data-theme="light"] [style*="#0a0f1e"]             { background-color: #f3f4f6 !important; background-image: none !important; }
        [data-theme="light"] [style*="#1f2937"]             { background-color: #ffffff !important; background-image: none !important; }
        [data-theme="light"] [style*="#111827"]             { background-color: #f3f4f6 !important; background-image: none !important; }

        /* ── Tailwind slate/gray page backgrounds ───────────────────── */
        [data-theme="light"] .bg-slate-950,
        [data-theme="light"] .bg-slate-900                  { background-color: #f3f4f6 !important; }
        [data-theme="light"] .bg-gray-950                   { background-color: #f3f4f6 !important; }
        [data-theme="light"] .bg-gray-900                   { background-color: #f3f4f6 !important; }

        /* ── Cards / Containers ─────────────────────────────────────── */
        [data-theme="light"] .bg-gray-800,
        [data-theme="light"] [class*="bg-gray-800"]         { background-color: #ffffff !important; }
        [data-theme="light"] .bg-gray-800\/60               { background-color: rgba(255,255,255,0.95) !important; }
        [data-theme="light"] .bg-gray-800\/40               { background-color: rgba(255,255,255,0.8) !important; }
        [data-theme="light"] .bg-gray-800\/30               { background-color: rgba(255,255,255,0.7) !important; }

        /* ── Icon wrappers / subtle containers ──────────────────────── */
        [data-theme="light"] .bg-gray-700,
        [data-theme="light"] [class*="bg-gray-700"]         { background-color: #e5e7eb !important; }
        [data-theme="light"] .bg-gray-700\/60               { background-color: rgba(229,231,235,0.8) !important; }
        [data-theme="light"] .bg-gray-700\/50               { background-color: rgba(229,231,235,0.7) !important; }
        [data-theme="light"] .bg-gray-700\/40               { background-color: rgba(229,231,235,0.6) !important; }

        [data-theme="light"] .bg-gray-600,
        [data-theme="light"] [class*="bg-gray-600"]         { background-color: #d1d5db !important; }

        /* ── Gradient backgrounds ───────────────────────────────────── */
        [data-theme="light"] [class*="from-gray-9"],
        [data-theme="light"] [class*="from-slate-9"],
        [data-theme="light"] [class*="to-gray-9"],
        [data-theme="light"] [class*="to-slate-9"]          { background-image: none !important; background-color: #f3f4f6 !important; }
        [data-theme="light"] [class*="bg-gradient"]         { background-image: linear-gradient(135deg, #f0fdfa 0%, #f9fafb 100%) !important; }

        /* ── Borders ────────────────────────────────────────────────── */
        [data-theme="light"] .border-gray-800,
        [data-theme="light"] [class*="border-gray-800"]     { border-color: #e5e7eb !important; }
        [data-theme="light"] .border-gray-700,
        [data-theme="light"] [class*="border-gray-700"]     { border-color: #e5e7eb !important; }
        [data-theme="light"] .border-gray-600,
        [data-theme="light"] [class*="border-gray-600"]     { border-color: #d1d5db !important; }

        /* ── Text ───────────────────────────────────────────────────── */
        [data-theme="light"] .text-white                    { color: #111827 !important; }
        [data-theme="light"] .text-gray-100                 { color: #1f2937 !important; }
        [data-theme="light"] .text-gray-200                 { color: #1f2937 !important; }
        [data-theme="light"] .text-gray-300                 { color: #374151 !important; }
        [data-theme="light"] .text-gray-400                 { color: #4b5563 !important; }
        [data-theme="light"] .text-gray-500                 { color: #6b7280 !important; }
        [data-theme="light"] .text-gray-600                 { color: #6b7280 !important; }

        /* ── Remove dark text-shadows ───────────────────────────────── */
        [data-theme="light"] [class*="text-white"],
        [data-theme="light"] [class*="text-gray"]           { text-shadow: none !important; }

        /* ── Navbar ─────────────────────────────────────────────────── */
        [data-theme="light"] nav                            { background-color: #ffffff !important; border-bottom-color: rgba(13,148,136,0.2) !important; box-shadow: 0 1px 8px rgba(0,0,0,0.08) !important; background-image: none !important; }

        /* ── Dropdown menus ─────────────────────────────────────────── */
        [data-theme="light"] .dropdown-menu                 { background-color: #ffffff !important; border-color: #e5e7eb !important; box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }
        [data-theme="light"] .dropdown-item-pro             { color: #374151 !important; }
        [data-theme="light"] .dropdown-item-pro:hover       { background-color: #0d9488 !important; color: #ffffff !important; }
        [data-theme="light"] .dropdown-icon                 { color: #0d9488 !important; }

        /* ── Sidebar tabs ───────────────────────────────────────────── */
        [data-theme="light"] .sidebar-tab                   { color: #4b5563 !important; }
        [data-theme="light"] .sidebar-tab:hover             { background: rgba(13,148,136,0.08) !important; color: #1f2937 !important; }
        [data-theme="light"] .sidebar-tab.active            { background: rgba(13,148,136,0.12) !important; color: #0d9488 !important; }

        /* ── Named card classes ─────────────────────────────────────── */
        [data-theme="light"] .job-card,
        [data-theme="light"] .eng-card,
        [data-theme="light"] .pentester-card,
        [data-theme="light"] .stat-card,
        [data-theme="light"] .service-card                  { background-color: #ffffff !important; border-color: #e5e7eb !important; }

        /* ── Form inputs ────────────────────────────────────────────── */
        [data-theme="light"] input:not([type="checkbox"]):not([type="radio"]):not([type="range"]),
        [data-theme="light"] select,
        [data-theme="light"] textarea                       { background-color: #f9fafb !important; color: #111827 !important; border-color: #d1d5db !important; }
        [data-theme="light"] input::placeholder,
        [data-theme="light"] textarea::placeholder          { color: #9ca3af !important; }
        [data-theme="light"] .form-input                    { background-color: #f9fafb !important; color: #111827 !important; border-color: #d1d5db !important; }
        [data-theme="light"] .input-field                   { background-color: #f9fafb !important; color: #111827 !important; border-color: #d1d5db !important; }

        /* ── Dividers ───────────────────────────────────────────────── */
        [data-theme="light"] hr                             { border-color: #e5e7eb !important; }
        [data-theme="light"] .divide-gray-700 > * + *       { border-color: #e5e7eb !important; }

        /* ── Scrollbar ──────────────────────────────────────────────── */
        [data-theme="light"] ::-webkit-scrollbar-track      { background: #f3f4f6 !important; }
        [data-theme="light"] ::-webkit-scrollbar-thumb      { background: #d1d5db !important; }
        [data-theme="light"] ::-webkit-scrollbar-thumb:hover{ background: #9ca3af !important; }

        /* ── Gray-500 / Slate-600/700 backgrounds ──────────────────── */
        /* (bg-gray-600 is intentionally NOT re-declared here — it is set
            to #d1d5db above so gray-600 surfaces stay distinct from the
            #f3f4f6 page background instead of blending into it.) */
        [data-theme="light"] .bg-gray-500                   { background-color: #e5e7eb !important; }
        [data-theme="light"] .bg-slate-600                  { background-color: #e5e7eb !important; }
        [data-theme="light"] .bg-slate-700                  { background-color: #e5e7eb !important; }

        /* ── Ad / Sponsor / Banner / Promo cards ───────────────────── */
        [data-theme="light"] .ad-banner-glass               { background: rgba(255,255,255,0.9) !important; background-image: none !important; border-color: rgba(13,148,136,0.2) !important; backdrop-filter: none !important; }
        [data-theme="light"] .ad-banner-glass::before       { display: none !important; }
        [data-theme="light"] [class*="ad-card"],
        [data-theme="light"] [class*="sponsor"],
        [data-theme="light"] [class*="banner"],
        [data-theme="light"] [class*="promo"]               { background-color: #f3f4f6 !important; background-image: none !important; border-color: #e5e7eb !important; }
        [data-theme="light"] [class*="ad-card"] p,
        [data-theme="light"] [class*="sponsor"] p,
        [data-theme="light"] [class*="banner"] p,
        [data-theme="light"] [class*="promo"] p             { color: #374151 !important; }

        /* ── Dark slate inline styles ───────────────────────────────── */
        [data-theme="light"] [style*="#4a5568"]             { background-color: #f3f4f6 !important; background-image: none !important; }
        [data-theme="light"] [style*="#475569"]             { background-color: #f3f4f6 !important; background-image: none !important; }
        [data-theme="light"] [style*="rgba(71"]             { background-color: #f3f4f6 !important; background-image: none !important; }
        [data-theme="light"] [style*="rgba(75"]             { background-color: #f3f4f6 !important; background-image: none !important; }

        /* ── data-dark-bg / data-dark-text attributes ───────────────── */
        [data-theme="light"] [data-dark-bg]                 { background-color: #f3f4f6 !important; background-image: none !important; }
        [data-theme="light"] [data-dark-text]               { color: #111827 !important; }

        /* ── Elite Crowd Sourced SVG — light mode fix ──────────────── */
        [data-theme="light"] .advantage-svg                 { background-color: #f0fdfa !important; border-radius: 12px !important; }
        [data-theme="light"] .advantage-svg rect:first-child { fill: #f0fdfa !important; }
        [data-theme="light"] .advantage-svg circle,
        [data-theme="light"] .advantage-svg ellipse         { fill: #0d9488 !important; stroke: #0d9488 !important; }
        [data-theme="light"] .advantage-svg line,
        [data-theme="light"] .advantage-svg path[stroke-dasharray],
        [data-theme="light"] .advantage-svg polyline        { stroke: #0d9488 !important; stroke-opacity: 0.6 !important; }
        /* Hide the dark gradient overlay that washes out the SVG in light mode */
        [data-theme="light"] .advantage-overlay             { background: none !important; background-image: none !important; }

        /* ── Keep teal accents unchanged (no overrides for teal-*) ─── */

    `;
    document.head.appendChild(style);
})();
