<?php

namespace App\Services;

use App\Models\Peli;

/**
 * Importació massiva des de JSON (OMDb o format intern amb claus titol, descripcio, …).
 */
class PeliculaImportService
{
    public function __construct(
        protected OmdbService $omdb
    ) {}

    /**
     * @return list<Peli>
     */
    public function importar(array $payload): array
    {
        $rows = $this->expandToRows($payload);
        if (count($rows) === 0) {
            throw new \InvalidArgumentException('Cap entrada per importar (JSON buit o sense dades).');
        }

        $creates = [];
        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                throw new \InvalidArgumentException('L’element '.$i.' no és un objecte JSON.');
            }
            $attrs = $this->rowToAttributes($row);
            $creates[] = Peli::create($attrs);
        }

        return $creates;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function expandToRows(array $payload): array
    {
        if (isset($payload['Search']) && is_array($payload['Search'])) {
            return array_values(array_filter($payload['Search'], 'is_array'));
        }
        if (isset($payload['peliculas']) && is_array($payload['peliculas'])) {
            return array_values($payload['peliculas']);
        }
        if (array_is_list($payload)) {
            return $payload;
        }

        return [$payload];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function rowToAttributes(array $row): array
    {
        if (isset($row['Title'])) {
            return $this->omdb->peliCreateAttributesFromOmdb($row);
        }
        if (isset($row['titol']) && is_string($row['titol']) && trim($row['titol']) !== '') {
            return $this->attributesFromInternal($row);
        }

        throw new \InvalidArgumentException('Cada fila ha de tenir «Title» (OMDb) o «titol» (format intern).');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{titol: string, descripcio: string, imatge_url: string, durada_minuts: int, estat: string}
     */
    private function attributesFromInternal(array $row): array
    {
        $titol = trim((string) $row['titol']);
        $desc = isset($row['descripcio']) ? trim((string) $row['descripcio']) : '';
        if ($desc === '') {
            $desc = '—';
        }

        $img = isset($row['imatge_url']) ? trim((string) $row['imatge_url']) : '';
        if ($img === '') {
            $img = 'https://picsum.photos/seed/'.rawurlencode(substr(md5($titol), 0, 12)).'/400/600';
        }

        $min = isset($row['durada_minuts']) ? (int) $row['durada_minuts'] : 90;
        if ($min < 1) {
            $min = 90;
        }

        $estat = isset($row['estat']) && in_array($row['estat'], ['actiu', 'inactiu'], true)
            ? $row['estat']
            : 'actiu';

        return [
            'titol' => $titol,
            'descripcio' => $desc,
            'imatge_url' => $img,
            'durada_minuts' => $min,
            'estat' => $estat,
        ];
    }
}
