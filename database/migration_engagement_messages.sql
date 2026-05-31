-- PTjo — Chat between customer and hacker on an engagement.
-- Run once against an existing DB:
--   docker compose exec -T db mysql -uptjo_user -pptjo_pass ptjo < database/migration_engagement_messages.sql
-- (Fresh installs get this automatically via schema.mysql.sql.)

CREATE TABLE IF NOT EXISTS engagement_messages (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  engagement_id CHAR(36) NOT NULL,
  sender_id CHAR(36) NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL,
  CONSTRAINT fk_msg_engagement FOREIGN KEY (engagement_id) REFERENCES engagements(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_eng_messages_engagement ON engagement_messages(engagement_id, created_at);
