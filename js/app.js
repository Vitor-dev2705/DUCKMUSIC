/**
 * DUCKMUSIC SPA ENGINE
 * Navegacao SPA + Player Persistente + Media Session API
 * Permite ouvir musica em segundo plano como YouTube.
 */
(function () {
    'use strict';

    // =============================================
    // 1. ELEMENTOS DO DOM (Persistentes no shell)
    // =============================================
    const audio       = document.getElementById('audio-element');
    const player      = document.getElementById('player');
    const playerImg   = document.getElementById('player-img');
    const playerTitle = document.getElementById('player-title');
    const playerArtist= document.getElementById('player-artist');
    const playerFavBtn= document.getElementById('player-fav-btn');
    const btnPlay     = document.getElementById('btn-play');
    const btnPause    = document.getElementById('btn-pause');
    const btnNext     = document.getElementById('btn-next');
    const btnPrev     = document.getElementById('btn-prev');
    const btnShuffle  = document.getElementById('btn-shuffle');
    const btnRepeat   = document.getElementById('btn-repeat');
    const progressBar = document.getElementById('progress-bar');
    const progressEl  = document.getElementById('progress');
    const curTimeEl   = document.getElementById('current-time');
    const durEl       = document.getElementById('duration');
    const volSlider   = document.getElementById('volume-slider');
    const volIcon     = document.getElementById('volume-icon');
    const content     = document.getElementById('content');
    const toastEl     = document.getElementById('toast');

    // =============================================
    // 2. ESTADO GLOBAL
    // =============================================
    let queue        = [];       // fila de reproducao
    let curIdx       = -1;       // indice atual na fila
    let shuffle      = false;
    let repeat       = 0;        // 0=off 1=all 2=one
    let navigating   = false;
    let curPage      = '';

    // Helpers para favoritos Deezer (localStorage)
    function isDeezerTrack(id) { return String(id).indexOf('dz_') === 0; }
    function getDeezerFavs() {
        try { return JSON.parse(localStorage.getItem('duckDzFavs') || '[]'); }
        catch(e) { return []; }
    }
    function saveDeezerFavs(arr) {
        localStorage.setItem('duckDzFavs', JSON.stringify(arr));
    }

    // Dados do PHP injetados no shell + favoritos Deezer do localStorage
    var phpFavs = (window.APP_DATA?.favoritasIds || []).map(String);
    var dzFavs  = getDeezerFavs();
    const favIds   = new Set(phpFavs.concat(dzFavs));
    const userData = window.APP_DATA?.usuario || {};
    let savedVol   = parseFloat(localStorage.getItem('duckVol')) || 0.8;

    // =============================================
    // 3. UTILITARIOS
    // =============================================
    function fmt(s) {
        if (!s || !isFinite(s)) return '0:00';
        var m = Math.floor(s / 60);
        var sec = Math.floor(s % 60);
        return m + ':' + (sec < 10 ? '0' : '') + sec;
    }

    function toast(msg, ms) {
        if (!toastEl) return;
        toastEl.textContent = msg;
        toastEl.classList.add('show');
        clearTimeout(toastEl._t);
        toastEl._t = setTimeout(function () { toastEl.classList.remove('show'); }, ms || 3000);
    }

    /** Normaliza caminhos relativos para absolutos */
    function norm(path) {
        if (!path) return '';
        // URLs completas do Supabase etc.
        if (path.indexOf('http://') === 0 || path.indexOf('https://') === 0) return path;
        // Remove ../ e garante inicio com /
        var p = path.replace(/^(\.\.\/)+/, '');
        // Re-checa apos strip (ex: ../https://supabase.co/...)
        if (p.indexOf('http://') === 0 || p.indexOf('https://') === 0) return p;
        if (p.charAt(0) !== '/') p = '/' + p;
        return p;
    }

    // =============================================
    // 4. NAVEGACAO SPA
    // =============================================
    function navigateTo(url, push) {
        if (navigating) return;
        if (push === undefined) push = true;
        navigating = true;

        // Loading
        content.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner"></i></div>';

        fetch(url, { credentials: 'same-origin', headers: { 'X-SPA': '1' } })
            .then(function (res) {
                // Se redirecionou para login, manda o usuario
                if (res.redirected && res.url.indexOf('login') !== -1) {
                    window.location.href = res.url;
                    return null;
                }
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .then(function (html) {
                if (html === null) return;

                var doc = new DOMParser().parseFromString(html, 'text/html');

                // Tenta extrair .main-content (dashboard, explorar, biblioteca, ver_playlist)
                var mc = doc.querySelector('.main-content');
                if (mc) {
                    content.innerHTML = mc.innerHTML;
                } else {
                    // Fallback para paginas como adicionar_musica que usam .content ou .container
                    var alt = doc.querySelector('.content') || doc.querySelector('.container') || doc.querySelector('body');
                    content.innerHTML = alt ? alt.innerHTML : '<p style="padding:40px;text-align:center">Pagina nao encontrada.</p>';
                }

                if (push) {
                    history.pushState({ page: url }, '', url);
                }
                curPage = url;
                try { sessionStorage.setItem('duckPage', url); } catch(e) {}

                updateActiveNav(url);
                buildQueue();
                initPageComponents();
            })
            .catch(function (err) {
                console.error('SPA nav error:', err);
                content.innerHTML =
                    '<div style="text-align:center;padding:60px 20px;color:#b3b3b3">' +
                    '<i class="fas fa-exclamation-triangle" style="font-size:3rem;margin-bottom:20px;color:#e74c3c"></i>' +
                    '<h2>Erro ao carregar</h2>' +
                    '<p>' + err.message + '</p>' +
                    '<button onclick="DuckMusic.nav(\'/paginas/dashboard.php\')" ' +
                    'style="margin-top:20px;padding:10px 30px;background:#8e44ad;border:none;color:#fff;border-radius:20px;cursor:pointer">' +
                    'Voltar ao Inicio</button></div>';
            })
            .finally(function () { navigating = false; });
    }

    function updateActiveNav(url) {
        var links = document.querySelectorAll('.sidebar .menu-item, .mobile-nav-item');
        for (var i = 0; i < links.length; i++) {
            var href = links[i].getAttribute('href') || '';
            links[i].classList.toggle('active', url.indexOf(href) !== -1 && href.length > 1);
        }
    }

    /** Inicializa componentes dinamicos da pagina injetada */
    function initPageComponents() {
        // --- Tabs da biblioteca ---
        var tabs = content.querySelectorAll('.library-tab');
        var conts = content.querySelectorAll('.library-content');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.remove('active'); });
                conts.forEach(function (c) { c.classList.remove('active'); });
                this.classList.add('active');
                var tgt = content.querySelector('#' + this.dataset.tab);
                if (tgt) tgt.classList.add('active');
                buildQueue();
            });
        });

        // --- Form de busca (explorar) ---
        var searchForm = content.querySelector('form.search-box') || content.querySelector('form[action*="explorar"]');
        if (searchForm) {
            searchForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var fd = new FormData(this);
                var q = fd.get('busca') || '';
                navigateTo('/paginas/explorar.php?busca=' + encodeURIComponent(q));
            });
        }

        // --- Links dentro do conteudo que devem ser SPA ---
        var internalLinks = content.querySelectorAll('a[href*="ver_playlist"], a[href*="configuracoes"], a[href*="dashboard"], a[href*="explorar"], a[href*="biblioteca"], a[href*="iniciar_doacao"]');
        internalLinks.forEach(function (a) {
            if (a._spa) return;
            a._spa = true;
            a.addEventListener('click', function (e) {
                var href = this.getAttribute('href');
                if (href && href.indexOf('http') !== 0 && href.indexOf('logout') === -1) {
                    e.preventDefault();
                    navigateTo(href);
                }
            });
        });

        // --- Icones de favorito nos cards ---
        updateAllFavIcons();

        // --- Carrossel touch mobile ---
        initCarouselTouch();
    }

    /**
     * Carrossel — drag com mouse no desktop
     * No mobile o scroll nativo do overflow-x ja funciona sozinho
     */
    function initCarouselTouch() {
        var carousels = content.querySelectorAll('.cards-container:not(.quick-access)');
        carousels.forEach(function(el) {
            if (el._carouselInit) return;
            el._carouselInit = true;

            var dragging = false, dragStartX = 0, scrollStart = 0, moved = false;

            el.addEventListener('mousedown', function(e) {
                dragging = true;
                moved = false;
                dragStartX = e.pageX;
                scrollStart = el.scrollLeft;
                el.style.cursor = 'grabbing';
                el.style.userSelect = 'none';
            });

            el.addEventListener('mousemove', function(e) {
                if (!dragging) return;
                e.preventDefault();
                var walk = e.pageX - dragStartX;
                if (Math.abs(walk) > 3) moved = true;
                el.scrollLeft = scrollStart - walk;
            });

            el.addEventListener('mouseup', function() {
                dragging = false;
                el.style.cursor = 'grab';
                el.style.userSelect = '';
            });

            el.addEventListener('mouseleave', function() {
                if (dragging) {
                    dragging = false;
                    el.style.cursor = 'grab';
                    el.style.userSelect = '';
                }
            });

            // Impede clique no card apos um drag
            el.addEventListener('click', function(e) {
                if (moved) {
                    e.stopPropagation();
                    e.preventDefault();
                    moved = false;
                }
            }, true);

            el.style.cursor = 'grab';
        });
    }

    // =============================================
    // 5. FILA DE REPRODUCAO
    // =============================================
    function buildQueue() {
        var items = content.querySelectorAll('.card[data-id], .song-row[data-id], .music-card[data-id]');
        queue = [];
        for (var i = 0; i < items.length; i++) {
            var el = items[i];
            var audioSrc = norm(el.dataset.audio);
            if (audioSrc) {
                queue.push({
                    id: el.dataset.id,
                    audio: audioSrc,
                    titulo: el.dataset.titulo || 'Sem titulo',
                    artista: el.dataset.artista || 'Desconhecido',
                    capa: norm(el.dataset.capa)
                });
            }
        }
    }

    // =============================================
    // 6. PLAYER DE AUDIO
    // =============================================
    function loadSong(idx) {
        if (idx < 0 || idx >= queue.length) return;

        curIdx = idx;
        var song = queue[idx];

        audio.src = song.audio;
        playerTitle.textContent = song.titulo;
        playerArtist.textContent = song.artista;
        playerImg.src = song.capa || '/assets/img/capa-padrao.svg';
        playerImg.onerror = function () { this.src = '/assets/img/capa-padrao.svg'; };

        if (playerFavBtn) playerFavBtn.setAttribute('data-id', song.id);

        // Mostra player
        player.style.display = 'flex';

        // Toca
        audio.play().catch(function () {
            console.log('Autoplay bloqueado');
        });

        // Favorito
        setFavIcon(playerFavBtn, favIds.has(String(song.id)));

        // Sync fullscreen
        var pf = document.getElementById('player-full');
        if (pf && pf.classList.contains('active')) {
            var pfImg = document.getElementById('player-full-img');
            var pfBg = document.getElementById('player-full-bg');
            var pfTitle = document.getElementById('player-full-title');
            var pfArtist = document.getElementById('player-full-artist');
            var pfFav = document.getElementById('player-full-fav');
            var capa = song.capa || '/assets/img/capa-padrao.svg';
            if (pfImg) pfImg.src = capa;
            if (pfBg) pfBg.style.backgroundImage = 'url(' + capa + ')';
            if (pfTitle) pfTitle.textContent = song.titulo;
            if (pfArtist) pfArtist.textContent = song.artista;
            if (pfFav) { pfFav.setAttribute('data-id', song.id); setFavIcon(pfFav, favIds.has(String(song.id))); }
        }

        // Media Session
        setMediaSession(song);

        // Salva estado
        try {
            localStorage.setItem('duckSong', JSON.stringify(song));
        } catch (e) { }
    }

    function playNext() {
        if (queue.length === 0) return;
        if (repeat === 2) { audio.currentTime = 0; audio.play(); return; }

        var next;
        if (shuffle) {
            next = Math.floor(Math.random() * queue.length);
            if (queue.length > 1) while (next === curIdx) next = Math.floor(Math.random() * queue.length);
        } else {
            next = (curIdx + 1) % queue.length;
            // Se repeat=off e voltou ao 0, para
            if (repeat === 0 && next === 0 && curIdx === queue.length - 1) {
                audio.pause();
                return;
            }
        }
        loadSong(next);
    }

    function playPrev() {
        if (queue.length === 0) return;
        // Se ja tocou mais de 3s, volta ao inicio
        if (audio.currentTime > 3) { audio.currentTime = 0; return; }

        var prev;
        if (shuffle) {
            prev = Math.floor(Math.random() * queue.length);
        } else {
            prev = (curIdx - 1 + queue.length) % queue.length;
        }
        loadSong(prev);
    }

    function toggleShuffle() {
        shuffle = !shuffle;
        btnShuffle.classList.toggle('active', shuffle);
        toast(shuffle ? 'Aleatorio ativado' : 'Aleatorio desativado');
    }

    function toggleRepeat() {
        repeat = (repeat + 1) % 3;
        btnRepeat.classList.remove('active', 'repeat-one');
        if (repeat === 1) { btnRepeat.classList.add('active'); toast('Repetir todas'); }
        else if (repeat === 2) { btnRepeat.classList.add('active'); btnRepeat.classList.add('repeat-one'); toast('Repetir uma'); }
        else { toast('Repetir desativado'); }
    }

    // =============================================
    // 7. MEDIA SESSION API (Lock Screen / Background)
    // =============================================
    function setMediaSession(song) {
        if (!('mediaSession' in navigator)) return;

        var artwork = [];
        if (song.capa) {
            artwork = [
                { src: song.capa, sizes: '96x96',   type: 'image/jpeg' },
                { src: song.capa, sizes: '128x128', type: 'image/jpeg' },
                { src: song.capa, sizes: '256x256', type: 'image/jpeg' },
                { src: song.capa, sizes: '512x512', type: 'image/jpeg' }
            ];
        }

        navigator.mediaSession.metadata = new MediaMetadata({
            title:  song.titulo,
            artist: song.artista,
            album:  'DuckMusic',
            artwork: artwork
        });

        navigator.mediaSession.setActionHandler('play',          function () { audio.play(); });
        navigator.mediaSession.setActionHandler('pause',         function () { audio.pause(); });
        navigator.mediaSession.setActionHandler('previoustrack', playPrev);
        navigator.mediaSession.setActionHandler('nexttrack',     playNext);
        navigator.mediaSession.setActionHandler('seekto', function (d) {
            audio.currentTime = d.seekTime;
            syncPositionState();
        });
        navigator.mediaSession.setActionHandler('seekbackward', function (d) {
            audio.currentTime = Math.max(0, audio.currentTime - (d.seekOffset || 10));
        });
        navigator.mediaSession.setActionHandler('seekforward', function (d) {
            audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + (d.seekOffset || 10));
        });
    }

    function syncPositionState() {
        if (!('mediaSession' in navigator) || !audio.duration || !isFinite(audio.duration)) return;
        try {
            navigator.mediaSession.setPositionState({
                duration:     audio.duration,
                playbackRate: audio.playbackRate,
                position:     audio.currentTime
            });
        } catch (e) { }
    }

    // =============================================
    // 8. FAVORITOS (AJAX)
    // =============================================
    /** Busca metadados do track pelo ID no DOM */
    function getTrackMeta(id) {
        var card = content.querySelector('[data-id="' + id + '"]') ||
                   document.querySelector('[data-id="' + id + '"]');
        if (!card) return {};
        return {
            titulo: card.dataset.titulo || '',
            artista: card.dataset.artista || '',
            capa: card.dataset.capa || '',
            audio: card.dataset.audio || ''
        };
    }

    function toggleFav(id, btn) {
        var strId = String(id);
        var meta = getTrackMeta(strId);

        // Monta body com metadados para Deezer tracks
        var bodyParts = ['musica_id=' + encodeURIComponent(strId)];
        if (isDeezerTrack(strId)) {
            bodyParts.push('titulo=' + encodeURIComponent(meta.titulo));
            bodyParts.push('artista=' + encodeURIComponent(meta.artista));
            bodyParts.push('capa=' + encodeURIComponent(meta.capa));
            bodyParts.push('audio=' + encodeURIComponent(meta.audio));
        }

        fetch('/api/favoritar.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bodyParts.join('&')
        })
        .then(function (r) { return r.text(); })
        .then(function (raw) {
            var clean = raw.replace(/^﻿/, '').trim();
            var i = clean.indexOf('{');
            var data = JSON.parse(clean.substring(i >= 0 ? i : 0));

            if (data.status === 'success') {
                if (data.favoritado) {
                    favIds.add(strId);
                    toast('Adicionado aos favoritos');
                } else {
                    favIds.delete(strId);
                    toast('Removido dos favoritos');
                }
                syncFavIcons(strId, data.favoritado);

                // Se na aba favoritas e desfavoritou, esconde o card
                if (!data.favoritado) {
                    var activeTab = content.querySelector('.library-tab.active[data-tab="favoritas"]');
                    if (activeTab && btn) {
                        var card = btn.closest('.card, .music-card');
                        if (card) {
                            card.style.transition = 'opacity 0.3s';
                            card.style.opacity = '0';
                            setTimeout(function () { card.remove(); }, 300);
                        }
                    }
                }
            }
        })
        .catch(function (err) {
            console.error('Erro favoritar:', err);
            toast('Erro ao favoritar');
        });
    }

    function setFavIcon(btn, isFav) {
        if (!btn) return;
        var icon = btn.querySelector('i');
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
        var btns = content.querySelectorAll('.btn-fav[data-id="' + id + '"]');
        for (var i = 0; i < btns.length; i++) setFavIcon(btns[i], isFav);
        if (playerFavBtn && playerFavBtn.getAttribute('data-id') === String(id)) {
            setFavIcon(playerFavBtn, isFav);
        }
    }

    function updateAllFavIcons() {
        var btns = content.querySelectorAll('.btn-fav[data-id]');
        for (var i = 0; i < btns.length; i++) {
            var id = String(btns[i].dataset.id);
            setFavIcon(btns[i], favIds.has(id));
        }
    }

    // =============================================
    // 9. MODAL DE PLAYLIST (sidebar)
    // =============================================
    function initPlaylistModal() {
        var modal = document.getElementById('modalCriarPlaylist');
        var form  = document.getElementById('formCriarPlaylist');
        var fb    = document.getElementById('modalFeedback');
        if (!modal || !form) return;

        // Fechar
        var closeBtn  = document.getElementById('closeModalCriarPlaylist');
        var cancelBtn = document.getElementById('btnCancelarCriarPlaylist');
        if (closeBtn)  closeBtn.onclick  = function () { modal.style.display = 'none'; };
        if (cancelBtn) cancelBtn.onclick = function () { modal.style.display = 'none'; };
        modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });

        // Submissao
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd   = new FormData(form);
            var nome = (fd.get('playlist_nome') || '').trim();
            if (!nome) {
                if (fb) { fb.textContent = 'Nome obrigatorio.'; fb.style.display = 'block'; fb.style.color = '#e74c3c'; }
                return;
            }

            fetch('/api/criar_playlist.php', { method: 'POST', credentials: 'same-origin', body: fd })
                .then(function (r) { return r.text(); })
                .then(function (raw) {
                    var clean = raw.replace(/^﻿/, '').trim();
                    var i = clean.indexOf('{');
                    var data = JSON.parse(clean.substring(i >= 0 ? i : 0));

                    if (data.status === 'success') {
                        toast('Playlist criada!');
                        modal.style.display = 'none';
                        form.reset();
                        // Adiciona na sidebar
                        var sb = document.getElementById('sidebar-playlists');
                        if (sb) {
                            var aviso = sb.querySelector('.sem-playlists-aviso');
                            if (aviso) aviso.remove();
                            var a = document.createElement('a');
                            a.href = '/api/ver_playlist.php?id=' + data.playlist_id;
                            a.className = 'playlist-item nav-link';
                            a.innerHTML = '<i class="fas fa-music"></i><span>' + nome + '</span>';
                            sb.appendChild(a);
                        }
                    } else {
                        if (fb) { fb.textContent = data.message || 'Erro.'; fb.style.display = 'block'; fb.style.color = '#e74c3c'; }
                    }
                })
                .catch(function () {
                    if (fb) { fb.textContent = 'Erro de comunicacao.'; fb.style.display = 'block'; fb.style.color = '#e74c3c'; }
                });
        });
    }

    // =============================================
    // 10. VOLUME
    // =============================================
    function initVolume() {
        audio.volume = savedVol;
        if (volSlider) volSlider.value = savedVol;
        setVolIcon(savedVol);

        if (volSlider) {
            volSlider.addEventListener('input', function () {
                audio.volume = this.value;
                savedVol = parseFloat(this.value);
                localStorage.setItem('duckVol', savedVol);
                setVolIcon(this.value);
            });
        }

        if (volIcon) {
            volIcon.addEventListener('click', function () {
                if (audio.volume > 0) {
                    localStorage.setItem('duckVolPrev', audio.volume);
                    audio.volume = 0;
                    if (volSlider) volSlider.value = 0;
                    setVolIcon(0);
                } else {
                    var prev = parseFloat(localStorage.getItem('duckVolPrev')) || 0.8;
                    audio.volume = prev;
                    if (volSlider) volSlider.value = prev;
                    setVolIcon(prev);
                }
            });
        }
    }

    function setVolIcon(v) {
        if (!volIcon) return;
        v = parseFloat(v);
        volIcon.className = v === 0 ? 'fas fa-volume-xmark'
                          : v < 0.3 ? 'fas fa-volume-off'
                          : v < 0.7 ? 'fas fa-volume-low'
                          :           'fas fa-volume-high';
    }

    // =============================================
    // 11. EVENTOS DO PLAYER
    // =============================================
    function initPlayerEvents() {
        btnPlay.addEventListener('click', function () { audio.play(); });
        btnPause.addEventListener('click', function () { audio.pause(); });
        btnNext.addEventListener('click', playNext);
        btnPrev.addEventListener('click', playPrev);
        btnShuffle.addEventListener('click', toggleShuffle);
        btnRepeat.addEventListener('click', toggleRepeat);

        // === PLAYER FULLSCREEN (mobile) ===
        var pf = document.getElementById('player-full');
        var pfImg = document.getElementById('player-full-img');
        var pfBg = document.getElementById('player-full-bg');
        var pfTitle = document.getElementById('player-full-title');
        var pfArtist = document.getElementById('player-full-artist');
        var pfFav = document.getElementById('player-full-fav');
        var pfClose = document.getElementById('player-full-close');
        var pfPlay = document.getElementById('pf-play');
        var pfPrev = document.getElementById('pf-prev');
        var pfNext = document.getElementById('pf-next');
        var pfShuffle = document.getElementById('pf-shuffle');
        var pfRepeat = document.getElementById('pf-repeat');
        var pfFill = document.getElementById('player-full-fill');
        var pfThumb = document.getElementById('player-full-thumb');
        var pfBar = document.getElementById('player-full-bar');
        var pfCur = document.getElementById('player-full-current');
        var pfDur = document.getElementById('player-full-duration');

        function isMobile() { return window.innerWidth <= 576; }

        function syncFullscreen() {
            if (!pf) return;
            var song = queue[curIdx];
            if (!song) return;
            var capa = song.capa || playerImg.src || '/assets/img/capa-padrao.svg';
            pfImg.src = capa;
            pfBg.style.backgroundImage = 'url(' + capa + ')';
            pfTitle.textContent = song.titulo || playerTitle.textContent;
            pfArtist.textContent = song.artista || playerArtist.textContent;
            if (pfFav) pfFav.setAttribute('data-id', song.id || '');
            setFavIcon(pfFav, favIds.has(String(song.id)));
        }

        function syncFullscreenPlayState() {
            if (!pfPlay) return;
            pfPlay.innerHTML = audio.paused
                ? '<i class="fas fa-play"></i>'
                : '<i class="fas fa-pause"></i>';
        }

        function openFullscreen() {
            if (!pf || !isMobile()) return;
            syncFullscreen();
            syncFullscreenPlayState();
            pf.classList.add('active', 'sliding-up');
            pf.classList.remove('sliding-down');
            document.body.style.overflow = 'hidden';
        }

        function closeFullscreen() {
            if (!pf) return;
            pf.classList.add('sliding-down');
            pf.classList.remove('sliding-up');
            setTimeout(function() {
                pf.classList.remove('active', 'sliding-down');
                document.body.style.overflow = '';
            }, 300);
        }

        // Abrir fullscreen ao clicar no mini player (mobile)
        player.addEventListener('click', function(e) {
            if (!isMobile()) return;
            // Nao abrir se clicou em botao de controle
            if (e.target.closest('button') || e.target.closest('.progress-bar')) return;
            if (curIdx < 0) return;
            openFullscreen();
        });

        if (pfClose) pfClose.addEventListener('click', closeFullscreen);

        // Controles do fullscreen
        if (pfPlay) pfPlay.addEventListener('click', function() {
            audio.paused ? audio.play() : audio.pause();
        });
        if (pfPrev) pfPrev.addEventListener('click', playPrev);
        if (pfNext) pfNext.addEventListener('click', playNext);
        if (pfShuffle) pfShuffle.addEventListener('click', function() {
            toggleShuffle();
            pfShuffle.style.color = shuffle ? '#1db954' : '';
        });
        if (pfRepeat) pfRepeat.addEventListener('click', function() {
            toggleRepeat();
            pfRepeat.style.color = repeat > 0 ? '#1db954' : '';
        });

        // Favoritar no fullscreen
        if (pfFav) pfFav.addEventListener('click', function() {
            if (playerFavBtn) playerFavBtn.click();
            setTimeout(function() {
                var id = pfFav.getAttribute('data-id');
                setFavIcon(pfFav, favIds.has(String(id)));
            }, 300);
        });

        // Seek na barra do fullscreen
        if (pfBar) pfBar.addEventListener('click', function(e) {
            if (!audio.duration || !isFinite(audio.duration)) return;
            var rect = pfBar.getBoundingClientRect();
            var pct = (e.clientX - rect.left) / rect.width;
            audio.currentTime = pct * audio.duration;
        });

        audio.addEventListener('play', function () {
            btnPlay.style.display = 'none';
            btnPause.style.display = 'inline-flex';
            player.classList.add('playing');
            syncFullscreenPlayState();
            if ('mediaSession' in navigator) navigator.mediaSession.playbackState = 'playing';
        });

        audio.addEventListener('pause', function () {
            btnPlay.style.display = 'inline-flex';
            btnPause.style.display = 'none';
            player.classList.remove('playing');
            syncFullscreenPlayState();
            if ('mediaSession' in navigator) navigator.mediaSession.playbackState = 'paused';
        });

        audio.addEventListener('timeupdate', function () {
            if (!audio.duration || !isFinite(audio.duration)) return;
            var pct = (audio.currentTime / audio.duration) * 100;
            progressEl.style.width = pct + '%';
            curTimeEl.textContent = fmt(audio.currentTime);
            durEl.textContent = fmt(audio.duration);
            // Fullscreen sync
            if (pfFill) pfFill.style.width = pct + '%';
            if (pfThumb) pfThumb.style.left = pct + '%';
            if (pfCur) pfCur.textContent = fmt(audio.currentTime);
            if (pfDur) pfDur.textContent = fmt(audio.duration);
        });

        audio.addEventListener('ended', playNext);

        audio.addEventListener('loadedmetadata', function () {
            durEl.textContent = fmt(audio.duration);
            if (pfDur) pfDur.textContent = fmt(audio.duration);
            syncPositionState();
        });

        // Click na barra de progresso para seek
        progressBar.addEventListener('click', function (e) {
            if (!audio.duration || !isFinite(audio.duration)) return;
            var rect = this.getBoundingClientRect();
            var pct = (e.clientX - rect.left) / rect.width;
            audio.currentTime = pct * audio.duration;
        });

        // Atalhos de teclado
        document.addEventListener('keydown', function (e) {
            var tag = e.target.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

            switch (e.code) {
                case 'Space':
                    e.preventDefault();
                    audio.paused ? audio.play() : audio.pause();
                    break;
                case 'ArrowRight':
                    e.shiftKey ? playNext() : (audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + 5));
                    break;
                case 'ArrowLeft':
                    e.shiftKey ? playPrev() : (audio.currentTime = Math.max(0, audio.currentTime - 5));
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    audio.volume = Math.min(1, audio.volume + 0.05);
                    if (volSlider) volSlider.value = audio.volume;
                    setVolIcon(audio.volume);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    audio.volume = Math.max(0, audio.volume - 0.05);
                    if (volSlider) volSlider.value = audio.volume;
                    setVolIcon(audio.volume);
                    break;
                case 'KeyM':
                    if (volIcon) volIcon.click();
                    break;
            }
        });
    }

    // =============================================
    // 12. PLAYLIST DROPDOWN (Adicionar musica)
    // =============================================
    var activeDropdown = null;

    function closePlaylistDropdown() {
        if (activeDropdown) {
            activeDropdown.remove();
            activeDropdown = null;
        }
    }

    function showPlaylistDropdown(btn, overrideMeta) {
        closePlaylistDropdown();

        var card = btn.closest('.card[data-id]');
        var trackId, meta;
        if (card) {
            trackId = card.dataset.id;
            meta = {
                titulo: card.dataset.titulo || '',
                artista: card.dataset.artista || '',
                capa: card.dataset.capa || '',
                audio: card.dataset.audio || ''
            };
        } else if (overrideMeta) {
            trackId = overrideMeta.id;
            meta = overrideMeta;
        } else {
            return;
        }

        // Overlay escuro
        var overlay = document.createElement('div');
        overlay.className = 'playlist-dropdown-overlay';
        overlay.addEventListener('click', function(ev) {
            if (ev.target === overlay) closePlaylistDropdown();
        });

        // Popup centralizado
        var dd = document.createElement('div');
        dd.className = 'playlist-dropdown';
        dd.innerHTML = '<div class="playlist-dropdown-header"><span>Adicionar a playlist</span><button class="playlist-dropdown-close"><i class="fas fa-times"></i></button></div>';
        dd.querySelector('.playlist-dropdown-close').addEventListener('click', closePlaylistDropdown);

        // Lista de playlists
        var listContainer = document.createElement('div');
        listContainer.className = 'playlist-dropdown-list';

        var plItems = document.querySelectorAll('#sidebar-playlists .playlist-item');
        if (plItems.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'playlist-dropdown-empty';
            empty.textContent = 'Nenhuma playlist criada';
            listContainer.appendChild(empty);
        } else {
            for (var i = 0; i < plItems.length; i++) {
                var href = plItems[i].getAttribute('href') || '';
                var match = href.match(/id=(\d+)/);
                if (!match) continue;
                var plId = match[1];
                var plName = plItems[i].textContent.trim();
                var item = document.createElement('div');
                item.className = 'playlist-dropdown-item';
                item.setAttribute('data-playlist-id', plId);
                item.innerHTML = '<i class="fas fa-music"></i><span>' + plName + '</span>';
                item.addEventListener('click', (function(pid, m) {
                    return function() {
                        addToPlaylist(pid, trackId, m);
                        closePlaylistDropdown();
                    };
                })(plId, meta));
                listContainer.appendChild(item);
            }
        }
        dd.appendChild(listContainer);

        // Secao criar nova playlist
        var createSection = document.createElement('div');
        createSection.className = 'playlist-dropdown-create';
        createSection.innerHTML =
            '<button class="playlist-dropdown-create-btn"><i class="fas fa-plus"></i><span>Criar nova playlist</span></button>' +
            '<div class="playlist-dropdown-create-form" style="display:none;">' +
            '<input type="text" class="playlist-dropdown-input" placeholder="Nome da playlist" maxlength="100">' +
            '<button class="playlist-dropdown-save-btn" title="Criar"><i class="fas fa-check"></i></button>' +
            '</div>';
        dd.appendChild(createSection);

        var createBtn = createSection.querySelector('.playlist-dropdown-create-btn');
        var createForm = createSection.querySelector('.playlist-dropdown-create-form');
        var createInput = createSection.querySelector('.playlist-dropdown-input');
        var saveBtn = createSection.querySelector('.playlist-dropdown-save-btn');

        createBtn.addEventListener('click', function() {
            createBtn.style.display = 'none';
            createForm.style.display = 'flex';
            createInput.focus();
        });

        function submitNewPlaylist() {
            var nome = createInput.value.trim();
            if (!nome) return;
            saveBtn.disabled = true;
            createInput.disabled = true;

            var fd = new FormData();
            fd.append('playlist_nome', nome);

            fetch('/api/criar_playlist.php', { method: 'POST', credentials: 'same-origin', body: fd })
                .then(function(r) { return r.text(); })
                .then(function(raw) {
                    var clean = raw.replace(/^\xEF\xBB\xBF/, '').trim();
                    var idx = clean.indexOf('{');
                    var data = JSON.parse(clean.substring(idx >= 0 ? idx : 0));

                    if (data.status === 'success') {
                        toast('Playlist criada!');
                        // Adiciona na sidebar
                        var sb = document.getElementById('sidebar-playlists');
                        if (sb) {
                            var aviso = sb.querySelector('.sem-playlists-aviso');
                            if (aviso) aviso.remove();
                            var a = document.createElement('a');
                            a.href = '/api/ver_playlist.php?id=' + data.playlist_id;
                            a.className = 'playlist-item nav-link';
                            a.innerHTML = '<i class="fas fa-music"></i><span>' + nome + '</span>';
                            sb.appendChild(a);
                        }
                        // Adiciona a musica automaticamente na nova playlist
                        addToPlaylist(data.playlist_id, trackId, meta);
                        closePlaylistDropdown();
                    } else {
                        toast(data.message || 'Erro ao criar playlist');
                        saveBtn.disabled = false;
                        createInput.disabled = false;
                    }
                })
                .catch(function() {
                    toast('Erro de comunicacao');
                    saveBtn.disabled = false;
                    createInput.disabled = false;
                });
        }

        saveBtn.addEventListener('click', submitNewPlaylist);
        createInput.addEventListener('keydown', function(ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); submitNewPlaylist(); }
        });

        overlay.appendChild(dd);
        document.body.appendChild(overlay);
        activeDropdown = overlay;
    }

    function addToPlaylist(playlistId, trackId, meta) {
        var bodyParts = [
            'id_playlist=' + playlistId,
            'musica_id=' + encodeURIComponent(trackId)
        ];
        if (isDeezerTrack(trackId)) {
            bodyParts.push('titulo=' + encodeURIComponent(meta.titulo));
            bodyParts.push('artista=' + encodeURIComponent(meta.artista));
            bodyParts.push('capa=' + encodeURIComponent(meta.capa));
            bodyParts.push('audio=' + encodeURIComponent(meta.audio));
        }

        fetch('/api/adicionar_playlist_track.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bodyParts.join('&')
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            toast(data.message || (data.status === 'success' ? 'Adicionada!' : 'Erro'));
        })
        .catch(function() { toast('Erro ao adicionar'); });
    }

    // =============================================
    // 13. DELEGACAO DE EVENTOS GLOBAL
    // =============================================
    function initGlobalEvents() {
        document.addEventListener('click', function (e) {
            var target = e.target;

            // --- Fechar dropdown de playlist ---
            if (activeDropdown && !target.closest('.playlist-dropdown-overlay') && !target.closest('.btn-add-playlist') && !target.closest('#player-add-playlist-btn')) {
                closePlaylistDropdown();
            }

            // --- Botao adicionar a playlist (cards) ---
            var btnAddPl = target.closest('.btn-add-playlist');
            if (btnAddPl) {
                e.preventDefault();
                e.stopPropagation();
                showPlaylistDropdown(btnAddPl);
                return;
            }

            // --- Botao adicionar a playlist (player) ---
            var btnPlayerAdd = target.closest('#player-add-playlist-btn');
            if (btnPlayerAdd) {
                e.preventDefault();
                e.stopPropagation();
                if (curIdx < 0 || !queue[curIdx]) {
                    toast('Nenhuma musica tocando');
                    return;
                }
                var s = queue[curIdx];
                showPlaylistDropdown(btnPlayerAdd, {
                    id: s.id,
                    titulo: s.titulo,
                    artista: s.artista,
                    capa: s.capa,
                    audio: s.audio
                });
                return;
            }

            // --- Abrir modal de playlist ---
            if (target.closest('.btn-trigger-modal-playlist')) {
                e.preventDefault();
                var modal = document.getElementById('modalCriarPlaylist');
                if (modal) {
                    modal.style.display = 'flex';
                    var form = document.getElementById('formCriarPlaylist');
                    if (form) form.reset();
                    var fb = document.getElementById('modalFeedback');
                    if (fb) { fb.textContent = ''; fb.style.display = 'none'; }
                }
                return;
            }

            // --- Navegacao SPA: links do menu e sidebar ---
            var navLink = target.closest('.nav-link, .sidebar .menu-item');
            if (navLink) {
                var href = navLink.getAttribute('href');
                if (href && href !== '#' && href.indexOf('logout') === -1 && href.indexOf('http') !== 0) {
                    e.preventDefault();
                    navigateTo(href);
                    return;
                }
            }

            // --- Favoritar ---
            var btnFav = target.closest('.btn-fav') || target.closest('#player-fav-btn');
            if (btnFav) {
                e.preventDefault();
                e.stopPropagation();
                var id = btnFav.dataset.id;
                if (id) toggleFav(id, btnFav);
                return;
            }

            // --- Tocar musica (click no card/row) ---
            var card = target.closest('.card[data-id], .song-row[data-id], .music-card[data-id]');
            if (card && !target.closest('.btn-fav') && !target.closest('a[href]')) {
                buildQueue();
                var songId = card.dataset.id;
                var idx = -1;
                for (var i = 0; i < queue.length; i++) {
                    if (queue[i].id === songId) { idx = i; break; }
                }
                if (idx !== -1) {
                    loadSong(idx);
                } else {
                    // Cria entrada avulsa
                    var song = {
                        id: card.dataset.id,
                        audio: norm(card.dataset.audio),
                        titulo: card.dataset.titulo || 'Sem titulo',
                        artista: card.dataset.artista || 'Desconhecido',
                        capa: norm(card.dataset.capa)
                    };
                    if (song.audio) {
                        queue.push(song);
                        loadSong(queue.length - 1);
                    }
                }
                return;
            }
        });

        // Botao voltar/avancar do navegador
        window.addEventListener('popstate', function (e) {
            var page = (e.state && e.state.page) ? e.state.page : location.pathname + location.search;
            navigateTo(page, false);
        });
    }

    // =============================================
    // 13. RESTAURAR ESTADO AO RECARREGAR
    // =============================================
    function restoreState() {
        try {
            var raw = localStorage.getItem('duckSong');
            if (!raw) return;
            var song = JSON.parse(raw);
            // Restaura UI sem tocar
            playerTitle.textContent  = song.titulo  || 'Selecione uma musica';
            playerArtist.textContent = song.artista || 'DuckMusic';
            playerImg.src = song.capa || '/assets/img/capa-padrao.svg';
            if (playerFavBtn) playerFavBtn.setAttribute('data-id', song.id || '');
            audio.src = song.audio || '';
            player.style.display = 'flex';
            setFavIcon(playerFavBtn, favIds.has(String(song.id)));
            setMediaSession(song);
        } catch (e) { }
    }

    // =============================================
    // 14. INICIALIZACAO
    // =============================================
    function init() {
        initPlayerEvents();
        initVolume();
        initPlaylistModal();
        initGlobalEvents();
        restoreState();

        // Restaura pagina atual ou carrega dashboard
        var savedPage = null;
        try { savedPage = sessionStorage.getItem('duckPage'); } catch(e) {}
        navigateTo(savedPage || '/paginas/dashboard.php');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // API global
    window.DuckMusic = {
        nav:           navigateTo,
        play:          loadSong,
        next:          playNext,
        prev:          playPrev,
        toggleShuffle: toggleShuffle,
        toggleRepeat:  toggleRepeat,
        toast:         toast,
        buildQueue:    buildQueue
    };

})();
