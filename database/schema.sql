-- ═══════════════════════════════════════════════════════════════════════════
-- PTjo — Core database schema (PostgreSQL 14+)
-- ═══════════════════════════════════════════════════════════════════════════
-- Maps frontend demo data (localStorage) to a production-ready relational model.
-- Apply:  psql -U postgres -d ptjo -f schema.sql
-- ═══════════════════════════════════════════════════════════════════════════

BEGIN;

-- ── Extensions ───────────────────────────────────────────────────────────────
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "citext";

-- ── Enumerations ─────────────────────────────────────────────────────────────
CREATE TYPE user_role AS ENUM ('customer', 'hacker', 'admin');

CREATE TYPE bounty_request_status AS ENUM (
    'open',
    'assigned',
    'cancelled',
    'completed'
);

CREATE TYPE application_status AS ENUM (
    'pending',
    'shortlisted',
    'accepted',
    'rejected',
    'withdrawn'
);

CREATE TYPE engagement_status AS ENUM (
    'pending',
    'accepted',
    'in_progress',
    'completed',
    'declined',
    'cancelled'
);

CREATE TYPE report_status AS ENUM (
    'submitted',
    'under_review',
    'edit_requested',
    'approved',
    'paid',
    'rejected',
    'archived'
);

CREATE TYPE wallet_type AS ENUM ('customer', 'hacker');

CREATE TYPE ledger_entry_type AS ENUM (
    'topup',
    'withdrawal',
    'payment_to_platform',
    'payout_to_hacker',
    'refund',
    'fee',
    'adjustment'
);

CREATE TYPE notification_channel AS ENUM ('in_app', 'email', 'push');

