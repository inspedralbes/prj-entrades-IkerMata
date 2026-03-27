-- Dades inicials de exemple

-- Netejar dades (OPCIONAL, però útil per reset)
TRUNCATE users, categories_seients, sales, pelis, sessions, seients, preus_sessio CASCADE;

-- Users de test
INSERT INTO users (id, name, email, password, email_verified_at) VALUES
('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 'Usuari Test', 'test@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL);

-- Categories de seients
INSERT INTO categories_seients (id, nom, color_hex) VALUES
(1, 'VIP', '#FFD700'),
(2, 'Platea', '#4169E1'),
(3, 'General', '#228B22');

-- Sales
INSERT INTO sales (id, nom, capacitat) VALUES
(1, 'Sala Principal', 100),
(2, 'Sala Petita', 50);

-- Seients Sala Principal (3 files x 10 seients)
INSERT INTO seients (id, sala_id, fila, numero, categoria_id) VALUES
(1, 1, 'A', 1, 1), (2, 1, 'A', 2, 1), (3, 1, 'A', 3, 2), (4, 1, 'A', 4, 2), (5, 1, 'A', 5, 2), (6, 1, 'A', 6, 2), (7, 1, 'A', 7, 2), (8, 1, 'A', 8, 2), (9, 1, 'A', 9, 1), (10, 1, 'A', 10, 1),
(11, 1, 'B', 1, 1), (12, 1, 'B', 2, 1), (13, 1, 'B', 3, 2), (14, 1, 'B', 4, 2), (15, 1, 'B', 5, 2), (16, 1, 'B', 6, 2), (17, 1, 'B', 7, 2), (18, 1, 'B', 8, 2), (19, 1, 'B', 9, 1), (20, 1, 'B', 10, 1),
(21, 1, 'C', 1, 2), (22, 1, 'C', 2, 2), (23, 1, 'C', 3, 3), (24, 1, 'C', 4, 3), (25, 1, 'C', 5, 3), (26, 1, 'C', 6, 3), (27, 1, 'C', 7, 3), (28, 1, 'C', 8, 3), (29, 1, 'C', 9, 2), (30, 1, 'C', 10, 2);

-- Pelicules
INSERT INTO pelis (id, uuid, titol, descripcio, imatge_url, durada_minuts, estat) VALUES
(1, 'b1eebc99-9c0b-4ef8-bb6d-6bb9bd380a12', 'El Padrino', 'Saga mafiosa de Francis Ford Coppola', 'https://m.media-amazon.com/images/M/MV5BM2MyNjYxNmUtYTAwNi00MTYxLWJmNWYtYzZlODY3ZTk3OTFlXkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_.jpg', 175, 'actiu'),
(2, 'c2eebc99-9c0b-4ef8-bb6d-6bb9bd380a13', 'Star Wars', 'Aventures a una galaxia molt llunyana', 'https://m.media-amazon.com/images/M/MV5BOTA5NjhiOTAtZWM0ZC00MWNhLWE1N2QtMDI4NWZkMWY0NGRhXkEyXkFqcGdeQXVyNDAzNDk0MTQ@._V1_.jpg', 121, 'actiu'),
(3, 'd3eebc99-9c0b-4ef8-bb6d-6bb9bd380a14', 'Inception', 'Somnis dins de somnis', 'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_.jpg', 148, 'actiu'),
(4, 'e4eebc99-9c0b-4ef8-bb6d-6bb9bd380a15', 'Titanic', 'Una historia de amor a l''Atlantic', 'https://m.media-amazon.com/images/M/MV5BMDdmZGU3NDQtY2E5My00ZTliLWIzOTUtOTU4Zjg3YjZlNWVjXkEyXkFqcGdeQXVyNTA4NzY1MzY@._V1_.jpg', 194, 'actiu');

-- Sessions
INSERT INTO sessions (id, uuid, esdeveniment_id, sala_id, data_hora) VALUES
(1, 'd3eebc99-9c0b-4ef8-bb6d-6bb9bd380a20', 1, 1, '2026-04-01 20:00:00'),
(2, 'e4eebc99-9c0b-4ef8-bb6d-6bb9bd380a21', 2, 1, '2026-04-02 22:00:00'),
(3, 'f5eebc99-9c0b-4ef8-bb6d-6bb9bd380a22', 3, 2, '2026-04-03 19:00:00'),
(4, 'g6eebc99-9c0b-4ef8-bb6d-6bb9bd380a24', 4, 2, '2026-04-04 18:00:00');

-- Preus per sessio
INSERT INTO preus_sessio (sessio_id, categoria_id, preu) VALUES
(1, 1, 50.00), (1, 2, 35.00), (1, 3, 20.00),
(2, 1, 50.00), (2, 2, 35.00), (2, 3, 20.00),
(3, 1, 40.00), (3, 2, 25.00), (3, 3, 15.00),
(4, 1, 45.00), (4, 2, 30.00), (4, 3, 18.00);

-- Reset de les sequencies
SELECT setval('pelis_id_seq', (SELECT max(id) FROM pelis));
SELECT setval('sessions_id_seq', (SELECT max(id) FROM sessions));
SELECT setval('sales_id_seq', (SELECT max(id) FROM sales));
SELECT setval('categories_seients_id_seq', (SELECT max(id) FROM categories_seients));
SELECT setval('seients_id_seq', (SELECT max(id) FROM seients));
