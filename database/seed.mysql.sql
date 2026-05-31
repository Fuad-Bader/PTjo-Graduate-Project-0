-- PTjo MySQL seed (run after schema.mysql.sql)
-- Contains all 7 service offerings + demo users, bounties, and related data
-- that were previously hardcoded in the HTML front-end files.

-- ─────────────────────────────────────────────────────────────
-- 1. SERVICE OFFERINGS (all 7 types used by the UI)
-- ─────────────────────────────────────────────────────────────
INSERT INTO service_offerings (code, label, icon, sort_order) VALUES
  ('network', 'Network Testing',    'fa-network-wired', 1),
  ('ad',      'Active Directory',   'fa-users-cog',     2),
  ('cloud',   'Cloud Testing',      'fa-cloud',         3),
  ('webapp',  'Web App Testing',    'fa-globe',         4),
  ('mobile',  'Mobile App Testing', 'fa-mobile-alt',    5),
  ('api',     'API Testing',        'fa-cube',          6),
  ('iot',     'IoT Testing',        'fa-wifi',          7)
ON DUPLICATE KEY UPDATE
  label      = VALUES(label),
  icon       = VALUES(icon),
  sort_order = VALUES(sort_order);

-- ─────────────────────────────────────────────────────────────
-- 2. DEMO USERS
--    Passwords are bcrypt hashes of "Demo@12345!"
--    (cost 12 — change before any public deployment)
-- ─────────────────────────────────────────────────────────────

-- 2a. Demo customers
INSERT INTO users (id, email, password_hash, role) VALUES
  ('00000000-0000-0000-0001-000000000001', 'acme@demo.ptjo.io',       '$2y$12$demoHashPlaceholderAcme000000000000000000000000000000', 'customer'),
  ('00000000-0000-0000-0001-000000000002', 'gulf.secure@demo.ptjo.io','$2y$12$demoHashPlaceholderGulf000000000000000000000000000000', 'customer')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO customer_profiles (user_id, display_name, company_name) VALUES
  ('00000000-0000-0000-0001-000000000001', 'Acme Corp',       'Acme Corp'),
  ('00000000-0000-0000-0001-000000000002', 'Gulf Secure Ltd', 'Gulf Secure Ltd')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

-- 2b. Demo hackers (match the mockPentesters array in Customer_Dashboard.html)
INSERT INTO users (id, email, password_hash, role) VALUES
  ('00000000-0000-0000-0002-000000000001', 'nullpointer@demo.ptjo.io',    '$2y$12$demoHashPlaceholderNull000000000000000000000000000000', 'hacker'),
  ('00000000-0000-0000-0002-000000000002', 'shadowscript@demo.ptjo.io',   '$2y$12$demoHashPlaceholderShdw000000000000000000000000000000', 'hacker'),
  ('00000000-0000-0000-0002-000000000003', 'rootaccess@demo.ptjo.io',     '$2y$12$demoHashPlaceholderRoot000000000000000000000000000000', 'hacker'),
  ('00000000-0000-0000-0002-000000000004', 'cloudbreaker@demo.ptjo.io',   '$2y$12$demoHashPlaceholderCld0000000000000000000000000000000', 'hacker'),
  ('00000000-0000-0000-0002-000000000005', 'firmwarefox@demo.ptjo.io',    '$2y$12$demoHashPlaceholderFirm000000000000000000000000000000', 'hacker'),
  ('00000000-0000-0000-0002-000000000006', 'mobilephantom@demo.ptjo.io',  '$2y$12$demoHashPlaceholderMob0000000000000000000000000000000', 'hacker')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO hacker_profiles (user_id, handle, display_name, professional_title, bio, years_experience, hourly_rate_usd, avatar_url) VALUES
  ('00000000-0000-0000-0002-000000000001', 'NullPointer',    'NullPointer',    'Senior Infrastructure Specialist',
   'Senior infrastructure specialist with 6 years of offensive security experience. Known for uncovering complex multi-hop attack paths in enterprise environments.',
   '5 – 10 years', 120.00,
   'https://api.dicebear.com/7.x/avataaars/svg?seed=NullPointer&backgroundColor=0d9488'),

  ('00000000-0000-0000-0002-000000000002', 'ShadowScript',   'ShadowScript',   'Web App & API Pentester',
   'Expert web application and API penetration tester. Specializes in complex multi-step logic vulnerabilities and authentication bypass chains that automated scanners miss.',
   '5 – 10 years', 100.00,
   'https://api.dicebear.com/7.x/avataaars/svg?seed=ShadowScript&backgroundColor=0f766e'),

  ('00000000-0000-0000-0002-000000000003', 'RootAccess',     'RootAccess',     'Elite Red Team Operator',
   'Elite-tier pentester with deep expertise in Active Directory attacks and custom payload development. Background in red teaming Fortune 500 financial institutions.',
   '5 – 10 years', 150.00,
   'https://api.dicebear.com/7.x/avataaars/svg?seed=RootAccess&backgroundColor=134e4a'),

  ('00000000-0000-0000-0002-000000000004', 'CloudBreaker',   'CloudBreaker',   'Cloud Security Specialist',
   'Cloud-native offensive security specialist focusing on AWS, Azure, and GCP misconfigurations. Has discovered critical IAM privilege escalation paths in three major cloud providers.',
   '3 – 5 years', 110.00,
   'https://api.dicebear.com/7.x/avataaars/svg?seed=CloudBreaker&backgroundColor=1e3a5f'),

  ('00000000-0000-0000-0002-000000000005', 'FirmwareFox',    'FirmwareFox',    'IoT & Embedded Systems Expert',
   'Embedded systems security pentester with hands-on experience in hardware reverse engineering, firmware extraction, and radio protocol attacks on IoT/OT devices.',
   '3 – 5 years', 95.00,
   'https://api.dicebear.com/7.x/avataaars/svg?seed=FirmwareFox&backgroundColor=5c3d11'),

  ('00000000-0000-0000-0002-000000000006', 'MobilePhantom',  'MobilePhantom',  'Mobile Security Researcher',
   'Mobile application security expert with deep experience in reverse engineering both Android and iOS applications. Focuses on data leakage, insecure storage, and backend API abuse.',
   '3 – 5 years', 105.00,
   'https://api.dicebear.com/7.x/avataaars/svg?seed=MobilePhantom&backgroundColor=3b1f6e')
