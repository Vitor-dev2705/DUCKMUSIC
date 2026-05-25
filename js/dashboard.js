/**
 * DUCK MUSIC - ARQUIVO MESTRE UNIFICADO
 * Gerencia Player, Favoritos, Tabs e Modal de Playlists
 * Suporta tracks locais (ID numerico) e Deezer (ID dz_XXXXX)
 */

// --- 1. ELEMENTOS DO DOM ---
const audio = document.getElementById('audio-element');
const player = document.getElementById('player');
const playTitle = document.getElementById('player-title');
const playArtist = document.getElementById('player-artist');
const playImg = document.getElementById('player-img');
const btnPlay = document.getElementById('btn-play');
const btnPause = document.getElementById('btn-pause');
const progress = document.getElementById('progress');
const currentTimeEl = document.getElementById('current-time');
const durationEl = document.getElementById('duration');
const playerFavBtn = document.getElementById('player-fav-btn');
const volumeSlider = document.getElementById('volume-slider');
const volumeIcon = document.getElementById('volume-icon');
const toast = document.getElementById('toast');

// --- 2. ESTADO GLOBAL ---
let currentSongIndex = -1;
let songsArray = [];
let lastVolume = parseFloat(localStorage.getItem('duckMusicVolume')) || 0.8;

// Helpers para favoritos Deezer (localStorage)
function isDeezerTrack(id) { return String(id).indexOf('dz_') === 0; }
function getDeezerFavs() {
    try { return JSON.parse(localStorage.getItem('duckDzFavs') || '[]'); }
    catch(e) { return []; }
}
function saveDeezerFavs(arr) {
    localStorage.setItem('duckDzFavs', JSON.stringify(arr));
}

// Favoritos vindos do PHP (locais + Deezer, ja unificados no servidor)
const phpFavoritasRaw = window.APP_DATA ? window.APP_DATA.favoritasIds : [];
const allFavoritas = phpFavoritasRaw.map(String);

function isFavorited(id) {
    return allFavoritas.indexOf(String(id)) !== -1;
}

// --- 3. INICIALIZACAO ---
document.addEventListener('DOMContentLoaded', () => {
    populateSongsArray();
    initTabs();
    initPlayerEvents();
    initPlaylistLogic();
    loadStoredSong();
    updateAllFavoriteIcons();

    // Configura Volume Inicial
    if (audio) {
        audio.volume = lastVolume;
        if (volumeSlider) volumeSlider.value = lastVolume;
    }
});

/**
 * Mapeia todas as musicas presentes na pagina para a fila do player
 */
function populateSongsArray() {
    const items = document.querySelectorAll('.card[data-id], .song-row[data-id], .music-card[data-id]');
    songsArray = Array.from(items).map(item => ({
        id: item.dataset.id,
        audio: item.dataset.audio,
        titulo: item.dataset.titulo,
        artista: item.dataset.artista,
        capa: item.dataset.capa
    }));
}

/**
 * Gerencia as abas da Biblioteca
 */
function initTabs() {
    const tabs = document.querySelectorAll('.library-tab, .tab-btn');
    const contents = document.querySelectorAll('.library-content, .tab-content');

    tabs.forEach(tab => {
        tab.onclick = function() {
            const target = this.getAttribute('data-tab');
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            this.classList.add('active');
            const targetEl = document.getElementById(target);
            if (targetEl) targetEl.classList.add('active');

            populateSongsArray();
        };
    });
}

/**
 * Logica do Modal de Playlist (AJAX)
 */
function initPlaylistLogic() {
    const modal = document.getElementById('modalCriarPlaylist');
    const form = document.getElementById('formCriarPlaylist');
    const btnsAbrir = document.querySelectorAll('.btn-trigger-modal-playlist, #btnAbrirModalSidebar');

    btnsAbrir.forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            if (modal) modal.style.display = "flex";
        };
    });

    document.querySelectorAll('#closeModalCriarPlaylist, #btnCancelarCriarPlaylist, .close-modal').forEach(btn => {
        btn.onclick = () => { if (modal) modal.style.display = "none"; };
    });

    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(form);

            try {
                const response = await fetch('../api/criar_playlist.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    alert(data.message || "Erro ao criar playlist");
                }
            } catch (err) {
                console.error("Erro no envio:", err);
            }
        };
    }
}

/**
 * Gerencia a troca de musicas e estado do Player
 */
