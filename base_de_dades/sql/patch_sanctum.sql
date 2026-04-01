-- Patch manual si la base ja existia sense taula Sanctum ni columnes nom/rol.
-- Revisar errors abans d'executar en producció.

ALTER TABLE users ADD COLUMN IF NOT EXISTS rol VARCHAR(20) NOT NULL DEFAULT 'client';

DO $$
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'name'
  ) THEN
    ALTER TABLE users RENAME COLUMN name TO nom;
  END IF;
END $$;

CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id UUID NOT NULL,
    name TEXT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE INDEX IF NOT EXISTS personal_access_tokens_tokenable_type_tokenable_id_index
    ON personal_access_tokens (tokenable_type, tokenable_id);

CREATE INDEX IF NOT EXISTS personal_access_tokens_expires_at_index
    ON personal_access_tokens (expires_at);
