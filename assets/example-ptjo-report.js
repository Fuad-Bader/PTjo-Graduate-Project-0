/* PTjo demo: same shape as submitReport() → localStorage.ptjo_reports (XSS + Lorem; no attachments). */
window.PTJO_EXAMPLE_REPORTS = [
  {
    id: 'RPT-4F2A9B',
    engagementId: 'ENG-4F2A9B',
    service: 'Web App Testing',
    hackerName: 'Khalid Al-Rashidi',
    client: 'Al-Rashidi Corp',
    submittedAt: '2025-03-23T16:00:00.000Z',
    status: 'submitted',
    severity: 'High',
    priority: 2,
    serviceType: 'Web App Testing — scoped web assessment',
    vulnCategory: 'Cross-Site Scripting',
    vulnSubcategory: 'Reflected XSS',
    vulnVariant: 'HTML context',
    vulnCategoryFull: 'A03:2021 — Injection (Cross-Site Scripting)',
    vulnSubcategoryFull: 'Reflected cross-site scripting',
    vulnVariantFull: 'Reflected — HTML sink',
    vulnerabilityPath: 'XSS · Reflected · HTML context (checkout)',
    title: 'Reflected XSS via unencoded query parameter on checkout review',
    url: 'https://shop.example.com/checkout/review?q=',
    description:
      'The `q` parameter is reflected into the page HTML without output encoding. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.\n\n' +
      'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    impact:
      'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque habitant morbi tristique senectus et netus. Attackers may execute scripts in the victim browser, phish credentials on the payment step, or deface the checkout UI until remediated.',
    weakness: 'CWE-79: Improper neutralization of input during web page generation (cross-site scripting).',
    recommendations:
      '1. Lorem ipsum — HTML-encode all dynamic output and use CSP.\n2. Dolor sit amet — add server-side validation for `q` and similar reflected fields.\n3. Consectetur — regression tests for XSS payloads in the checkout flow.',
    agreedAmount: 1200,
    editNote: '',
    paymentMethod: '',
    paymentCard: '',
    files: [],
    submittedBy: 'Khalid Al-Rashidi',
    hackerAvatar:
      'https://api.dicebear.com/7.x/initials/svg?seed=Khalid&backgroundColor=0d9488',
    isDisplaySeed: true
  }
];
