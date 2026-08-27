-- Addendum: dedicated contact_messages table.
--
-- The public /contact form originally had no dedicated table (out of
-- 02-SCHEMA.sql's locked scope for the v1 build) — messages were appended
-- to storage/logs/contact-*.log and shown read-only in /admin/contact.
-- Promoted to a real table + states now that admin needs to triage and
-- resolve messages instead of just tailing a log file.

CREATE TABLE contact_messages (
  id         BIGINT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  contact    VARCHAR(100) NOT NULL,
  message    VARCHAR(1000) NOT NULL,
  status     VARCHAR(20) NOT NULL DEFAULT 'new' CHECK (status IN ('new','resolved')),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP NULL
);

CREATE INDEX idx_contact_status_created ON contact_messages (status, created_at);
