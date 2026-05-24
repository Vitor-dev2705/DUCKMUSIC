<div class="player" id="player" style="display: none;">
    <div class="player-song">
        <img src="capa-padrao.jpg" class="player-song-img" id="player-img">
        <div class="player-song-info">
            <h4 id="player-title"></h4>
            <p id="player-artist"></p>
        </div>
        <button class="player-btn" id="favorite-btn">
            <i class="far fa-heart"></i>
        </button>
    </div>
    
    <div class="player-controls">
        <div class="player-buttons">
            <button class="player-btn" id="shuffle-btn">
                <i class="fas fa-random"></i>
            </button>
            <button class="player-btn" id="prev-btn">
                <i class="fas fa-step-backward"></i>
            </button>
            <button class="player-btn play" id="play-btn">
                <i class="fas fa-play"></i>
            </button>
            <button class="player-btn" id="next-btn">
                <i class="fas fa-step-forward"></i>
            </button>
            <button class="player-btn" id="repeat-btn">
                <i class="fas fa-redo"></i>
            </button>
        </div>
        
        <div class="player-progress">
            <span class="progress-time" id="current-time">0:00</span>
            <div class="progress-bar" id="progress-bar">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
            <span class="progress-time" id="duration">0:00</span>
        </div>
    </div>
    
    <div class="player-extra">
        <button class="player-btn" id="queue-btn">
            <i class="fas fa-list"></i>
        </button>
        <button class="player-btn" id="devices-btn">
            <i class="fas fa-laptop"></i>
        </button>
        <div class="volume-control">
            <button class="player-btn" id="volume-btn">
                <i class="fas fa-volume-up"></i>
            </button>
            <div class="volume-bar" id="volume-bar">
                <div class="volume-fill" id="volume-fill"></div>
            </div>
        </div>
    </div>
</div>

<audio id="audio-element" src=""></audio>