function loadSong(index) {
    if (index < 0 || index >= songsArray.length) return;

    currentSongIndex = index;
    const song = songsArray[index];

    audio.src = song.audio;
    playTitle.textContent = song.titulo;
    playArtist.textContent = song.artista || 'Artista Desconhecido';
    playImg.src = song.capa || '/assets/img/capa-padrao.svg';
    playImg.onerror = function() { this.src = '/assets/img/capa-padrao.svg'; };

    if (playerFavBtn) playerFavBtn.setAttribute('data-id', song.id);

    // Mostra o player e toca
    player.style.display = 'flex';
    audio.play().catch(() => console.log("Interacao necessaria para tocar"));

    // Atualiza icone de favorito do player
    updateFavIcon(playerFavBtn, isFavorited(song.id));

    // Muda icone de play/pause no player
    btnPlay.style.display = 'none';
    btnPause.style.display = 'inline-block';

    // Salva estado para restaurar
    try { localStorage.setItem('duckSong', JSON.stringify(song)); } catch(e) {}

    // Media Session API
    if ('mediaSession' in navigator) {
        var artwork = [];
        if (song.capa) {
            artwork = [
                { src: song.capa, sizes: '96x96', type: 'image/jpeg' },
                { src: song.capa, sizes: '256x256', type: 'image/jpeg' },
                { src: song.capa, sizes: '512x512', type: 'image/jpeg' }
            ];
        }
        navigator.mediaSession.metadata = new MediaMetadata({
            title: song.titulo,
            artist: song.artista,
            album: 'DuckMusic',
            artwork: artwork
        });
        navigator.mediaSession.setActionHandler('play', () => audio.play());
        navigator.mediaSession.setActionHandler('pause', () => audio.pause());
        navigator.mediaSession.setActionHandler('previoustrack', () => document.getElementById('btn-prev').click());
        navigator.mediaSession.setActionHandler('nexttrack', () => document.getElementById('btn-next').click());
    }
}

function initPlayerEvents() {
    if (!audio) return;

    btnPlay.onclick = () => audio.play();
    btnPause.onclick = () => audio.pause();

    document.getElementById('btn-next').onclick = () => loadSong((currentSongIndex + 1) % songsArray.length);
    document.getElementById('btn-prev').onclick = () => loadSong((currentSongIndex - 1 + songsArray.length) % songsArray.length);

    audio.onplay = () => {
        btnPlay.style.display = 'none';
        btnPause.style.display = 'inline-block';
        if ('mediaSession' in navigator) navigator.mediaSession.playbackState = 'playing';
    };

    audio.onpause = () => {
        btnPlay.style.display = 'inline-block';
        btnPause.style.display = 'none';
        if ('mediaSession' in navigator) navigator.mediaSession.playbackState = 'paused';
    };

    audio.ontimeupdate = () => {
        if (audio.duration) {
            const perc = (audio.currentTime / audio.duration) * 100;
            progress.style.width = perc + '%';
            currentTimeEl.textContent = formatTime(audio.currentTime);
            durationEl.textContent = formatTime(audio.duration);
        }
    };

    audio.onended = () => document.getElementById('btn-next').click();

    // Click na barra de progresso para seek
    const progressBar = document.getElementById('progress-bar');
    if (progressBar) {
        progressBar.onclick = function(e) {
            if (!audio.duration || !isFinite(audio.duration)) return;
            const rect = this.getBoundingClientRect();
            const pct = (e.clientX - rect.left) / rect.width;
            audio.currentTime = pct * audio.duration;
        };
    }

    if (volumeSlider) {
        volumeSlider.oninput = function() {
            audio.volume = this.value;
            lastVolume = this.value;
            localStorage.setItem('duckMusicVolume', lastVolume);
            updateVolumeIcon(this.value);
        };
    }

    if (volumeIcon) {
        volumeIcon.onclick = function() {
            if (audio.volume > 0) {
                localStorage.setItem('duckVolPrev', audio.volume);
                audio.volume = 0;
                if (volumeSlider) volumeSlider.value = 0;
                updateVolumeIcon(0);
            } else {
                var prev = parseFloat(localStorage.getItem('duckVolPrev')) || 0.8;
                audio.volume = prev;
                if (volumeSlider) volumeSlider.value = prev;
                updateVolumeIcon(prev);
            }
        };
    }

    // Atalhos de teclado
    document.addEventListener('keydown', (e) => {
        const tag = e.target.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

        switch(e.code) {
            case 'Space':
                e.preventDefault();
                audio.paused ? audio.play() : audio.pause();
                break;
            case 'ArrowRight':
                audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + 5);
                break;
            case 'ArrowLeft':
                audio.currentTime = Math.max(0, audio.currentTime - 5);
                break;
        }
    });
}