ON DUPLICATE KEY UPDATE handle = VALUES(handle);

-- ─────────────────────────────────────────────────────────────
-- 3. HACKER SKILLS  (service areas each hacker covers)
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO hacker_skills (hacker_id, skill_code) VALUES
  ('00000000-0000-0000-0002-000000000001', 'network'),
  ('00000000-0000-0000-0002-000000000001', 'ad'),
  ('00000000-0000-0000-0002-000000000001', 'cloud'),
  ('00000000-0000-0000-0002-000000000002', 'webapp'),
  ('00000000-0000-0000-0002-000000000002', 'api'),
  ('00000000-0000-0000-0002-000000000002', 'mobile'),
  ('00000000-0000-0000-0002-000000000003', 'ad'),
  ('00000000-0000-0000-0002-000000000003', 'network'),
  ('00000000-0000-0000-0002-000000000003', 'webapp'),
  ('00000000-0000-0000-0002-000000000004', 'cloud'),
  ('00000000-0000-0000-0002-000000000004', 'network'),
  ('00000000-0000-0000-0002-000000000004', 'api'),
  ('00000000-0000-0000-0002-000000000005', 'iot'),
  ('00000000-0000-0000-0002-000000000005', 'network'),
  ('00000000-0000-0000-0002-000000000006', 'mobile'),
  ('00000000-0000-0000-0002-000000000006', 'api'),
  ('00000000-0000-0000-0002-000000000006', 'webapp');

-- ─────────────────────────────────────────────────────────────
-- 4. HACKER TOOLS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO hacker_tools (hacker_id, tool_name) VALUES
  ('00000000-0000-0000-0002-000000000001', 'Nmap'),
  ('00000000-0000-0000-0002-000000000001', 'Metasploit'),
  ('00000000-0000-0000-0002-000000000001', 'BloodHound'),
  ('00000000-0000-0000-0002-000000000001', 'Impacket'),
  ('00000000-0000-0000-0002-000000000002', 'Burp Suite'),
  ('00000000-0000-0000-0002-000000000002', 'SQLMap'),
  ('00000000-0000-0000-0002-000000000002', 'Postman'),
  ('00000000-0000-0000-0002-000000000003', 'BloodHound'),
  ('00000000-0000-0000-0002-000000000003', 'Impacket'),
  ('00000000-0000-0000-0002-000000000003', 'Cobalt Strike'),
  ('00000000-0000-0000-0002-000000000004', 'Pacu'),
  ('00000000-0000-0000-0002-000000000004', 'ScoutSuite'),
  ('00000000-0000-0000-0002-000000000004', 'Prowler'),
  ('00000000-0000-0000-0002-000000000005', 'Binwalk'),
  ('00000000-0000-0000-0002-000000000005', 'Ghidra'),
  ('00000000-0000-0000-0002-000000000005', 'GNU Radio'),
  ('00000000-0000-0000-0002-000000000006', 'Frida'),
  ('00000000-0000-0000-0002-000000000006', 'Jadx'),
  ('00000000-0000-0000-0002-000000000006', 'Objection');

