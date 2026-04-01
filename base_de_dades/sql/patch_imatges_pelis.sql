-- Actualitza URLs de cartell sense caràcter @ (evita 404 en el navegador). Executar manualment si la base ja existia.

UPDATE pelis SET imatge_url = 'https://picsum.photos/seed/padrino/400/600' WHERE id = 1;
UPDATE pelis SET imatge_url = 'https://picsum.photos/seed/starwars/400/600' WHERE id = 2;
UPDATE pelis SET imatge_url = 'https://picsum.photos/seed/inception/400/600' WHERE id = 3;
UPDATE pelis SET imatge_url = 'https://picsum.photos/seed/titanic/400/600' WHERE id = 4;