/**
 * Logica de Favoritos - suporta tracks locais (API) e Deezer (localStorage)
 */
async function toggleFavorite(musicaId, button) {
    const strId = String(musicaId);

    // Busca metadados do track pelo card no DOM
    const card = document.querySelector('[data-id="' + strId + '"]');
    const meta = card ? {
        titulo: card.dataset.titulo || '',
        artista: card.dataset.artista || '',
        capa: card.dataset.capa || '',
        audio: card.dataset.audio || ''
    } : {};

    // Monta body - inclui metadados para Deezer tracks
    let bodyParts = ['musica_id=' + encodeURIComponent(strId)];
    if (isDeezerTrack(strId)) {
        bodyParts.push('titulo=' + encodeURIComponent(meta.titulo));
        bodyParts.push('artista=' + encodeURIComponent(meta.artista));
        bodyParts.push('capa=' + encodeURIComponent(meta.capa));
        bodyParts.push('audio=' + encodeURIComponent(meta.audio));
    }

    // Envia para a API (funciona para AMBOS local e Deezer)
    try {
        const response = await fetch('/api/favoritar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bodyParts.join('&')
        });
        const data = await response.json();

        if (data.status === 'success') {
            const isFav = data.favoritado;

            // Atualiza array local unificado
            var aIdx = allFavoritas.indexOf(strId);
            if (isFav && aIdx === -1) allFavoritas.push(strId);
            if (!isFav && aIdx > -1) allFavoritas.splice(aIdx, 1);

            syncFavIcons(strId, isFav);
            showToast(isFav ? "Adicionado aos favoritos" : "Removido dos favoritos");

            // Se estiver na aba de favoritas e removeu, esconde o card
            if (!isFav && document.querySelector('.tab-btn.active[data-tab="favoritas"], .library-tab.active[data-tab="favoritas"]')) {
                const card = button.closest('.card, .music-card');
                if (card) {
                    card.style.transition = 'opacity 0.3s';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 300);
                }
            }
        }
    } catch (err) {
        console.error("Erro ao favoritar:", err);
        showToast("Erro ao favoritar");
    }
}

/**
 * Dropdown de Playlists (Adicionar musica)
 */
let activeDropdown = null;

function closePlaylistDropdown() {
    if (activeDropdown) { activeDropdown.remove(); activeDropdown = null; }
}

function showPlaylistDropdown(btn) {
    closePlaylistDropdown();
    const card = btn.closest('.card[data-id]');
    if (!card) return;

    const trackId = card.dataset.id;
    const meta = {
        titulo: card.dataset.titulo || '',
        artista: card.dataset.artista || '',
        capa: card.dataset.capa || '',
        audio: card.dataset.audio || ''
    };

    const plItems = document.querySelectorAll('.playlist-item');
    if (plItems.length === 0) { showToast('Crie uma playlist primeiro'); return; }

    const dd = document.createElement('div');
    dd.className = 'playlist-dropdown';
    dd.innerHTML = '<div class="playlist-dropdown-header"><i class="fas fa-plus"></i> Adicionar a playlist</div>';

    plItems.forEach(pl => {
        const href = pl.getAttribute('href') || '';
        const match = href.match(/id=(\d+)/);
        if (!match) return;
        const plId = match[1];
        const plName = pl.textContent.trim();

        const item = document.createElement('div');
        item.className = 'playlist-dropdown-item';
        item.innerHTML = '<i class="fas fa-music"></i> ' + plName;
        item.addEventListener('click', () => {
            addToPlaylist(plId, trackId, meta);
            closePlaylistDropdown();
        });
        dd.appendChild(item);
    });

    const rect = btn.getBoundingClientRect();
    dd.style.top = Math.max(10, rect.top - 10) + 'px';
    dd.style.left = Math.min(rect.left, window.innerWidth - 240) + 'px';
    document.body.appendChild(dd);
    activeDropdown = dd;
}

