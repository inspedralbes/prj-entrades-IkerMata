-- Dades inicials de exemple

-- Netejar dades (OPCIONAL, però útil per reset)
TRUNCATE compres_entrades, reserves_temporals, personal_access_tokens, preus_sessio, seients, sessions, pelis, sales, categories_seients, users RESTART IDENTITY CASCADE;

-- Users de test (contrasenya: password)
-- Hash bcrypt generat amb Hash::make('password') (PHP 8.4); l'antic hash de documentació Laravel no verifica bé.
INSERT INTO users (id, nom, email, password, rol, email_verified_at) VALUES
('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 'Usuari Test', 'test@example.com', '$2y$12$N2Auy7BkTuhUBuuljRsw1OG3YDibzVvt9ZJEPbJhksiK/n8QpsR/C', 'client', NULL),
('b1eebc99-9c0b-4ef8-bb6d-6bb9bd380a21', 'Admin Cine', 'admin@cine.com', '$2y$12$N2Auy7BkTuhUBuuljRsw1OG3YDibzVvt9ZJEPbJhksiK/n8QpsR/C', 'admin', NULL);

-- Categories de seients (només VIP i Normal)
INSERT INTO categories_seients (id, nom, color_hex) VALUES
(1, 'VIP', '#FFD700'),
(2, 'Normal', '#4169E1');

-- Sales
INSERT INTO sales (id, nom, capacitat) VALUES
(1, 'Sala Principal', 50),
(2, 'Sala Petita', 30);

-- Seients Sala Principal (5 files A-E x 10 seients): només la 4a fila (D) és VIP
INSERT INTO seients (id, sala_id, fila, numero, categoria_id) VALUES
(1, 1, 'A', 1, 2), (2, 1, 'A', 2, 2), (3, 1, 'A', 3, 2), (4, 1, 'A', 4, 2), (5, 1, 'A', 5, 2), (6, 1, 'A', 6, 2), (7, 1, 'A', 7, 2), (8, 1, 'A', 8, 2), (9, 1, 'A', 9, 2), (10, 1, 'A', 10, 2),
(11, 1, 'B', 1, 2), (12, 1, 'B', 2, 2), (13, 1, 'B', 3, 2), (14, 1, 'B', 4, 2), (15, 1, 'B', 5, 2), (16, 1, 'B', 6, 2), (17, 1, 'B', 7, 2), (18, 1, 'B', 8, 2), (19, 1, 'B', 9, 2), (20, 1, 'B', 10, 2),
(21, 1, 'C', 1, 2), (22, 1, 'C', 2, 2), (23, 1, 'C', 3, 2), (24, 1, 'C', 4, 2), (25, 1, 'C', 5, 2), (26, 1, 'C', 6, 2), (27, 1, 'C', 7, 2), (28, 1, 'C', 8, 2), (29, 1, 'C', 9, 2), (30, 1, 'C', 10, 2),
(31, 1, 'D', 1, 1), (32, 1, 'D', 2, 1), (33, 1, 'D', 3, 1), (34, 1, 'D', 4, 1), (35, 1, 'D', 5, 1), (36, 1, 'D', 6, 1), (37, 1, 'D', 7, 1), (38, 1, 'D', 8, 1), (39, 1, 'D', 9, 1), (40, 1, 'D', 10, 1),
(41, 1, 'E', 1, 2), (42, 1, 'E', 2, 2), (43, 1, 'E', 3, 2), (44, 1, 'E', 4, 2), (45, 1, 'E', 5, 2), (46, 1, 'E', 6, 2), (47, 1, 'E', 7, 2), (48, 1, 'E', 8, 2), (49, 1, 'E', 9, 2), (50, 1, 'E', 10, 2);

-- Seients Sala Petita (5 files A–E x 6 seients = 30; fila D VIP)
INSERT INTO seients (id, sala_id, fila, numero, categoria_id) VALUES
(51, 2, 'A', 1, 2), (52, 2, 'A', 2, 2), (53, 2, 'A', 3, 2), (54, 2, 'A', 4, 2), (55, 2, 'A', 5, 2), (56, 2, 'A', 6, 2),
(57, 2, 'B', 1, 2), (58, 2, 'B', 2, 2), (59, 2, 'B', 3, 2), (60, 2, 'B', 4, 2), (61, 2, 'B', 5, 2), (62, 2, 'B', 6, 2),
(63, 2, 'C', 1, 2), (64, 2, 'C', 2, 2), (65, 2, 'C', 3, 2), (66, 2, 'C', 4, 2), (67, 2, 'C', 5, 2), (68, 2, 'C', 6, 2),
(69, 2, 'D', 1, 1), (70, 2, 'D', 2, 1), (71, 2, 'D', 3, 1), (72, 2, 'D', 4, 1), (73, 2, 'D', 5, 1), (74, 2, 'D', 6, 1),
(75, 2, 'E', 1, 2), (76, 2, 'E', 2, 2), (77, 2, 'E', 3, 2), (78, 2, 'E', 4, 2), (79, 2, 'E', 5, 2), (80, 2, 'E', 6, 2);

-- Pel·lícules i sessions: sense dades inicials; es creen des del panell admin (o import OMDb) o migracions.
-- Si la cartellera està buida, és normal: cal almenys una fila a `pelis`.

-- Reset de les sequències (taules buides: proper id = 1 per a pelis/sessions/preus)
SELECT setval('pelis_id_seq', 1, false);
SELECT setval('sessions_id_seq', 1, false);
SELECT setval('preus_sessio_id_seq', 1, false);
SELECT setval('sales_id_seq', (SELECT max(id) FROM sales));
SELECT setval('categories_seients_id_seq', (SELECT max(id) FROM categories_seients));
SELECT setval('seients_id_seq', (SELECT max(id) FROM seients));
