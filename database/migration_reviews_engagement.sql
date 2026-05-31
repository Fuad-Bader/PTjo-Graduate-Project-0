-- ─────────────────────────────────────────────────────────────
-- PTjo — Migration: engagement-completion reviews
-- Allows a customer to review a hacker after a job (engagement) is
-- completed, without requiring a linked vulnerability report.
--   • report_id  → nullable (review may stand on its own)
--   • comment    → nullable (an optional written comment)
--   • recommended→ new flag: "would you recommend this pentester?"
--   • one review per engagement (a job), enforced by a unique index
-- Safe to run more than once is NOT guaranteed; run on an existing DB once.
-- ─────────────────────────────────────────────────────────────

ALTER TABLE reviews
  MODIFY report_id CHAR(36) NULL,
  MODIFY comment   TEXT     NULL,
  ADD COLUMN recommended TINYINT(1) NOT NULL DEFAULT 1 AFTER rating;

-- A job can be reviewed once. (report_id stays unique too, but NULLs are
-- allowed to repeat, so engagement-based reviews don't collide there.)
ALTER TABLE reviews
  ADD CONSTRAINT uq_one_review_per_engagement UNIQUE (engagement_id);