async function addToPlaylist(playlistId, trackId, meta) {
    let bodyParts = ['id_playlist=' + playlistId, 'musica_id=' + encodeURIComponent(trackId)];
    if (isDeezerTrack(trackId)) {
        bodyParts.push('titulo=' + encodeURIComponent(meta.titulo));
        bodyParts.push('artista=' + encodeURIComponent(meta.artista));
        bodyParts.push('capa=' + encodeURIComponent(meta.capa));
        bodyParts.push('audio=' + encodeURIComponent(meta.audio));
    }
    try {
        const res = await fetch('/api/adicionar_playlist_track.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bodyParts.join('&')
        });
        const data = await res.json();
        showToast(data.message || (data.status === 'success' ? 'Adicionada!' : 'Erro'));
    } catch(e) { showToast('Erro ao adicionar'); }
}

/**
 * Eventos Globais de Clique (Delegacao)
 */
document.addEventListener('click', (e) => {
    const target = e.target;

    // Fechar dropdown
    if (activeDropdown && !target.closest('.playlist-dropdown') && !target.closest('.btn-add-playlist')) {
        closePlaylistDropdown();
    }

    // Botao adicionar a playlist
    const btnAddPl = target.closest('.btn-add-playlist');
    if (btnAddPl) {
        e.preventDefault();
        e.stopPropagation();
        showPlaylistDropdown(btnAddPl);
        return;
    }

    // Botao Favoritar
    const btnFav = target.closest('.btn-fav') || target.closest('#player-fav-btn');
    if (btnFav) {
        e.preventDefault();
        e.stopPropagation();
        toggleFavorite(btnFav.dataset.id, btnFav);
        return;
    }

    // Clique no Card (Tocar Musica)
    const card = target.closest('.card[data-id], .music-card[data-id], .song-row[data-id]');
    if (card) {
        populateSongsArray();
        const id = card.dataset.id;
        const index = songsArray.findIndex(s => s.id === id);
        if (index !== -1) loadSong(index);
    }
});

// --- AUXILIARES ---
function formatTime(s) {
    const min = Math.floor(s / 60);
    const sec = Math.floor(s % 60);
    return `${min}:${sec < 10 ? '0' : ''}${sec}`;
}

function updateFavIcon(btn, isFav) {
    if (!btn) return;
    const icon = btn.querySelector('i');
    if (!icon) return;
    if (isFav) {
        icon.classList.remove('far');
        icon.classList.add('fas', 'favorito');
    } else {
        icon.classList.remove('fas', 'favorito');
        icon.classList.add('far');
    }
}

function syncFavIcons(id, isFav) {
    const strId = String(id);
    document.querySelectorAll('.btn-fav[data-id="' + strId + '"]').forEach(btn => {
        updateFavIcon(btn, isFav);
    });
    if (playerFavBtn && playerFavBtn.getAttribute('data-id') === strId) {
        updateFavIcon(playerFavBtn, isFav);
    }
}

function updateAllFavoriteIcons() {
    document.querySelectorAll('.btn-fav[data-id]').forEach(btn => {
        updateFavIcon(btn, isFavorited(btn.dataset.id));
    });
    // Player favorito
    if (playerFavBtn && playerFavBtn.dataset.id) {
        updateFavIcon(playerFavBtn, isFavorited(playerFavBtn.dataset.id));
    }
}

function updateVolumeIcon(v) {
    if (!volumeIcon) return;
    v = parseFloat(v);
    volumeIcon.className = v === 0 ? 'fas fa-volume-xmark'
                         : v < 0.3 ? 'fas fa-volume-off'
                         : v < 0.7 ? 'fas fa-volume-low'
                         :           'fas fa-volume-high';
}

function showToast(msg) {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => toast.classList.remove('show'), 3000);
}

function loadStoredSong() {
    try {
        const raw = localStorage.getItem('duckSong');
        if (!raw) return;
        const song = JSON.parse(raw);
        // Restaura UI sem tocar
        if (playTitle) playTitle.textContent = song.titulo || 'Selecione uma musica';
        if (playArtist) playArtist.textContent = song.artista || 'DuckMusic';
        if (playImg) {
            playImg.src = song.capa || '/assets/img/capa-padrao.svg';
            playImg.onerror = function() { this.src = '/assets/img/capa-padrao.svg'; };
        }
        if (playerFavBtn) playerFavBtn.setAttribute('data-id', song.id || '');
        if (audio) audio.src = song.audio || '';
        if (player) player.style.display = 'flex';
        updateFavIcon(playerFavBtn, isFavorited(song.id));
    } catch(e) {}
}
