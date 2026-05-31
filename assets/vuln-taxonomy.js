const PTjoVulnTaxonomy = (() => {
    const JSON_PATH = (() => {
        try {
            const s = document.currentScript;
            if (s && s.src) return new URL('vulnerability-rating-taxonomy.json', s.src).href;
        } catch (e) { /* ignore */ }
        return '../assets/vulnerability-rating-taxonomy.json';
    })();

    const SERVICE_VULN_MAP = {
        'Web App Testing':    ['cross_site_scripting_xss','cross_site_request_forgery_csrf','broken_access_control','broken_authentication_and_session_management','server_side_injection','sensitive_data_exposure','server_security_misconfiguration','unvalidated_redirects_and_forwards','using_components_with_known_vulnerabilities','application_level_denial_of_service_dos','cryptographic_weakness','client_side_injection'],
        'Network Testing':    ['network_security_misconfiguration','protocol_specific_misconfiguration','insecure_data_transport','server_security_misconfiguration','cryptographic_weakness','broken_access_control','physical_security_issues','insecure_os_firmware'],
        'Cloud Testing':      ['cloud_security','broken_access_control','insecure_data_storage','sensitive_data_exposure','server_security_misconfiguration','cryptographic_weakness','insecure_data_transport','application_level_denial_of_service_dos'],
        'API Testing':        ['broken_access_control','broken_authentication_and_session_management','server_side_injection','sensitive_data_exposure','application_level_denial_of_service_dos','server_security_misconfiguration','cryptographic_weakness','cross_site_scripting_xss','insufficient_security_configurability'],
        'Mobile App Testing': ['mobile_security_misconfiguration','insecure_data_storage','broken_access_control','broken_authentication_and_session_management','cryptographic_weakness','client_side_injection','insecure_data_transport','application_level_denial_of_service_dos','insufficient_security_configurability'],
        'IoT Testing':        ['insecure_os_firmware','lack_of_binary_hardening','network_security_misconfiguration','protocol_specific_misconfiguration','insecure_data_transport','insecure_data_storage','cryptographic_weakness','insufficient_security_configurability','physical_security_issues'],
        'Active Directory':   ['broken_access_control','broken_authentication_and_session_management','server_security_misconfiguration','network_security_misconfiguration','cryptographic_weakness','sensitive_data_exposure','insecure_data_transport'],
    };

    // Priority → Severity mapping
    const PRIORITY_MAP = {
        1: { label: 'Critical', color: '#ef4444', bg: 'rgba(239,68,68,.12)',  border: '#ef4444' },
        2: { label: 'High',     color: '#f97316', bg: 'rgba(249,115,22,.12)', border: '#f97316' },
        3: { label: 'Medium',   color: '#eab308', bg: 'rgba(234,179,8,.12)',  border: '#eab308' },
        4: { label: 'Low',      color: '#22c55e', bg: 'rgba(34,197,94,.12)',  border: '#22c55e' },
        5: { label: 'Info',     color: '#3b82f6', bg: 'rgba(59,130,246,.12)', border: '#3b82f6' },
    };

    let taxonomy = null;
    let loadPromise = null;

    function getCandidateJsonUrls() {
        const urls = [];
        if (JSON_PATH) urls.push(JSON_PATH);
        try {
            const page = window.location.href;
            const fromPage = new URL('../assets/vulnerability-rating-taxonomy.json', page).href;
            if (!urls.includes(fromPage)) urls.push(fromPage);
        } catch (e) { /* ignore */ }
        try {
            if (typeof document !== 'undefined' && document.baseURI) {
                const fromBase = new URL('assets/vulnerability-rating-taxonomy.json', document.baseURI).href;
                if (!urls.includes(fromBase)) urls.push(fromBase);
            }
        } catch (e) { /* ignore */ }
        return urls;
    }

    // Load and cache the JSON once (try several URLs for file:// vs http://)
    async function load() {
        if (taxonomy) return taxonomy;
        if (loadPromise) return loadPromise;
        loadPromise = (async () => {
            const urls = getCandidateJsonUrls();
            for (const url of urls) {
                try {
                    const r = await fetch(url, { cache: 'no-cache' });
                    if (!r.ok) continue;
                    const data = await r.json();
                    if (data && Array.isArray(data.content)) {
                        taxonomy = data;
                        return taxonomy;
                    }
                } catch (e) {
                    /* try next URL */
                }
            }
            console.error('PTjoVulnTaxonomy: failed to load vulnerability-rating-taxonomy.json from', urls);
            return null;
        })();
        return loadPromise;
    }

    // Get all top-level categories
    async function getCategories() {
        const data = await load();
        return data ? data.content : [];
    }

    async function getCategoriesForService(serviceName) {
        const all = await getCategories();
        const ids = SERVICE_VULN_MAP[serviceName] || [];
        return ids.map(id => all.find(c => c.id === id)).filter(Boolean);
    }

    function formatVulnOptionLabel(pathNames, priority) {
        const base = pathNames.join(' → ');
        if (priority != null && priority !== '' && !Number.isNaN(Number(priority))) {
            const p = Number(priority);
            return base + '  [P' + p + ' · ' + (PRIORITY_MAP[p]?.label || '') + ']';
        }
        return base;
    }

    /** Every selectable leaf under a category (category-only, sub-only, or sub → variant). */
    function collectVulnerabilityLeaves(cat) {
        const rows = [];
        const catName = cat.name;
        const catId = cat.id;
        if (!cat.children || !cat.children.length) {
            rows.push({
                categoryId: catId,
                subcategoryId: '',
                variantId: '',
                shortText: catName,
                fullLabel: formatVulnOptionLabel([catName], cat.priority),
            });
            return rows;
        }
        for (const sub of cat.children) {
            const subsKids = sub.children || [];
            if (subsKids.length > 0) {
                for (const v of subsKids) {
                    rows.push({
                        categoryId: catId,
                        subcategoryId: sub.id,
                        variantId: v.id,
                        shortText: sub.name + ' → ' + v.name,
                        fullLabel: formatVulnOptionLabel([catName, sub.name, v.name], v.priority),
                    });
                }
            } else {
                rows.push({
                    categoryId: catId,
                    subcategoryId: sub.id,
                    variantId: '',
                    shortText: sub.name,
                    fullLabel: formatVulnOptionLabel([catName, sub.name], sub.priority),
                });
            }
        }
        return rows;
    }

    /** All vulnerability rows for every category mapped to this PTjo service (full tree flattened). */
    async function getAllVulnerabilitiesForService(serviceName) {
        const all = await getCategories();
        const ids = SERVICE_VULN_MAP[serviceName] || [];
        const out = [];
        for (const id of ids) {
            const cat = all.find(c => c.id === id);
            if (!cat) continue;
            out.push({ category: cat, rows: collectVulnerabilityLeaves(cat) });
        }
        return out;
    }

    const VULN_SEL_SEP = '\x1e';

    function packVulnSelection(categoryId, subcategoryId, variantId) {
        return [categoryId || '', subcategoryId || '', variantId || ''].join(VULN_SEL_SEP);
    }

    function unpackVulnSelection(value) {
        const p = String(value || '').split(VULN_SEL_SEP);
        return {
            categoryId: p[0] || '',
            subcategoryId: p[1] || '',
            variantId: p[2] || '',
        };
    }

    /**
     * Fill a native &lt;select&gt; with flat &lt;option&gt;s from JSON (reliable dropdown UX; no optgroup quirks).
     */
    async function populateServiceVulnerabilitySelect(selectEl, serviceName, placeholder) {
        const data = await load();
        selectEl.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        if (!data) {
            ph.textContent = 'Could not load taxonomy — use Live Server / http (not file://)';
            selectEl.appendChild(ph);
            return;
        }
        ph.textContent = placeholder;
        selectEl.appendChild(ph);

        const grouped = await getAllVulnerabilitiesForService(serviceName);
        let count = 0;
        for (const { category, rows } of grouped) {
            if (!rows.length) continue;
            for (const row of rows) {
                const opt = document.createElement('option');
                opt.value = packVulnSelection(row.categoryId, row.subcategoryId, row.variantId);
                opt.textContent = category.name + ' — ' + row.shortText;
                opt.dataset.fullLabel = row.fullLabel;
                selectEl.appendChild(opt);
                count++;
            }
        }
        if (count === 0) {
            ph.textContent = 'No vulnerabilities listed for "' + serviceName + '" in taxonomy.';
        }
    }

    // Get subcategories for a given category id
    async function getSubcategories(categoryId) {
        const cats = await getCategories();
        const cat  = cats.find(c => c.id === categoryId);
        return cat?.children || [];
    }

    // Get variants for a given category id + subcategory id
    async function getVariants(categoryId, subcategoryId) {
        const subs = await getSubcategories(categoryId);
        const sub  = subs.find(s => s.id === subcategoryId);
        return sub?.children || [];
    }

    // Resolve priority from the deepest selected level
    async function resolvePriority(categoryId, subcategoryId = null, variantId = null) {
        const cats = await getCategories();
        const cat  = cats.find(c => c.id === categoryId);
        if (!cat) return null;

        if (variantId && subcategoryId) {
            const sub     = cat.children?.find(s => s.id === subcategoryId);
            const variant = sub?.children?.find(v => v.id === variantId);
            if (variant?.priority) return variant.priority;
        }
        if (subcategoryId) {
            const sub = cat.children?.find(s => s.id === subcategoryId);
            if (sub?.priority) return sub.priority;
        }
        if (cat.priority) return cat.priority;
        return null;
    }

    // Get severity object from priority number
    function getSeverity(priority) {
        return PRIORITY_MAP[priority] || null;
    }

    // Populate a <select> element with options from an array
    function populateSelect(selectEl, items, placeholder) {
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
        items.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name + (item.priority ? '  [P' + item.priority + ' · ' + (PRIORITY_MAP[item.priority]?.label || '') + ']' : '');
            selectEl.appendChild(opt);
        });
    }

    return {
        load,
        getCategories,
        getCategoriesForService,
        getAllVulnerabilitiesForService,
        collectVulnerabilityLeaves,
        getSubcategories,
        getVariants,
        resolvePriority,
        getSeverity,
        populateSelect,
        populateServiceVulnerabilitySelect,
        packVulnSelection,
        unpackVulnSelection,
        PRIORITY_MAP,
        SERVICE_VULN_MAP,
    };
})();