-- ─────────────────────────────────────────────────────────────
-- 5. HACKER LANGUAGES
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO hacker_languages (hacker_id, language) VALUES
  ('00000000-0000-0000-0002-000000000001', 'English'),
  ('00000000-0000-0000-0002-000000000001', 'Arabic'),
  ('00000000-0000-0000-0002-000000000002', 'English'),
  ('00000000-0000-0000-0002-000000000003', 'English'),
  ('00000000-0000-0000-0002-000000000004', 'English'),
  ('00000000-0000-0000-0002-000000000004', 'French'),
  ('00000000-0000-0000-0002-000000000005', 'English'),
  ('00000000-0000-0000-0002-000000000006', 'English'),
  ('00000000-0000-0000-0002-000000000006', 'Arabic');

-- ─────────────────────────────────────────────────────────────
-- 6. CERTIFICATIONS  (match certs arrays from Customer_Dashboard mockPentesters)
-- ─────────────────────────────────────────────────────────────
INSERT INTO certifications (id, hacker_id, name, issuer, issued_on) VALUES
  (UUID(), '00000000-0000-0000-0002-000000000001', 'OSCP – Offensive Security Certified Professional', 'Offensive Security', '2021-06-01'),
  (UUID(), '00000000-0000-0000-0002-000000000001', 'CRT – Certified Red Team Professional',            'Zero-Point Security',  '2022-03-01'),
  (UUID(), '00000000-0000-0000-0002-000000000001', 'CEH – Certified Ethical Hacker',                   'EC-Council',           '2020-09-01'),
  (UUID(), '00000000-0000-0000-0002-000000000002', 'OSWP – Offensive Security Wireless Professional',  'Offensive Security', '2022-11-01'),
  (UUID(), '00000000-0000-0000-0002-000000000002', 'CompTIA PenTest+',                                  'CompTIA',             '2021-04-01'),
  (UUID(), '00000000-0000-0000-0002-000000000002', 'BSCP – Burp Suite Certified Practitioner',         'PortSwigger',          '2023-01-01'),
  (UUID(), '00000000-0000-0000-0002-000000000003', 'OSEP – Experienced Penetration Tester',            'Offensive Security', '2022-08-01'),
  (UUID(), '00000000-0000-0000-0002-000000000003', 'OSCE3 – Expert-level Certification',               'Offensive Security', '2023-05-01'),
  (UUID(), '00000000-0000-0000-0002-000000000003', 'OSED – Exploit Developer',                         'Offensive Security', '2022-01-01'),
  (UUID(), '00000000-0000-0000-0002-000000000004', 'AWS Security Specialty',                           'Amazon Web Services', '2022-07-01'),
  (UUID(), '00000000-0000-0000-0002-000000000004', 'CCSP – Certified Cloud Security Professional',     'ISC²',                '2021-12-01'),
  (UUID(), '00000000-0000-0000-0002-000000000004', 'OSCP – Offensive Security Certified Professional', 'Offensive Security', '2020-10-01'),
  (UUID(), '00000000-0000-0000-0002-000000000005', 'OSCP – Offensive Security Certified Professional', 'Offensive Security', '2021-03-01'),
  (UUID(), '00000000-0000-0000-0002-000000000005', 'GREM – GIAC Reverse Engineering Malware',          'GIAC',                '2022-06-01'),
  (UUID(), '00000000-0000-0000-0002-000000000005', 'eWPTX – Web App Pentester eXtreme',                'eLearnSecurity',       '2023-02-01'),
  (UUID(), '00000000-0000-0000-0002-000000000006', 'eMAPT – Mobile App Pentester',                     'eLearnSecurity',       '2022-09-01'),
  (UUID(), '00000000-0000-0000-0002-000000000006', 'OSCP – Offensive Security Certified Professional', 'Offensive Security', '2021-07-01'),
  (UUID(), '00000000-0000-0000-0002-000000000006', 'GWAPT – GIAC Web App Penetration Tester',          'GIAC',                '2023-04-01');

