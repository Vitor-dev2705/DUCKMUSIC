<?php
/**
 * Helper para integrar com a API do Deezer.
 * Busca musicas, artistas, charts e generos.
 */

function deezerGet($endpoint, $params = []) {
    $url = 'https://api.deezer.com/' . ltrim($endpoint, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "Accept: application/json\r\n"
        ]
    ]);

    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) return null;

    return json_decode($json, true);
}

/**
 * Busca tracks por termo de pesquisa
 */
function deezerBuscar($termo, $limite = 20) {
    $data = deezerGet('search', ['q' => $termo, 'limit' => $limite]);
    return formatarTracks($data['data'] ?? []);
}

/**
 * Busca tracks populares de um genero
 * Generos Deezer: 116=Rap, 197=Funk Carioca, 85=Sertanejo, 106=Electro, 466=Forro
 */
function deezerPorGenero($generoId, $limite = 10) {
    $data = deezerGet("genre/{$generoId}/artists", ['limit' => 5]);
    $tracks = [];

    if (!empty($data['data'])) {
        foreach ($data['data'] as $artista) {
            $topData = deezerGet("artist/{$artista['id']}/top", ['limit' => 3]);
            if (!empty($topData['data'])) {
                $tracks = array_merge($tracks, $topData['data']);
            }
            if (count($tracks) >= $limite) break;
        }
    }

    return formatarTracks(array_slice($tracks, 0, $limite));
}

/**
 * Busca chart (mais populares global)
 */
function deezerChart($limite = 10) {
    $data = deezerGet('chart/0/tracks', ['limit' => $limite]);
    return formatarTracks($data['data'] ?? []);
}

/**
 * Busca tracks de uma playlist Deezer publica
 */
function deezerPlaylist($playlistId, $limite = 20) {
    $data = deezerGet("playlist/{$playlistId}/tracks", ['limit' => $limite]);
    return formatarTracks($data['data'] ?? []);
}

/**
 * Formata a resposta do Deezer para o formato padrao do DuckMusic
 */
function formatarTracks($tracks) {
    $resultado = [];
    foreach ($tracks as $t) {
        if (empty($t['preview'])) continue; // Pula tracks sem preview

        $resultado[] = [
            'id'            => 'dz_' . $t['id'],
            'deezer_id'     => $t['id'],
            'titulo'        => $t['title'] ?? $t['title_short'] ?? 'Sem titulo',
            'nome_artista'  => $t['artist']['name'] ?? 'Artista Desconhecido',
            'artista_id'    => $t['artist']['id'] ?? 0,
            'album_titulo'  => $t['album']['title'] ?? '',
            'caminho_arquivo' => $t['preview'],
            'caminho_capa'  => $t['album']['cover_medium'] ?? $t['album']['cover'] ?? '/assets/img/capa-padrao.svg',
            'capa_grande'   => $t['album']['cover_big'] ?? $t['album']['cover_medium'] ?? '',
            'duracao'       => $t['duration'] ?? 30,
        ];
    }
    return $resultado;
}

/**
 * Mapa de generos DuckMusic -> Deezer genre IDs
 */
function getGenerosMap() {
    return [
        'FUNK'       => ['id' => 197, 'busca' => 'funk brasileiro'],
        'RAP'        => ['id' => 116, 'busca' => 'rap brasileiro'],
        'SERTANEJO'  => ['id' => 85,  'busca' => 'sertanejo'],
        'FORRO'      => ['id' => 466, 'busca' => 'forro'],
        'ELETROFUNK' => ['id' => 106, 'busca' => 'eletrofunk'],
        'POP'        => ['id' => 132, 'busca' => 'pop brasil'],
    ];
}
