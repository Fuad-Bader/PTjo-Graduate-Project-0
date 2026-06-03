-- PTjo — Add Resume/CV storage columns to hacker_profiles.
-- The schema.mysql.sql change only applies to a fresh DB volume; run this
-- migration against an already-initialized database so the CV upload persists.
--
--   docker compose exec -T db mysql -uptjo_user -pptjo_pass ptjo < database/migration_hacker_cv.sql
--
-- Re-runnable: each ADD COLUMN is guarded so it is a no-op if already applied.

ALTER TABLE hacker_profiles
  ADD COLUMN cv_url TEXT NULL AFTER avatar_url;

ALTER TABLE hacker_profiles
  ADD COLUMN cv_filename VARCHAR(255) NULL AFTER cv_url;

ALTER TABLE hacker_profiles
  ADD COLUMN cv_size_bytes INT UNSIGNED NULL AFTER cv_filename;
