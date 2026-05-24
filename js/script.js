/**
 * DUCK MUSIC - ARQUIVO MESTRE DE INTERATIVIDADE
 * Unifica: Player, Favoritos, Biblioteca (Tabs) e Playlists
 */

// --- 1. ELEMENTOS DO DOM (PLAYER) ---
const audio = document.getElementById('audio-element');
const player = document.getElementById('player');
const playTitle = document.getElementById('player-title');
const playArtist = document.getElementById('player-artist');
const playImg = document.getElementById('player-img');
const btnPlay = document.getElementById('btn-play');
const btnPause = document.getElementById('btn-pause');
const progressBarContainer = document.getElementById('progress-bar');
const progress = document.getElementById('progress');
const currentTimeEl = document.getElementById('current-time');
const durationEl = document.getElementById('duration');
const playerFavBtn = document.getElementById('player-fav-btn');
const volumeSlider = document.getElementById('volume-slider');
const volumeIcon = document.getElementById('volume-icon');
const toast = document.getElementById('toast');

// --- 2. ESTADO GLOBAL ---
let currentSongData = null;
let songsArray = [];
let currentSongIndex = -1;
let isFading = false;
const fadeDuration = 5;
const phpFavoritas = window.APP_DATA ? window.APP_DATA.favoritasIds : [];
let lastVolume = parseFloat(localStorage.getItem('duckMusicVolume')) || 0.8;

// --- 3. INICIALIZAÇÃO E ABAS ---
document.addEventListener('DOMContentLoaded', () => {
    populateSongsArray();
    initTabs();
    initVolume();
    loadStoredSong();

    // Lógica de Abas da Biblioteca
    function initTabs() {
        const tabs = document.querySelectorAll('.library-tab');
        const contents = document.querySelectorAll('.library-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                const targetContent = document.getElementById(targetTab);
                if (targetContent) targetContent.classList.add('active');
            });
        });
    }
});

// --- 4. FUNÇÕES DO PLAYER ---

function populateSongsArray() {
    songsArray = Array.from(document.querySelectorAll('.card[data-id], .song-row[data-id]')).map(item => ({
        id: item.dataset.id,
        audio: item.dataset.audio,
        titulo: item.dataset.titulo,
        artista: item.dataset.artista,
        capa: item.dataset.capa
    }));
}

function loadSong(index) {
    if (!songsArray || index < 0 || index >= songsArray.length) return;
    
    isFading = false;
    audio.volume = lastVolume;
    currentSongIndex = index;
    currentSongData = songsArray[index];

    audio.src = currentSongData.audio;
    playTitle.textContent = currentSongData.titulo;
    playArtist.textContent = currentSongData.artista || 'Artista Desconhecido';
    playImg.src = currentSongData.capa || 'capa-padrao.jpg';
    playImg.onerror = function() { this.src = 'capa-padrao.jpg'; };

    playerFavBtn.setAttribute('data-id', currentSongData.id);
    updateAllFavoriteIcons(currentSongData.id, phpFavoritas.includes(parseInt(currentSongData.id)));

    player.style.display = 'flex';
    audio.play().catch(err => showToast("Erro ao reproduzir áudio"));
}

// Controle de Volume e UI
function initVolume() {
    audio.volume = lastVolume;
    if(volumeSlider) volumeSlider.value = lastVolume;
    updateVolumeIcon();
}

btnPlay.onclick = () => { audio.src ? audio.play() : (songsArray.length > 0 && loadSong(0)); };
btnPause.onclick = () => { audio.pause(); };

audio.onplay = () => { btnPlay.style.display = 'none'; btnPause.style.display = 'flex'; player.classList.add('playing'); };
audio.onpause = () => { btnPause.style.display = 'none'; btnPlay.style.display = 'flex'; player.classList.remove('playing'); };

audio.onended = () => {
    if (songsArray.length > 0 && currentSongIndex !== -1) {
        loadSong((currentSongIndex + 1) % songsArray.length);
    }
};

audio.ontimeupdate = () => {
    if (audio.duration) {
        progress.style.width = ((audio.currentTime / audio.duration) * 100) + '%';
        currentTimeEl.textContent = formatTime(audio.currentTime);
        
        // Fade-out automático
        const remaining = audio.duration - audio.currentTime;
        if (remaining <= fadeDuration && !audio.muted) {
            isFading = true;
            audio.volume = lastVolume * (remaining / fadeDuration);
        }
    }
};

audio.onloadedmetadata = () => { durationEl.textContent = formatTime(audio.duration); };

// --- 5. LOGICA DE FAVORITOS (GLOBAL) ---

