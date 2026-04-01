-- Crear tables per PostgreSQL

CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nom VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL DEFAULT 'client',
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT users_rol_check CHECK (rol IN ('client', 'admin'))
);

-- Tokens API Laravel Sanctum (tokenable_id UUID per al model User)
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

CREATE TABLE IF NOT EXISTS pelis (
    id SERIAL PRIMARY KEY,
    uuid UUID UNIQUE NOT NULL DEFAULT gen_random_uuid(),
    titol VARCHAR(255) NOT NULL,
    descripcio TEXT NOT NULL,
    imatge_url VARCHAR(255) NOT NULL,
    durada_minuts INT NOT NULL,
    estat VARCHAR(20) DEFAULT 'actiu' CHECK (estat IN ('actiu', 'inactiu')),
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sales (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    capacitat INT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories_seients (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    color_hex VARCHAR(7) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sessions (
    id SERIAL PRIMARY KEY,
    uuid UUID UNIQUE NOT NULL DEFAULT gen_random_uuid(),
    esdeveniment_id INT NOT NULL REFERENCES pelis(id) ON DELETE CASCADE,
    sala_id INT NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    data_hora TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS seients (
    id SERIAL PRIMARY KEY,
    sala_id INT NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    fila VARCHAR(10) NOT NULL,
    numero INT NOT NULL,
    categoria_id INT NOT NULL REFERENCES categories_seients(id) ON DELETE CASCADE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS preus_sessio (
    id SERIAL PRIMARY KEY,
    sessio_id INT NOT NULL REFERENCES sessions(id) ON DELETE CASCADE,
    categoria_id INT NOT NULL REFERENCES categories_seients(id) ON DELETE CASCADE,
    preu DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reserves_temporals (
    id SERIAL PRIMARY KEY,
    seient_id INT NOT NULL REFERENCES seients(id) ON DELETE CASCADE,
    sessio_id INT NOT NULL REFERENCES sessions(id) ON DELETE CASCADE,
    usuari_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (seient_id, sessio_id)
);

CREATE TABLE IF NOT EXISTS compres_entrades (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    usuari_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    sessio_id INT NOT NULL REFERENCES sessions(id) ON DELETE CASCADE,
    seient_id INT NOT NULL REFERENCES seients(id) ON DELETE CASCADE,
    preu_pagat DECIMAL(10, 2) NOT NULL,
    data_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (sessio_id, seient_id)
);
