-- ═══════════════════════════════════════════════════════════════════════════
-- PTjo — Optional reference / demo seed (run after schema.sql)
-- ═══════════════════════════════════════════════════════════════════════════
-- Replace password_hash values with real bcrypt/argon2 hashes from your API.
-- ═══════════════════════════════════════════════════════════════════════════

BEGIN;

-- Example: platform service catalog (align keys with Customer Dashboard JS)
INSERT INTO service_offerings (code, label, icon, sort_order) VALUES
    ('web', 'Web Application Pentest', 'fa-globe', 1),
    ('network', 'Network Penetration Test', 'fa-network-wired', 2),
    ('mobile', 'Mobile App Security', 'fa-mobile-alt', 3),
    ('cloud', 'Cloud Security Assessment', 'fa-cloud', 4),
    ('social', 'Social Engineering Assessment', 'fa-user-secret', 5)
ON CONFLICT (code) DO NOTHING;

COMMIT;