-- ─────────────────────────────────────────────────────────────
-- 7. WALLETS  (initial balances for all demo users)
-- ─────────────────────────────────────────────────────────────
INSERT INTO wallets (id, user_id, type, balance) VALUES
  (UUID(), '00000000-0000-0000-0001-000000000001', 'customer', 15000.00),
  (UUID(), '00000000-0000-0000-0001-000000000002', 'customer', 8500.00),
  (UUID(), '00000000-0000-0000-0002-000000000001', 'hacker',   22400.00),
  (UUID(), '00000000-0000-0000-0002-000000000002', 'hacker',   38600.00),
  (UUID(), '00000000-0000-0000-0002-000000000003', 'hacker',   51200.00),
  (UUID(), '00000000-0000-0000-0002-000000000004', 'hacker',   17900.00),
  (UUID(), '00000000-0000-0000-0002-000000000005', 'hacker',   12300.00),
  (UUID(), '00000000-0000-0000-0002-000000000006', 'hacker',   29100.00)
ON DUPLICATE KEY UPDATE balance = VALUES(balance);

-- ─────────────────────────────────────────────────────────────
-- 8. OPEN BOUNTY REQUESTS
--    These replace the fallbackJobs array in HackerDashboard.html
-- ─────────────────────────────────────────────────────────────
INSERT INTO bounty_requests (id, public_id, customer_id, service_key, service_label, icon, price_amount, priority_text, deadline, scope_details, status) VALUES
  ('10000000-0000-0000-0000-000000000001',
   'PTJ-2026-NET-001',
   '00000000-0000-0000-0001-000000000001',
   'network', 'Network Testing', 'fa-network-wired',
   3500.00, 'High',
   DATE_ADD(CURDATE(), INTERVAL 14 DAY),
   'Full external perimeter assessment including edge firewalls, VPN endpoints, and DMZ hosts. Lateral movement simulation required. IP ranges: 203.0.113.0/24, 198.51.100.0/24.',
   'open'),

  ('10000000-0000-0000-0000-000000000002',
   'PTJ-2026-WEB-001',
   '00000000-0000-0000-0001-000000000001',
   'webapp', 'Web App Testing', 'fa-globe',
   2000.00, 'Medium',
   DATE_ADD(CURDATE(), INTERVAL 10 DAY),
   'OWASP Top 10 coverage on a React/Node.js e-commerce platform. Focus on checkout flow, authentication, and IDOR vulnerabilities. Staging URL will be provided after NDA.',
   'open'),

  ('10000000-0000-0000-0000-000000000003',
   'PTJ-2026-CLD-001',
   '00000000-0000-0000-0001-000000000002',
   'cloud', 'Cloud Testing', 'fa-cloud',
   4500.00, 'High',
   DATE_ADD(CURDATE(), INTERVAL 21 DAY),
   'AWS environment audit — IAM policy review, S3 bucket enumeration, Lambda function analysis, and CloudTrail log review. Read-only IAM role will be provisioned for the tester.',
   'open'),

  ('10000000-0000-0000-0000-000000000004',
   'PTJ-2026-AD-001',
   '00000000-0000-0000-0001-000000000002',
   'ad', 'Active Directory', 'fa-users-cog',
   6000.00, 'High',
   DATE_ADD(CURDATE(), INTERVAL 21 DAY),
   'Full AD attack path assessment — Kerberoasting, AS-REP Roasting, BloodHound analysis, and privilege escalation to Domain Admin. Domain: corp.demo.local. Low-priv user account provided.',
   'open'),

  ('10000000-0000-0000-0000-000000000005',
   'PTJ-2026-MOB-001',
   '00000000-0000-0000-0001-000000000001',
   'mobile', 'Mobile App Testing', 'fa-mobile-alt',
   1800.00, 'Low',
   DATE_ADD(CURDATE(), INTERVAL 7 DAY),
   'Android APK reverse engineering, certificate pinning bypass, and API endpoint testing for a mobile payment application. APK and test account will be provided.',
   'open'),

  ('10000000-0000-0000-0000-000000000006',
   'PTJ-2026-API-001',
   '00000000-0000-0000-0001-000000000002',
   'api', 'API Testing', 'fa-cube',
   1400.00, 'Medium',
   DATE_ADD(CURDATE(), INTERVAL 5 DAY),
   'REST API security assessment — broken object level auth (BOLA), mass assignment, rate limiting, and JWT token analysis. Swagger/OpenAPI spec and test credentials will be shared.',
   'open')