-- ── Core auth & profiles ─────────────────────────────────────────────────────
CREATE TABLE users (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email           CITEXT NOT NULL UNIQUE,
    password_hash   TEXT NOT NULL,
    role            user_role NOT NULL DEFAULT 'customer',
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    email_verified_at TIMESTAMPTZ,
    last_login_at   TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE customer_profiles (
    user_id         UUID PRIMARY KEY REFERENCES users (id) ON DELETE CASCADE,
    display_name    TEXT NOT NULL,
    company_name    TEXT,
    phone_e164      TEXT,
    avatar_url      TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE hacker_profiles (
    user_id             UUID PRIMARY KEY REFERENCES users (id) ON DELETE CASCADE,
    handle              CITEXT NOT NULL UNIQUE,
    display_name        TEXT NOT NULL,
    professional_title  TEXT,
    bio                 TEXT,
    location            TEXT,
    years_experience    TEXT,
    hourly_rate_usd     NUMERIC(10, 2),
    linkedin_url        TEXT,
    github_url          TEXT,
    portfolio_url       TEXT,
    phone_e164          TEXT,
    avatar_url          TEXT,
    public_slug         CITEXT UNIQUE,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ── Wallets & immutable ledger (audit trail) ─────────────────────────────────
CREATE TABLE wallets (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    type        wallet_type NOT NULL,
    currency    CHAR(3) NOT NULL DEFAULT 'USD',
    balance     NUMERIC(14, 2) NOT NULL DEFAULT 0 CHECK (balance >= 0),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (user_id, type)
);

CREATE TABLE wallet_ledger_entries (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    wallet_id       UUID NOT NULL REFERENCES wallets (id) ON DELETE CASCADE,
    amount          NUMERIC(14, 2) NOT NULL,
    balance_after   NUMERIC(14, 2) NOT NULL,
    entry_type      ledger_entry_type NOT NULL,
    reference_table TEXT,
    reference_id    UUID,
    description     TEXT,
    metadata        JSONB NOT NULL DEFAULT '{}',
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_ledger_wallet_created ON wallet_ledger_entries (wallet_id, created_at DESC);

-- ── Saved payment instruments (store tokens only in production) ──────────────
CREATE TABLE payment_methods (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id     UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    provider        TEXT NOT NULL DEFAULT 'demo',
    brand           TEXT NOT NULL,
    last4           CHAR(4) NOT NULL,
    exp_month       SMALLINT,
    exp_year        SMALLINT,
    cardholder_name TEXT,
    label           TEXT,
    is_default      BOOLEAN NOT NULL DEFAULT FALSE,
    external_ref    TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_payment_methods_customer ON payment_methods (customer_id);

-- ── Reference: service catalog (optional FK from bounty_requests.service_key) ─
CREATE TABLE service_offerings (
    code        TEXT PRIMARY KEY,
    label       TEXT NOT NULL,
    icon        TEXT,
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ── Customer bounty requests (dashboard step 1) ────────────────────────────
CREATE TABLE bounty_requests (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    public_id           TEXT NOT NULL UNIQUE,
    customer_id         UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    service_key         TEXT NOT NULL,
    service_label       TEXT NOT NULL,
    icon                TEXT,
    price_amount        NUMERIC(12, 2) NOT NULL CHECK (price_amount >= 0),
    priority_text       TEXT,
    deadline            DATE,
    scope_details       TEXT NOT NULL,
    status              bounty_request_status NOT NULL DEFAULT 'open',
    assigned_hacker_id  UUID REFERENCES users (id) ON DELETE SET NULL,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_bounty_requests_customer ON bounty_requests (customer_id);
CREATE INDEX idx_bounty_requests_status ON bounty_requests (status);

-- ── Hacker applications to a request ─────────────────────────────────────────
CREATE TABLE hacker_applications (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    request_id          UUID NOT NULL REFERENCES bounty_requests (id) ON DELETE CASCADE,
    hacker_id           UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    status              application_status NOT NULL DEFAULT 'pending',
    availability_note   TEXT,
    applied_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    resolved_at         TIMESTAMPTZ,
    snapshot_rating     NUMERIC(3, 2),
    snapshot_jobs_done  INTEGER,
    snapshot_bio        TEXT,
    UNIQUE (request_id, hacker_id)
);

CREATE INDEX idx_hacker_apps_hacker ON hacker_applications (hacker_id);
CREATE INDEX idx_hacker_apps_request ON hacker_applications (request_id);

-- ── Engagements (active work between customer & hacker) ──────────────────────
CREATE TABLE engagements (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    public_id           TEXT NOT NULL UNIQUE,
    customer_id         UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    hacker_id           UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    bounty_request_id   UUID REFERENCES bounty_requests (id) ON DELETE SET NULL,
    service_type        TEXT NOT NULL,
    client_display_name TEXT,
    agreed_price_usd    NUMERIC(12, 2) NOT NULL DEFAULT 0,
    deadline            DATE,
    status              engagement_status NOT NULL DEFAULT 'pending',
    status_note         TEXT,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_engagements_customer ON engagements (customer_id);
CREATE INDEX idx_engagements_hacker ON engagements (hacker_id);
CREATE INDEX idx_engagements_status ON engagements (status);

-- ── Vulnerability reports ────────────────────────────────────────────────────
CREATE TABLE vulnerability_reports (
    id                      UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    public_id               TEXT NOT NULL UNIQUE,
    engagement_id           UUID NOT NULL REFERENCES engagements (id) ON DELETE CASCADE,
    hacker_id               UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    customer_id             UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    service_type            TEXT NOT NULL,
    severity_label          TEXT NOT NULL,
    priority                SMALLINT NOT NULL CHECK (priority BETWEEN 1 AND 5),
    vuln_category           TEXT,
    vuln_subcategory        TEXT,
    vuln_variant            TEXT,
    vuln_category_full      TEXT,
    vuln_subcategory_full   TEXT,
    vuln_variant_full       TEXT,
    vulnerability_path      TEXT,
    title                   TEXT NOT NULL,
    url                     TEXT NOT NULL,
    description             TEXT NOT NULL,
    impact                  TEXT NOT NULL,
    weakness                TEXT,
    recommendations         TEXT NOT NULL,
    agreed_amount_usd       NUMERIC(12, 2) NOT NULL DEFAULT 0,
    status                  report_status NOT NULL DEFAULT 'submitted',
    edit_note               TEXT,
    payment_method          TEXT,
    payment_card_label      TEXT,
    awaiting_review_after_payment BOOLEAN NOT NULL DEFAULT FALSE,
    submitted_at            TIMESTAMPTZ NOT NULL DEFAULT now(),
    approved_at             TIMESTAMPTZ,
    paid_at                 TIMESTAMPTZ,
    hacker_avatar_url       TEXT
);

CREATE INDEX idx_reports_engagement ON vulnerability_reports (engagement_id);
CREATE INDEX idx_reports_hacker ON vulnerability_reports (hacker_id);
CREATE INDEX idx_reports_customer ON vulnerability_reports (customer_id);
CREATE INDEX idx_reports_status ON vulnerability_reports (status);

-- ── Report file attachments (metadata; blobs in object storage) ──────────────
CREATE TABLE report_attachments (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    report_id       UUID NOT NULL REFERENCES vulnerability_reports (id) ON DELETE CASCADE,
    file_name       TEXT NOT NULL,
    mime_type       TEXT NOT NULL,
    size_bytes      BIGINT NOT NULL CHECK (size_bytes >= 0),
    storage_key     TEXT NOT NULL,
    checksum_sha256 TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_attachments_report ON report_attachments (report_id);

-- ── Verified client reviews (after payment) ────────────────────────────────
CREATE TABLE reviews (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    public_id           TEXT NOT NULL UNIQUE,
    report_id           UUID NOT NULL REFERENCES vulnerability_reports (id) ON DELETE CASCADE,
    engagement_id       UUID NOT NULL REFERENCES engagements (id) ON DELETE CASCADE,
    hacker_id           UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    customer_id         UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    client_display_name TEXT NOT NULL DEFAULT 'Customer',
    client_company      TEXT,
    rating              SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment             TEXT NOT NULL,
    service_label       TEXT,
    vuln_title          TEXT,
    severity_label      TEXT,
    verified            BOOLEAN NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_one_review_per_report UNIQUE (report_id)
);

CREATE INDEX idx_reviews_hacker ON reviews (hacker_id);
CREATE INDEX idx_reviews_report ON reviews (report_id);

-- ── In-app notifications ─────────────────────────────────────────────────────
CREATE TABLE notifications (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    channel     notification_channel NOT NULL DEFAULT 'in_app',
    type        TEXT NOT NULL,
    title       TEXT,
    body        TEXT NOT NULL,
    payload     JSONB NOT NULL DEFAULT '{}',
    read_at     TIMESTAMPTZ,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_notifications_user_unread ON notifications (user_id) WHERE read_at IS NULL;

-- ── Hacker profile: skills, tools, languages (normalized) ──────────────────
CREATE TABLE hacker_skills (
    hacker_id   UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    skill_code  TEXT NOT NULL,
    PRIMARY KEY (hacker_id, skill_code)
);

CREATE TABLE hacker_tools (
    hacker_id   UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    tool_name   TEXT NOT NULL,
    PRIMARY KEY (hacker_id, tool_name)
);

CREATE TABLE hacker_languages (
    hacker_id   UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    language    TEXT NOT NULL,
    PRIMARY KEY (hacker_id, language)
);

-- ── Certifications ───────────────────────────────────────────────────────────
CREATE TABLE certifications (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    hacker_id       UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    name            TEXT NOT NULL,
    issuer          TEXT NOT NULL,
    issued_on       DATE,
    credential_id   TEXT,
    image_url       TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_certs_hacker ON certifications (hacker_id);

-- ── Optional: immutable audit trail for compliance ───────────────────────────
CREATE TABLE audit_log (
    id              BIGSERIAL PRIMARY KEY,
    actor_user_id   UUID REFERENCES users (id) ON DELETE SET NULL,
    action          TEXT NOT NULL,
    entity_table    TEXT NOT NULL,
    entity_id       UUID,
    old_data        JSONB,
    new_data        JSONB,
    ip_address      INET,
    user_agent      TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_audit_entity ON audit_log (entity_table, entity_id);
CREATE INDEX idx_audit_created ON audit_log (created_at DESC);

-- ── updated_at trigger helper ────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_users_updated
    BEFORE UPDATE ON users FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

CREATE TRIGGER tr_customer_profiles_updated
    BEFORE UPDATE ON customer_profiles FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

CREATE TRIGGER tr_hacker_profiles_updated
    BEFORE UPDATE ON hacker_profiles FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

CREATE TRIGGER tr_wallets_updated
    BEFORE UPDATE ON wallets FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

CREATE TRIGGER tr_payment_methods_updated
    BEFORE UPDATE ON payment_methods FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

CREATE TRIGGER tr_bounty_requests_updated
    BEFORE UPDATE ON bounty_requests FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

CREATE TRIGGER tr_engagements_updated
    BEFORE UPDATE ON engagements FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

CREATE TRIGGER tr_certifications_updated
    BEFORE UPDATE ON certifications FOR EACH ROW EXECUTE PROCEDURE set_updated_at();

-- ── View: public hacker review summary (for marketplace / profile) ──────────
CREATE OR REPLACE VIEW v_hacker_review_stats AS
SELECT
    hp.user_id,
    hp.handle,
    hp.display_name,
    COUNT(r.id) AS review_count,
    ROUND(AVG(r.rating)::numeric, 2) AS avg_rating
FROM hacker_profiles hp
LEFT JOIN reviews r ON r.hacker_id = hp.user_id
GROUP BY hp.user_id, hp.handle, hp.display_name;

COMMENT ON TABLE users IS 'Authentication principal; role drives customer vs hacker capabilities.';
COMMENT ON TABLE wallets IS 'One wallet per user per type; all balance changes must go through wallet_ledger_entries.';
COMMENT ON TABLE vulnerability_reports IS 'Maps to localStorage ptjo_reports; files live in report_attachments + object storage.';
COMMENT ON TABLE reviews IS 'Maps to ptjo_reviews; verified=true when tied to paid report flow.';

COMMIT;
