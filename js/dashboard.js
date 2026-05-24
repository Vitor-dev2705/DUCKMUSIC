/**
 * DUCK MUSIC - ARQUIVO MESTRE UNIFICADO
 * Gerencia Player, Favoritos, Tabs e Modal de Playlists
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
// Pega os IDs favoritos do PHP (se existirem) ou inicia vazio
const phpFavoritas = window.APP_DATA ? window.APP_DATA.favoritasIds : [];

// --- 3. INICIALIZAÇÃO ---
document.addEventListener('DOMContentLoaded', () => {
    populateSongsArray();
    initTabs();
    initPlayerEvents();
    initPlaylistLogic();
    loadStoredSong();
    
    // Configura Volume Inicial
    if (audio) {
        audio.volume = lastVolume;
        if (volumeSlider) volumeSlider.value = lastVolume;
    }
});

/**
 * Mapeia todas as músicas presentes na página para a fila do player
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
            
            // Recarrega a fila se mudar de aba
            populateSongsArray();
        };
    });
}

/**
 * Lógica do Modal de Playlist (AJAX)
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
 * Gerencia a troca de músicas e estado do Player
 */
function loadSong(index) {
    if (index < 0 || index >= songsArray.length) return;

    currentSongIndex = index;
    const song = songsArray[index];

    audio.src = song.audio;
    playTitle.textContent = song.titulo;
    playArtist.textContent = song.artista || 'Artista Desconhecido';
    playImg.src = song.capa || '../assets/img/capa-padrao.jpg';
    
    if (playerFavBtn) playerFavBtn.setAttribute('data-id', song.id);
    
    // Mostra o player e toca
    player.style.display = 'flex';
    audio.play().catch(() => console.log("Interação necessária para tocar"));
    
    updateAllFavoriteIcons(song.id, phpFavoritas.includes(parseInt(song.id)));
    
    // Muda ícone de play/pause no player
    btnPlay.style.display = 'none';
    btnPause.style.display = 'inline-block';
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
    };

    audio.onpause = () => {
        btnPlay.style.display = 'inline-block';
        btnPause.style.display = 'none';
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

    if (volumeSlider) {
        volumeSlider.oninput = function() {
            audio.volume = this.value;
            lastVolume = this.value;
            localStorage.setItem('duckMusicVolume', lastVolume);
            updateVolumeIcon(this.value);
        };
    }
}

/**
 * Lógica de Favoritos (AJAX)
 */
async function toggleFavorite(musicaId, button) {
    try {
        const response = await fetch('../api/favoritar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'musica_id=' + encodeURIComponent(musicaId)
        });
        const data = await response.json();

        if (data.status === 'success') {
            const isFav = data.favoritado;
            const numId = parseInt(musicaId);

            // Atualiza array local de favoritos
            if (isFav) {
                if (!phpFavoritas.includes(numId)) phpFavoritas.push(numId);
            } else {
                const idx = phpFavoritas.indexOf(numId);
                if (idx > -1) phpFavoritas.splice(idx, 1);
            }

            updateAllFavoriteIcons(musicaId, isFav);
            showToast(isFav ? "Adicionado aos favoritos" : "Removido dos favoritos");

            // Se estiver na aba de favoritas e removeu, esconde o card
            if (!isFav && document.querySelector('.tab-btn.active[data-tab="favoritas"], .library-tab.active[data-tab="favoritas"]')) {
                const card = button.closest('.card, .music-card');
                if (card) card.style.display = 'none';
            }
        }
    } catch (err) {
        console.error("Erro ao favoritar:", err);
    }
}

/**
 * Eventos Globais de Clique (Delegação)
 */
document.addEventListener('click', (e) => {
    const target = e.target;

    // Botão Favoritar
    const btnFav = target.closest('.btn-fav') || target.closest('#player-fav-btn');
    if (btnFav) {
        e.preventDefault();
        e.stopPropagation();
        toggleFavorite(btnFav.dataset.id, btnFav);
        return;
    }

    // Clique no Card (Tocar Música)
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

function updateAllFavoriteIcons(id, isFav) {
    document.querySelectorAll(`[data-id="${id}"] .fa-heart, #player-fav-btn[data-id="${id}"] .fa-heart`).forEach(icon => {
        icon.classList.toggle('fas', isFav);
        icon.classList.toggle('far', !isFav);
        icon.classList.toggle('favorito', isFav);
    });
}

function updateVolumeIcon(v) {
    if (!volumeIcon) return;
    volumeIcon.className = v == 0 ? 'fas fa-volume-mute' : (v < 0.5 ? 'fas fa-volume-down' : 'fas fa-volume-up');
}

function showToast(msg) {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function loadStoredSong() {
    const stored = localStorage.getItem('playingSong');
    if (stored) {
        localStorage.removeItem('playingSong'); // Limpa para não tocar toda vez que der F5
        const songData = JSON.parse(stored);
        populateSongsArray();
        const idx = songsArray.findIndex(s => s.id == songData.id);
        if (idx !== -1) loadSong(idx);
    }
}