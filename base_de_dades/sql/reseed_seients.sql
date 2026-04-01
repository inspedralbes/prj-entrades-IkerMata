-- Actualitza només categories, seients i preus (sense tocar users/pelis/sessions).
-- S'executa a cada arrencada del contenidor app per aplicar el disseny de sala actual.

DELETE FROM compres_entrades WHERE TRUE;
DELETE FROM reserves_temporals WHERE TRUE;
DELETE FROM preus_sessio WHERE TRUE;
DELETE FROM seients WHERE TRUE;
DELETE FROM categories_seients WHERE TRUE;

INSERT INTO categories_seients (id, nom, color_hex) VALUES
(1, 'VIP', '#FFD700'),
(2, 'Normal', '#4169E1');

UPDATE sales SET capacitat = 50 WHERE id = 1;

-- 5 files A-E x 10: només fila D és VIP
INSERT INTO seients (id, sala_id, fila, numero, categoria_id) VALUES
(1, 1, 'A', 1, 2), (2, 1, 'A', 2, 2), (3, 1, 'A', 3, 2), (4, 1, 'A', 4, 2), (5, 1, 'A', 5, 2), (6, 1, 'A', 6, 2), (7, 1, 'A', 7, 2), (8, 1, 'A', 8, 2), (9, 1, 'A', 9, 2), (10, 1, 'A', 10, 2),
(11, 1, 'B', 1, 2), (12, 1, 'B', 2, 2), (13, 1, 'B', 3, 2), (14, 1, 'B', 4, 2), (15, 1, 'B', 5, 2), (16, 1, 'B', 6, 2), (17, 1, 'B', 7, 2), (18, 1, 'B', 8, 2), (19, 1, 'B', 9, 2), (20, 1, 'B', 10, 2),
(21, 1, 'C', 1, 2), (22, 1, 'C', 2, 2), (23, 1, 'C', 3, 2), (24, 1, 'C', 4, 2), (25, 1, 'C', 5, 2), (26, 1, 'C', 6, 2), (27, 1, 'C', 7, 2), (28, 1, 'C', 8, 2), (29, 1, 'C', 9, 2), (30, 1, 'C', 10, 2),
(31, 1, 'D', 1, 1), (32, 1, 'D', 2, 1), (33, 1, 'D', 3, 1), (34, 1, 'D', 4, 1), (35, 1, 'D', 5, 1), (36, 1, 'D', 6, 1), (37, 1, 'D', 7, 1), (38, 1, 'D', 8, 1), (39, 1, 'D', 9, 1), (40, 1, 'D', 10, 1),
(41, 1, 'E', 1, 2), (42, 1, 'E', 2, 2), (43, 1, 'E', 3, 2), (44, 1, 'E', 4, 2), (45, 1, 'E', 5, 2), (46, 1, 'E', 6, 2), (47, 1, 'E', 7, 2), (48, 1, 'E', 8, 2), (49, 1, 'E', 9, 2), (50, 1, 'E', 10, 2);

INSERT INTO preus_sessio (sessio_id, categoria_id, preu) VALUES
(1, 1, 50.00), (1, 2, 20.00),
(2, 1, 50.00), (2, 2, 20.00),
(3, 1, 40.00), (3, 2, 15.00),
(4, 1, 45.00), (4, 2, 18.00);

INSERT INTO compres_entrades (usuari_id, sessio_id, seient_id, preu_pagat) VALUES
('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 1, 5, 20.00),
('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 1, 6, 20.00);

SELECT setval('categories_seients_id_seq', (SELECT COALESCE(MAX(id), 1) FROM categories_seients));
SELECT setval('seients_id_seq', (SELECT COALESCE(MAX(id), 1) FROM seients));
