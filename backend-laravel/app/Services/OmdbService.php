<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Client servidor per a l’API OMDb (clau només a .env; no exposar al navegador).
 *
 * @see https://www.omdbapi.com/
 */
class OmdbService
{
    private const BASE = 'https://www.omdbapi.com/';

    public function __construct(
        private readonly TmdbService $tmdb
    ) {}

    public function apiKeyConfigured(): bool
    {
        $k = config('services.omdb.key');

        return is_string($k) && trim($k) !== '';
    }

    /**
     * Detall d’una pel·lícula per IMDb ID (ex. tt3896198).
     *
     * @return array{titol: string, descripcio: string, imatge_url: string, durada_minuts: int, imdb_id: string}|null
     */
    public function peliculaPerImdbId(string $imdbId): ?array
    {
        $imdbId = strtolower(trim($imdbId));
        if ($imdbId === '' || ! preg_match('/^tt\d+$/', $imdbId)) {
            throw new \InvalidArgumentException('ID IMDb invàlid. Utilitza el format tt1234567.');
        }

        $data = $this->request(['i' => $imdbId, 'plot' => 'full']);
        if ($data === null) {
            return null;
        }
        if (($data['Type'] ?? '') !== 'movie') {
            throw new \InvalidArgumentException('Aquest IMDb ID no correspon a una pel·lícula.');
        }

        return $this->mapMovieToPeli($data, $imdbId);
    }

    /**
     * Crida OMDb amb plot complet i retorna atributs vàlids per a {@see \App\Models\Peli::create()}.
     *
     * @return array{titol: string, descripcio: string, imatge_url: string, durada_minuts: int, estat: string}|null
     */
    public function atributsPerCrearPeliDesOmdb(string $imdbId): ?array
    {
        $imdbId = strtolower(trim($imdbId));
        if ($imdbId === '' || ! preg_match('/^tt\d+$/', $imdbId)) {
            throw new \InvalidArgumentException('ID IMDb invàlid. Utilitza el format tt1234567.');
        }

        $data = $this->request(['i' => $imdbId, 'plot' => 'full']);
        if ($data === null) {
            return null;
        }
        if (($data['Type'] ?? '') !== 'movie') {
            throw new \InvalidArgumentException('Aquest IMDb ID no correspon a una pel·lícula.');
        }

        return $this->peliCreateAttributesFromOmdb($data);
    }

    /**
     * Cerca per títol; retorna llista curta per triar IMDb ID.
     *
     * @return list<array{imdbID: string, Title: string, Year: string, Poster: string}>
     */
    public function cercar(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            throw new \InvalidArgumentException('El text de cerca ha de tenir almenys 2 caràcters.');
        }

        $data = $this->request(['s' => $query, 'page' => 1]);
        if ($data === null) {
            return [];
        }

        $search = $data['Search'] ?? [];
        if (! is_array($search)) {
            return [];
        }

