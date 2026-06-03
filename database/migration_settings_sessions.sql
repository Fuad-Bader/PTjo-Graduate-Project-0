-- PTjo — Settings features: multi-session tracking, user preferences,
-- and hacker bounty dismissals. The schema.mysql.sql changes only apply to a
-- fresh DB volume; run this migration against an already-initialized database:
--
--   docker compose exec -T db mysql -uptjo_user -pptjo_pass ptjo < database/migration_settings_sessions.sql
--
-- Re-runnable: CREATE TABLE IF NOT EXISTS makes each step a no-op if applied.

-- ── Active login sessions (for "Active Sessions" + revoke / sign-out-all) ──────
CREATE TABLE IF NOT EXISTS user_sessions (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id CHAR(36) NOT NULL,
  php_session_id VARCHAR(128) NOT NULL,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  revoked_at TIMESTAMP NULL,
  UNIQUE KEY uq_user_sessions_php_sid (php_session_id),
  KEY idx_user_sessions_user (user_id),
  CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Per-user preferences (notification toggles, date/time format) ──────────────
CREATE TABLE IF NOT EXISTS user_preferences (
  user_id CHAR(36) PRIMARY KEY,
  prefs JSON NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Bounties a hacker has "passed"/dismissed (so they stay hidden) ─────────────
CREATE TABLE IF NOT EXISTS hacker_dismissed_bounties (
  hacker_id CHAR(36) NOT NULL,
  bounty_id CHAR(36) NOT NULL,
  dismissed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (hacker_id, bounty_id),
  CONSTRAINT fk_dismissed_hacker FOREIGN KEY (hacker_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_dismissed_bounty FOREIGN KEY (bounty_id) REFERENCES bounty_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