async function toggleFavorite(musicaId, button = null) {
    try {
        const response = await fetch('../acoes/favoritar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `musica_id=${encodeURIComponent(musicaId)}`
        });
        const data = await response.json();

        if (data.status === 'success') {
            const isFav = data.favoritado;
            updateAllFavoriteIcons(musicaId, isFav);
            showToast(isFav ? 'Música favoritada!' : 'Removida dos favoritos');

            const numId = parseInt(musicaId);
            isFav ? phpFavoritas.push(numId) : phpFavoritas.splice(phpFavoritas.indexOf(numId), 1);

            // Efeito visual na aba Favoritas
            if (!isFav && button) {
                const card = button.closest('.card');
                if (card && document.querySelector('.library-tab.active[data-tab="favoritas"]')) {
                    card.style.opacity = '0';
                    setTimeout(() => { card.remove(); checkEmptyState('favoritas'); }, 300);
                }
            }
        }
    } catch (error) { console.error("Erro ao favoritar:", error); }
}

function updateAllFavoriteIcons(musicaId, isFavorited) {
    document.querySelectorAll(`.btn-fav[data-id="${musicaId}"], #player-fav-btn[data-id="${musicaId}"]`).forEach(btn => {
        const icon = btn.querySelector('i');
        icon.classList.toggle('fas', isFavorited);
        icon.classList.toggle('far', !isFavorited);
        icon.classList.toggle('favorito', isFavorited);
    });
}

// --- 6. LOGICA DO MODAL DE PLAYLIST ---

const modalPlaylist = document.getElementById('modalCriarPlaylist');
const formPlaylist = document.getElementById('formCriarPlaylist');
const feedbackPlaylist = document.getElementById('modalFeedback');

document.querySelectorAll('.btn-trigger-modal-playlist, #btnAbrirModalCriarPlaylist, #btnAbrirModalCriarPlaylistSidebar').forEach(btn => {
    btn.onclick = (e) => {
        e.preventDefault();
        modalPlaylist.style.display = "flex";
        formPlaylist.reset();
        showFeedback('', '');
    };
});

document.querySelectorAll('#closeModalCriarPlaylist, #btnCancelarCriarPlaylist').forEach(btn => {
    btn.onclick = () => modalPlaylist.style.display = "none";
});

if (formPlaylist) {
    formPlaylist.onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(formPlaylist);
        
        try {
            const response = await fetch('/paginas/criar_playlist.php', { method: 'POST', body: formData });
            const text = await response.text();
            
            // Tratamento robusto para extrair JSON caso o PHP envie texto extra
            const jsonStart = text.indexOf('{');
            const data = JSON.parse(text.substring(jsonStart));

            if (data.status === 'success') {
                showFeedback('Playlist criada com sucesso!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showFeedback(data.message || 'Erro ao criar.', 'error');
            }
        } catch (err) { showFeedback('Erro no servidor.', 'error'); }
    };
}

// --- 7. EVENT DELEGATION (CLICKS GERAIS) ---

document.addEventListener('click', (e) => {
    const target = e.target;

    // Favoritar
    const btnFav = target.closest('.btn-fav') || target.closest('#player-fav-btn');
    if (btnFav) {
        e.preventDefault(); e.stopPropagation();
        toggleFavorite(btnFav.dataset.id, btnFav);
        return;
    }

    // Tocar Música (Card ou Linha)
    const card = target.closest('.card[data-id]') || target.closest('.song-row[data-id]');
    if (card && !target.closest('.btn-fav')) {
        populateSongsArray();
        const foundIndex = songsArray.findIndex(m => m.id === card.dataset.id);
        if (foundIndex !== -1) loadSong(foundIndex);
    }
});

// --- AUXILIARES ---
function formatTime(s) {
    const min = Math.floor(s / 60);
    const sec = Math.floor(s % 60);
    return `${min}:${sec < 10 ? '0' : ''}${sec}`;
}

function showToast(m) {
    if(!toast) return;
    toast.textContent = m;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function showFeedback(msg, type) {
    if (!feedbackPlaylist) return;
    feedbackPlaylist.textContent = msg;
    feedbackPlaylist.style.display = msg ? 'block' : 'none';
    feedbackPlaylist.style.color = type === 'success' ? '#2ecc71' : '#e74c3c';
}

function checkEmptyState(id) {
    const container = document.getElementById(id);
    if (container && container.querySelectorAll('.card').length === 0) {
        container.innerHTML = `<div class="empty-library-message"><h3>Sua biblioteca está vazia</h3></div>`;
    }
}

function updateVolumeIcon() {
    if (!volumeIcon) return;
    const vol = audio.volume;
    volumeIcon.className = (vol === 0 || audio.muted) ? 'fas fa-volume-xmark' : (vol < 0.5 ? 'fas fa-volume-low' : 'fas fa-volume-high');
}

function loadStoredSong() {
    const stored = localStorage.getItem('playingSong');
    if (stored) {
        const data = JSON.parse(stored);
        const idx = songsArray.findIndex(s => s.id == data.id);
        if (idx !== -1) loadSong(idx);
        localStorage.removeItem('playingSong');
    }
}