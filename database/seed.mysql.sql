-- PTjo MySQL seed (run after schema.mysql.sql)

INSERT INTO service_offerings (code, label, icon, sort_order) VALUES
  ('web', 'Web Application Pentest', 'fa-globe', 1),
  ('network', 'Network Penetration Test', 'fa-network-wired', 2),
  ('mobile', 'Mobile App Security', 'fa-mobile-alt', 3),
  ('cloud', 'Cloud Security Assessment', 'fa-cloud', 4),
  ('social', 'Social Engineering Assessment', 'fa-user-secret', 5)
ON DUPLICATE KEY UPDATE
  label = VALUES(label),
  icon = VALUES(icon),
  sort_order = VALUES(sort_order);