ON DUPLICATE KEY UPDATE scope_details = VALUES(scope_details);

-- ─────────────────────────────────────────────────────────────
-- 9. REVIEWS  (give each demo hacker some verified reviews)
--    We need engagements first for FK constraints, so we create
--    minimal completed engagements then attach reviews.
-- ─────────────────────────────────────────────────────────────

-- Completed engagements for review seeding
INSERT INTO engagements (id, public_id, customer_id, hacker_id, service_type, agreed_price_usd, status) VALUES
  ('20000000-0000-0000-0000-000000000001', 'ENG-SEED-001', '00000000-0000-0000-0001-000000000001', '00000000-0000-0000-0002-000000000001', 'network',  2800.00, 'completed'),
  ('20000000-0000-0000-0000-000000000002', 'ENG-SEED-002', '00000000-0000-0000-0001-000000000002', '00000000-0000-0000-0002-000000000002', 'webapp',   1900.00, 'completed'),
  ('20000000-0000-0000-0000-000000000003', 'ENG-SEED-003', '00000000-0000-0000-0001-000000000001', '00000000-0000-0000-0002-000000000003', 'ad',       5500.00, 'completed'),
  ('20000000-0000-0000-0000-000000000004', 'ENG-SEED-004', '00000000-0000-0000-0001-000000000002', '00000000-0000-0000-0002-000000000004', 'cloud',    3200.00, 'completed'),
  ('20000000-0000-0000-0000-000000000005', 'ENG-SEED-005', '00000000-0000-0000-0001-000000000001', '00000000-0000-0000-0002-000000000005', 'iot',      1600.00, 'completed'),
  ('20000000-0000-0000-0000-000000000006', 'ENG-SEED-006', '00000000-0000-0000-0001-000000000002', '00000000-0000-0000-0002-000000000006', 'mobile',   2100.00, 'completed')
ON DUPLICATE KEY UPDATE status = 'completed';

-- Seed vulnerability_reports needed for review FK
INSERT INTO vulnerability_reports (id, public_id, engagement_id, hacker_id, customer_id, service_type, severity_label, priority, title, url, description, impact, recommendations, agreed_amount_usd, status) VALUES
  ('30000000-0000-0000-0000-000000000001','RPT-SEED-001','20000000-0000-0000-0000-000000000001','00000000-0000-0000-0002-000000000001','00000000-0000-0000-0001-000000000001','network','High',2,'Unauthenticated RDP Exposed to Internet','rdp://203.0.113.45:3389','Remote Desktop Protocol was found open and accessible from the internet without VPN requirement.','Full system compromise possible without credentials.','Restrict RDP behind VPN or MFA-enabled jump host.',2500.00,'paid'),
  ('30000000-0000-0000-0000-000000000002','RPT-SEED-002','20000000-0000-0000-0000-000000000002','00000000-0000-0000-0002-000000000002','00000000-0000-0000-0001-000000000002','webapp','Critical',1,'SQL Injection in Search Endpoint','https://shop.demo/search?q=','Unsanitised query parameter allows full database dump via time-based blind SQLi.','Complete database exfiltration; authentication bypass possible.','Use parameterised queries / prepared statements.',1700.00,'paid'),
  ('30000000-0000-0000-0000-000000000003','RPT-SEED-003','20000000-0000-0000-0000-000000000003','00000000-0000-0000-0002-000000000003','00000000-0000-0000-0001-000000000001','ad','Critical',1,'Kerberoastable Service Account with Weak Password','ldap://corp.demo.local','svc_backup account has SPN set and uses a dictionary password crackable in <1 min.','Full Domain Admin via DCSync after cracking offline.','Enforce strong password policy on all service accounts; prefer Group Managed Service Accounts.',5000.00,'paid'),
  ('30000000-0000-0000-0000-000000000004','RPT-SEED-004','20000000-0000-0000-0000-000000000004','00000000-0000-0000-0002-000000000004','00000000-0000-0000-0001-000000000002','cloud','High',2,'Over-Privileged IAM Role Allows S3 Full Access','arn:aws:iam::123456789012:role/app-role','The application IAM role has s3:* attached at the account level instead of scoped to its bucket.','Any compromised EC2 instance can exfiltrate all S3 buckets.','Scope IAM policies to least-privilege; use resource-level ARNs.',2900.00,'paid'),
  ('30000000-0000-0000-0000-000000000005','RPT-SEED-005','20000000-0000-0000-0000-000000000005','00000000-0000-0000-0002-000000000005','00000000-0000-0000-0001-000000000001','iot','High',2,'Hardcoded Root Credentials in Firmware','/etc/shadow (firmware v2.1.4)','Extracted firmware contains root:toor hardcoded in shadow file; UART console exploitable.','Physical or network-level full root access.','Remove hardcoded credentials; implement secure boot and firmware signing.',1400.00,'paid'),
  ('30000000-0000-0000-0000-000000000006','RPT-SEED-006','20000000-0000-0000-0000-000000000006','00000000-0000-0000-0002-000000000006','00000000-0000-0000-0001-000000000002','mobile','High',2,'Sensitive Data Stored in Plaintext SharedPreferences','com.demo.mobilepay / SharedPreferences','Auth token and card last4 stored unencrypted in SharedPreferences accessible to any app with root.','Credential theft on rooted devices.','Use Android Keystore for sensitive data; never store credentials in SharedPreferences.',1900.00,'paid')