<script>
    const audioElement = document.getElementById('audio-element');
    const player = document.getElementById('player');
    const playerTitle = document.getElementById('player-title');
    const playerArtist = document.getElementById('player-artist');
    const playerImg = document.getElementById('player-img');
    const playBtn = document.getElementById('play-btn');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const shuffleBtn = document.getElementById('shuffle-btn');
    const repeatBtn = document.getElementById('repeat-btn');
    const favoriteBtn = document.getElementById('favorite-btn');
    const progressBar = document.getElementById('progress-bar');
    const progressFill = document.getElementById('progress-fill');
    const currentTimeEl = document.getElementById('current-time');
    const durationEl = document.getElementById('duration');
    const volumeBar = document.getElementById('volume-bar');
    const volumeFill = document.getElementById('volume-fill');
    const volumeBtn = document.getElementById('volume-btn');
    
    let currentSongIndex = 0;
    let songsQueue = [];
    let isShuffle = false;
    let isRepeat = false;
    let isMuted = false;
    let lastVolume = 0.7;
    
    // Configurar volume inicial
    audioElement.volume = lastVolume;
    volumeFill.style.width = `${lastVolume * 100}%`;
    
    // Função para formatar tempo (segundos para mm:ss)
    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
    }
    
    // Atualizar progresso da música
    audioElement.addEventListener('timeupdate', function() {
        const progress = (audioElement.currentTime / audioElement.duration) * 100;
        progressFill.style.width = `${progress}%`;
        currentTimeEl.textContent = formatTime(audioElement.currentTime);
    });
    
    // Atualizar duração total quando os metadados são carregados
    audioElement.addEventListener('loadedmetadata', function() {
        durationEl.textContent = formatTime(audioElement.duration);
    });
    
    // Clique na barra de progresso para buscar
    progressBar.addEventListener('click', function(e) {
        const percent = e.offsetX / this.offsetWidth;
        audioElement.currentTime = percent * audioElement.duration;
    });
    
    // Controles de volume
    volumeBar.addEventListener('click', function(e) {
        const percent = e.offsetX / this.offsetWidth;
        audioElement.volume = percent;
        lastVolume = percent;
        volumeFill.style.width = `${percent * 100}%`;
        
        if (percent === 0) {
            isMuted = true;
            volumeBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
        } else {
            isMuted = false;
            volumeBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
        }
    });
    
    // Botão de mute
    volumeBtn.addEventListener('click', function() {
        if (isMuted) {
            audioElement.volume = lastVolume;
            volumeFill.style.width = `${lastVolume * 100}%`;
            this.innerHTML = '<i class="fas fa-volume-up"></i>';
            isMuted = false;
        } else {
            audioElement.volume = 0;
            volumeFill.style.width = '0%';
            this.innerHTML = '<i class="fas fa-volume-mute"></i>';
            isMuted = true;
        }
    });
    
    // Botão de play/pause
    playBtn.addEventListener('click', function() {
        if (audioElement.paused) {
            audioElement.play();
            this.innerHTML = '<i class="fas fa-pause"></i>';
        } else {
            audioElement.pause();
            this.innerHTML = '<i class="fas fa-play"></i>';
        }
    });
    
    // Botão de shuffle
    shuffleBtn.addEventListener('click', function() {
        isShuffle = !isShuffle;
        this.style.color = isShuffle ? 'var(--primary)' : 'var(--white)';
    });
    
    // Botão de repeat
    repeatBtn.addEventListener('click', function() {
        isRepeat = !isRepeat;
        this.style.color = isRepeat ? 'var(--primary)' : 'var(--white)';
    });
    
    // Quando a música termina
    audioElement.addEventListener('ended', function() {
        if (isRepeat) {
            audioElement.currentTime = 0;
            audioElement.play();
        } else {
            playNextSong();
        }
    });
    
    // Função para tocar a próxima música
    function playNextSong() {
        if (songsQueue.length === 0) return;
        
        if (isShuffle) {
            currentSongIndex = Math.floor(Math.random() * songsQueue.length);
        } else {
            currentSongIndex = (currentSongIndex + 1) % songsQueue.length;
        }
        
        playSong(currentSongIndex);
    }
    
    // Função para tocar música específica
    function playSong(index) {
        if (index < 0 || index >= songsQueue.length) return;
        
        currentSongIndex = index;
        const song = songsQueue[index];
        
        audioElement.src = song.audio;
        playerTitle.textContent = song.title;
        playerArtist.textContent = song.artist;
        playerImg.src = song.image || 'capa-padrao.jpg';
        
        player.style.display = 'flex';
        audioElement.play();
        playBtn.innerHTML = '<i class="fas fa-pause"></i>';
        
        // Verificar se a música está favoritada
        checkIfFavorite(song.id);
    }
    
    // Verificar se música está favoritada
    async function checkIfFavorite(songId) {
        if (!songId) return;
        
        try {
            const response = await fetch('favoritar_musica.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `musica_id=${songId}&check_only=true`
            });
            
            const data = await response.json();
            
            if (data.is_favorite) {
                favoriteBtn.innerHTML = '<i class="fas fa-heart" style="color: var(--primary)"></i>';
                favoriteBtn.setAttribute('data-favorited', 'true');
            } else {
                favoriteBtn.innerHTML = '<i class="far fa-heart"></i>';
                favoriteBtn.setAttribute('data-favorited', 'false');
            }
            favoriteBtn.setAttribute('data-musica', songId);
        } catch (error) {
            console.error('Erro ao verificar favorito:', error);
        }
    }
    
    // Favoritar música
    favoriteBtn.addEventListener('click', async function(e) {
        e.stopPropagation();
        const musicaId = this.getAttribute('data-musica');
        const isFavorited = this.getAttribute('data-favorited') === 'true';
        
        if (!musicaId) return;
        
        try {
            const response = await fetch('favoritar_musica.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `musica_id=${musicaId}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (data.action === 'added') {
                    this.innerHTML = '<i class="fas fa-heart" style="color: var(--primary)"></i>';
                    this.setAttribute('data-favorited', 'true');
                } else {
                    this.innerHTML = '<i class="far fa-heart"></i>';
                    this.setAttribute('data-favorited', 'false');
                }
            }
        } catch (error) {
            console.error('Erro:', error);
        }
    });
    
    // Evento para quando uma música é clicada (deve ser chamado pelas páginas)
    window.playMusic = function(audioFile, title, artist, image, songId) {
        // Adicionar à fila (simplificado - na prática você pode querer gerenciar uma fila melhor)
        songsQueue = [{
            audio: audioFile,
            title: title,
            artist: artist,
            image: image,
            id: songId
        }];
        
        playSong(0);
    };
</script>