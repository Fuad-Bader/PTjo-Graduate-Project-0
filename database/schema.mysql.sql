-- PTjo MySQL schema (MySQL 8.0+)
-- Converted from PostgreSQL model.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE users (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role ENUM('customer','hacker','admin') NOT NULL DEFAULT 'customer',
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  email_verified_at TIMESTAMP NULL,
  last_login_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_profiles (
  user_id CHAR(36) PRIMARY KEY,
  display_name VARCHAR(255) NOT NULL,
  company_name VARCHAR(255),
  phone_e164 VARCHAR(30),
  avatar_url TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_customer_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hacker_profiles (
  user_id CHAR(36) PRIMARY KEY,
  handle VARCHAR(120) NOT NULL UNIQUE,
  display_name VARCHAR(255) NOT NULL,
  professional_title VARCHAR(255),
  bio TEXT,
  location VARCHAR(255),
  years_experience VARCHAR(64),
  hourly_rate_usd DECIMAL(10,2),
  linkedin_url TEXT,
  github_url TEXT,
  portfolio_url TEXT,
  phone_e164 VARCHAR(30),
  avatar_url TEXT,
  public_slug VARCHAR(150) UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_hacker_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wallets (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id CHAR(36) NOT NULL,
  type ENUM('customer','hacker') NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_wallet_balance_nonnegative CHECK (balance >= 0),
  CONSTRAINT uq_wallet_user_type UNIQUE (user_id, type),
  CONSTRAINT fk_wallets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wallet_ledger_entries (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  wallet_id CHAR(36) NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  balance_after DECIMAL(14,2) NOT NULL,
  entry_type ENUM('topup','withdrawal','payment_to_platform','payout_to_hacker','refund','fee','adjustment') NOT NULL,
  reference_table VARCHAR(128),
  reference_id CHAR(36),
  description TEXT,
  metadata JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ledger_wallet FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_ledger_wallet_created ON wallet_ledger_entries(wallet_id, created_at);

CREATE TABLE payment_methods (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  customer_id CHAR(36) NOT NULL,
  provider VARCHAR(100) NOT NULL DEFAULT 'demo',
  brand VARCHAR(80) NOT NULL,
  last4 CHAR(4) NOT NULL,
  exp_month SMALLINT,
  exp_year SMALLINT,
  cardholder_name VARCHAR(255),
  label VARCHAR(255),
  is_default BOOLEAN NOT NULL DEFAULT FALSE,
  external_ref VARCHAR(255),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_payment_methods_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_payment_methods_customer ON payment_methods(customer_id);

CREATE TABLE service_offerings (
  code VARCHAR(64) PRIMARY KEY,
  label VARCHAR(255) NOT NULL,
  icon VARCHAR(120),
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bounty_requests (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  public_id VARCHAR(80) NOT NULL UNIQUE,
  customer_id CHAR(36) NOT NULL,
  service_key VARCHAR(64) NOT NULL,
  service_label VARCHAR(255) NOT NULL,
  icon VARCHAR(120),
  price_amount DECIMAL(12,2) NOT NULL,
  priority_text VARCHAR(120),
  deadline DATE,
  scope_details TEXT NOT NULL,
  status ENUM('open','assigned','cancelled','completed') NOT NULL DEFAULT 'open',
  assigned_hacker_id CHAR(36),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_bounty_price_nonnegative CHECK (price_amount >= 0),
  CONSTRAINT fk_bounty_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_bounty_assigned_hacker FOREIGN KEY (assigned_hacker_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_bounty_requests_customer ON bounty_requests(customer_id);
CREATE INDEX idx_bounty_requests_status ON bounty_requests(status);

CREATE TABLE hacker_applications (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  request_id CHAR(36) NOT NULL,
  hacker_id CHAR(36) NOT NULL,
  status ENUM('pending','shortlisted','accepted','rejected','withdrawn') NOT NULL DEFAULT 'pending',
  availability_note TEXT,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP NULL,
  snapshot_rating DECIMAL(3,2),
  snapshot_jobs_done INT,
  snapshot_bio TEXT,
  CONSTRAINT uq_hacker_application UNIQUE (request_id, hacker_id),
  CONSTRAINT fk_hacker_apps_request FOREIGN KEY (request_id) REFERENCES bounty_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_hacker_apps_hacker FOREIGN KEY (hacker_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_hacker_apps_hacker ON hacker_applications(hacker_id);
CREATE INDEX idx_hacker_apps_request ON hacker_applications(request_id);

CREATE TABLE engagements (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  public_id VARCHAR(80) NOT NULL UNIQUE,
  customer_id CHAR(36) NOT NULL,
  hacker_id CHAR(36) NOT NULL,
  bounty_request_id CHAR(36),
  service_type VARCHAR(64) NOT NULL,
  client_display_name VARCHAR(255),
  agreed_price_usd DECIMAL(12,2) NOT NULL DEFAULT 0,
  deadline DATE,
  status ENUM('pending','accepted','in_progress','completed','declined','cancelled') NOT NULL DEFAULT 'pending',
  status_note TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_engagement_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_engagement_hacker FOREIGN KEY (hacker_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_engagement_bounty FOREIGN KEY (bounty_request_id) REFERENCES bounty_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_engagements_customer ON engagements(customer_id);
CREATE INDEX idx_engagements_hacker ON engagements(hacker_id);
CREATE INDEX idx_engagements_status ON engagements(status);

CREATE TABLE vulnerability_reports (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  public_id VARCHAR(80) NOT NULL UNIQUE,
  engagement_id CHAR(36) NOT NULL,
  hacker_id CHAR(36) NOT NULL,
  customer_id CHAR(36) NOT NULL,
  service_type VARCHAR(64) NOT NULL,
  severity_label VARCHAR(64) NOT NULL,
  priority SMALLINT NOT NULL,
  vuln_category VARCHAR(255),
  vuln_subcategory VARCHAR(255),
  vuln_variant VARCHAR(255),
  vuln_category_full VARCHAR(255),
  vuln_subcategory_full VARCHAR(255),
  vuln_variant_full VARCHAR(255),
  vulnerability_path TEXT,
  title VARCHAR(255) NOT NULL,
  url TEXT NOT NULL,
  description TEXT NOT NULL,
  impact TEXT NOT NULL,
  weakness TEXT,
  recommendations TEXT NOT NULL,
  agreed_amount_usd DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('submitted','under_review','edit_requested','approved','paid','rejected','archived') NOT NULL DEFAULT 'submitted',
  edit_note TEXT,
  payment_method VARCHAR(120),
  payment_card_label VARCHAR(255),
  awaiting_review_after_payment BOOLEAN NOT NULL DEFAULT FALSE,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at TIMESTAMP NULL,
  paid_at TIMESTAMP NULL,
  hacker_avatar_url TEXT,
  CONSTRAINT chk_report_priority CHECK (priority BETWEEN 1 AND 5),
  CONSTRAINT fk_report_engagement FOREIGN KEY (engagement_id) REFERENCES engagements(id) ON DELETE CASCADE,
  CONSTRAINT fk_report_hacker FOREIGN KEY (hacker_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_report_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_reports_engagement ON vulnerability_reports(engagement_id);
CREATE INDEX idx_reports_hacker ON vulnerability_reports(hacker_id);
CREATE INDEX idx_reports_customer ON vulnerability_reports(customer_id);
CREATE INDEX idx_reports_status ON vulnerability_reports(status);

CREATE TABLE report_attachments (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  report_id CHAR(36) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  size_bytes BIGINT NOT NULL,
  storage_key VARCHAR(500) NOT NULL,
  checksum_sha256 VARCHAR(128),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_attachment_size_nonnegative CHECK (size_bytes >= 0),
  CONSTRAINT fk_attachment_report FOREIGN KEY (report_id) REFERENCES vulnerability_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_attachments_report ON report_attachments(report_id);

CREATE TABLE reviews (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  public_id VARCHAR(80) NOT NULL UNIQUE,
  report_id CHAR(36) NOT NULL,
  engagement_id CHAR(36) NOT NULL,
  hacker_id CHAR(36) NOT NULL,
  customer_id CHAR(36) NOT NULL,
  client_display_name VARCHAR(255) NOT NULL DEFAULT 'Customer',
  client_company VARCHAR(255),
  rating SMALLINT NOT NULL,
  comment TEXT NOT NULL,
  service_label VARCHAR(255),
  vuln_title VARCHAR(255),
  severity_label VARCHAR(64),
  verified BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_one_review_per_report UNIQUE (report_id),
  CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_review_report FOREIGN KEY (report_id) REFERENCES vulnerability_reports(id) ON DELETE CASCADE,
  CONSTRAINT fk_review_engagement FOREIGN KEY (engagement_id) REFERENCES engagements(id) ON DELETE CASCADE,
  CONSTRAINT fk_review_hacker FOREIGN KEY (hacker_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_review_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_reviews_hacker ON reviews(hacker_id);
CREATE INDEX idx_reviews_report ON reviews(report_id);

CREATE TABLE notifications (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id CHAR(36) NOT NULL,
  channel ENUM('in_app','email','push') NOT NULL DEFAULT 'in_app',
  type VARCHAR(120) NOT NULL,
  title VARCHAR(255),
  body TEXT NOT NULL,
  payload JSON NOT NULL,
  read_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_notifications_user_read ON notifications(user_id, read_at);

CREATE TABLE hacker_skills (
  hacker_id CHAR(36) NOT NULL,
  skill_code VARCHAR(80) NOT NULL,
  PRIMARY KEY (hacker_id, skill_code),
  CONSTRAINT fk_hacker_skills_hacker FOREIGN KEY (hacker_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hacker_tools (
  hacker_id CHAR(36) NOT NULL,
  tool_name VARCHAR(120) NOT NULL,
  PRIMARY KEY (hacker_id, tool_name),
  CONSTRAINT fk_hacker_tools_hacker FOREIGN KEY (hacker_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hacker_languages (
  hacker_id CHAR(36) NOT NULL,
  language VARCHAR(80) NOT NULL,
  PRIMARY KEY (hacker_id, language),
  CONSTRAINT fk_hacker_languages_hacker FOREIGN KEY (hacker_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certifications (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  hacker_id CHAR(36) NOT NULL,
  name VARCHAR(255) NOT NULL,
  issuer VARCHAR(255) NOT NULL,
  issued_on DATE,
  credential_id VARCHAR(255),
  image_url TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_certifications_hacker FOREIGN KEY (hacker_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_certs_hacker ON certifications(hacker_id);

CREATE TABLE audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  actor_user_id CHAR(36),
  action VARCHAR(120) NOT NULL,
  entity_table VARCHAR(120) NOT NULL,
  entity_id CHAR(36),
  old_data JSON,
  new_data JSON,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_audit_entity ON audit_log(entity_table, entity_id);
CREATE INDEX idx_audit_created ON audit_log(created_at);

CREATE OR REPLACE VIEW v_hacker_review_stats AS
SELECT
  hp.user_id,
  hp.handle,
  hp.display_name,
  COUNT(r.id) AS review_count,
  ROUND(AVG(r.rating), 2) AS avg_rating
FROM hacker_profiles hp
LEFT JOIN reviews r ON r.hacker_id = hp.user_id
GROUP BY hp.user_id, hp.handle, hp.display_name;