ON DUPLICATE KEY UPDATE status = 'paid';

-- Now insert reviews (one per completed engagement)
INSERT INTO reviews (id, public_id, report_id, engagement_id, hacker_id, customer_id, client_display_name, client_company, rating, comment, service_label, vuln_title, severity_label) VALUES
  (UUID(),'REV-SEED-001','30000000-0000-0000-0000-000000000001','20000000-0000-0000-0000-000000000001','00000000-0000-0000-0002-000000000001','00000000-0000-0000-0001-000000000001','Acme Corp','Acme Corp',5,'Exceptional work — NullPointer found critical issues our team had missed for months. Clear report, fast turnaround.','Network Testing','Unauthenticated RDP Exposed to Internet','High'),
  (UUID(),'REV-SEED-002','30000000-0000-0000-0000-000000000002','20000000-0000-0000-0000-000000000002','00000000-0000-0000-0002-000000000002','00000000-0000-0000-0001-000000000002','Gulf Secure Ltd','Gulf Secure Ltd',5,'ShadowScript uncovered a critical SQLi that automated scanners had completely missed. Detailed remediation guidance was invaluable.','Web App Testing','SQL Injection in Search Endpoint','Critical'),
  (UUID(),'REV-SEED-003','30000000-0000-0000-0000-000000000003','20000000-0000-0000-0000-000000000003','00000000-0000-0000-0002-000000000003','00000000-0000-0000-0001-000000000001','Acme Corp','Acme Corp',5,'RootAccess escalated to Domain Admin within 4 hours. The depth of AD knowledge is unmatched. Highly recommended for any AD engagement.','Active Directory','Kerberoastable Service Account with Weak Password','Critical'),
  (UUID(),'REV-SEED-004','30000000-0000-0000-0000-000000000004','20000000-0000-0000-0000-000000000004','00000000-0000-0000-0002-000000000004','00000000-0000-0000-0001-000000000002','Gulf Secure Ltd','Gulf Secure Ltd',5,'CloudBreaker identified IAM issues we had overlooked. Professional, thorough, and well-documented findings.','Cloud Testing','Over-Privileged IAM Role Allows S3 Full Access','High'),
  (UUID(),'REV-SEED-005','30000000-0000-0000-0000-000000000005','20000000-0000-0000-0000-000000000005','00000000-0000-0000-0002-000000000005','00000000-0000-0000-0001-000000000001','Acme Corp','Acme Corp',4,'FirmwareFox delivered a solid assessment. Firmware extraction and analysis were impressive. Minor delay in report delivery.','IoT Testing','Hardcoded Root Credentials in Firmware','High'),
  (UUID(),'REV-SEED-006','30000000-0000-0000-0000-000000000006','20000000-0000-0000-0000-000000000006','00000000-0000-0000-0002-000000000006','00000000-0000-0000-0001-000000000002','Gulf Secure Ltd','Gulf Secure Ltd',5,'MobilePhantom found sensitive data storage issues that posed real risk. Great communication throughout the engagement.','Mobile App Testing','Sensitive Data Stored in Plaintext SharedPreferences','High');
