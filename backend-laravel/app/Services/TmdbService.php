<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * The Movie Database (TMDb) — permet sinopsis i títol localitzats (p. ex. català).
 * Clau gratuïta: https://www.themoviedb.org/settings/api
 *
 * OMDb només ofereix la trama en anglès; aquest servei complementa la importació.
 *
 * @see https://developer.themoviedb.org/reference/find-by-id
 */
class TmdbService
{
    private const BASE = 'https://api.themoviedb.org/3';

    public function apiKeyConfigured(): bool
    {
        $k = config('services.tmdb.key');

        return is_string($k) && trim($k) !== '';
    }

    /**
     * Intenta obtenir títol i sinopsis prioritzant català, després castellà (si no hi ha text en ca).
     *
     * @return array{titol: string|null, descripcio: string|null}|null null si no hi ha clau, no es troba la pel·lícula o no hi ha text útil
     */
    public function titolISinopsiPreferintCatala(string $imdbId): ?array
    {
        if (! $this->apiKeyConfigured()) {
            return null;
        }

        $imdbId = strtolower(trim($imdbId));
        if ($imdbId === '' || ! preg_match('/^tt\d+$/', $imdbId)) {
            return null;
        }

        $tmdbMovieId = $this->findMovieIdByImdbId($imdbId);
        if ($tmdbMovieId === null) {
            return null;
        }

        $ca = $this->movieDetails($tmdbMovieId, 'ca');
        $titol = $this->pickTitle($ca);
        $descripcio = $this->pickOverview($ca);

        if (($descripcio === null || $descripcio === '') || ($titol === null || $titol === '')) {
            $es = $this->movieDetails($tmdbMovieId, 'es');
            if ($descripcio === null || $descripcio === '') {
                $descripcio = $this->pickOverview($es);
            }
            if ($titol === null || $titol === '') {
                $titol = $this->pickTitle($es);
            }
        }

        if (($titol === null || $titol === '') && ($descripcio === null || $descripcio === '')) {
            return null;
        }

        return [
            'titol' => $titol !== '' ? $titol : null,
            'descripcio' => $descripcio !== '' ? $descripcio : null,
        ];
    }

    private function findMovieIdByImdbId(string $imdbId): ?int
    {
        $data = $this->getJson('/find/'.$imdbId, [
            'external_source' => 'imdb_id',
        ]);
        if ($data === null) {
            return null;
        }

        $movies = $data['movie_results'] ?? [];
        if (! is_array($movies) || $movies === []) {
            return null;
        }

        $first = $movies[0] ?? null;
        if (! is_array($first)) {
            return null;
        }

        $id = $first['id'] ?? null;

        return is_int($id) ? $id : (is_numeric($id) ? (int) $id : null);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function movieDetails(int $tmdbMovieId, string $language): ?array
    {
        return $this->getJson('/movie/'.$tmdbMovieId, [
            'language' => $language,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function pickTitle(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }
        $t = trim((string) ($data['title'] ?? ''));
        if ($t === '' || strcasecmp($t, 'N/A') === 0) {
            return null;
        }

        return $t;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function pickOverview(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }
        $o = trim((string) ($data['overview'] ?? ''));
        if ($o === '' || strcasecmp($o, 'N/A') === 0) {
            return null;
        }

        return $o;
    }

    /**
     * @param  array<string, string|int>  $query
     * @return array<string, mixed>|null
     */
    private function getJson(string $path, array $query): ?array
    {
        $query['api_key'] = config('services.tmdb.key');

        $response = Http::timeout(20)->acceptJson()->get(self::BASE.$path, $query);

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }
}