        $out = [];
        foreach ($search as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (($row['Type'] ?? '') !== 'movie') {
                continue;
            }
            $out[] = [
                'imdbID' => (string) ($row['imdbID'] ?? ''),
                'Title' => (string) ($row['Title'] ?? ''),
                'Year' => (string) ($row['Year'] ?? ''),
                'Poster' => (string) ($row['Poster'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    private function request(array $query): ?array
    {
        if (! $this->apiKeyConfigured()) {
            throw new \RuntimeException('OMDB_API_KEY no està configurada al servidor (.env).');
        }

        $query['apikey'] = config('services.omdb.key');

        $response = Http::timeout(20)->acceptJson()->get(self::BASE, $query);

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();
        if (! is_array($json)) {
            return null;
        }

        if (($json['Response'] ?? '') === 'False') {
            return null;
        }

        return $json;
    }

    /**
     * Construeix atributs per a `Peli::create()` des d’un objecte OMDb (resposta completa o fila de Search).
     *
     * @param  array<string, mixed>  $data
     * @return array{titol: string, descripcio: string, imatge_url: string, durada_minuts: int, estat: string}
     */
    public function peliCreateAttributesFromOmdb(array $data): array
    {
        $imdbId = strtolower(trim((string) ($data['imdbID'] ?? $data['imdb_id'] ?? '')));
        $type = strtolower((string) ($data['Type'] ?? 'movie'));
        if ($type !== '' && $type !== 'movie') {
            throw new \InvalidArgumentException('Només s’importen pel·lícules (Type=movie). Rebutjat: '.$type);
        }

        if ($imdbId !== '' && preg_match('/^tt\d+$/', $imdbId)) {
            $tePlot = isset($data['Plot']) && (string) $data['Plot'] !== '' && (string) $data['Plot'] !== 'N/A';
            $teRuntime = isset($data['Runtime']) && (string) $data['Runtime'] !== '' && (string) $data['Runtime'] !== 'N/A';
            if ($tePlot || $teRuntime) {
                $mapped = $this->mapMovieToPeli($data, $imdbId);

                return $this->perEmmagatzemarPeli($mapped);
            }
        }

        return $this->perEmmagatzemarPeli($this->mapSearchRowToPeli($data));
    }

    /**
     * @param  array{titol: string, descripcio: string, imatge_url: string, durada_minuts: int, imdb_id?: string}  $mapped
     * @return array{titol: string, descripcio: string, imatge_url: string, durada_minuts: int, estat: string}
     */
    private function perEmmagatzemarPeli(array $mapped): array
    {
        unset($mapped['imdb_id']);

        $mapped['estat'] = 'actiu';

        return $mapped;
    }

    /**
     * Fila curta de cerca OMDb (sense Plot/Runtime).
     *
     * @param  array<string, mixed>  $data
     * @return array{titol: string, descripcio: string, imatge_url: string, durada_minuts: int, imdb_id: string}
     */
    private function mapSearchRowToPeli(array $data): array
    {
        $title = (string) ($data['Title'] ?? '');
        $year = (string) ($data['Year'] ?? '');
        $imdbId = strtolower(trim((string) ($data['imdbID'] ?? '')));
        $poster = (string) ($data['Poster'] ?? '');
        if ($poster === 'N/A' || $poster === '') {
            $poster = $imdbId !== ''
                ? 'https://picsum.photos/seed/'.rawurlencode($imdbId).'/400/600'
                : 'https://picsum.photos/seed/omdb-search/400/600';
        }

        $titol = trim($title.($year !== '' ? ' ('.$year.')' : ''));

        $mapped = [
            'titol' => $titol !== '' ? $titol : 'Sense títol',
            'descripcio' => $imdbId !== '' ? 'Fitxa IMDb: '.$imdbId : '—',
            'imatge_url' => $poster,
            'durada_minuts' => 90,
            'imdb_id' => $imdbId,
        ];

        if ($imdbId !== '') {
            return $this->enrichAmbTmdbSiCal($mapped, $imdbId);
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{titol: string, descripcio: string, imatge_url: string, durada_minuts: int, imdb_id: string}
     */
    private function mapMovieToPeli(array $data, string $imdbId): array
    {
        $titol = (string) ($data['Title'] ?? '');
        $plot = (string) ($data['Plot'] ?? '');
        if ($plot === 'N/A') {
            $plot = '';
        }

        $poster = (string) ($data['Poster'] ?? '');
        if ($poster === 'N/A' || $poster === '') {
            $poster = 'https://picsum.photos/seed/'.rawurlencode($imdbId).'/400/600';
        }

        $runtime = (string) ($data['Runtime'] ?? '0');
        $minuts = $this->parseRuntimeMinutes($runtime);

        $mapped = [
            'titol' => $titol !== '' ? $titol : 'Sense títol',
            'descripcio' => $plot !== '' ? $plot : '—',
            'imatge_url' => $poster,
            'durada_minuts' => $minuts > 0 ? $minuts : 90,
            'imdb_id' => $imdbId,
        ];

        return $this->enrichAmbTmdbSiCal($mapped, $imdbId);
    }

    /**
     * Prioritza sinopsis (i títol) en català via TMDb; si no n’hi ha, castellà; si no hi ha TMDB_API_KEY, es manté OMDb (anglès).
     *
     * @param  array{titol: string, descripcio: string, imatge_url: string, durada_minuts: int, imdb_id: string}  $mapped
     * @return array{titol: string, descripcio: string, imatge_url: string, durada_minuts: int, imdb_id: string}
     */
    private function enrichAmbTmdbSiCal(array $mapped, string $imdbId): array
    {
        if (! $this->tmdb->apiKeyConfigured()) {
            return $mapped;
        }

        $loc = $this->tmdb->titolISinopsiPreferintCatala($imdbId);
        if ($loc === null) {
            return $mapped;
        }

        if (! empty($loc['descripcio'])) {
            $mapped['descripcio'] = $loc['descripcio'];
        }
        if (! empty($loc['titol'])) {
            $mapped['titol'] = $loc['titol'];
        }

        return $mapped;
    }

    private function parseRuntimeMinutes(string $runtime): int
    {
        if (preg_match('/(\d+)\s*min/i', $runtime, $m)) {
            return (int) $m[1];
        }

        return 0;
    }
}
