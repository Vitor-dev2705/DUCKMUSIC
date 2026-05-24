
let musicList    = JSON.parse(document.getElementById('musicListData').textContent) || [];
let currentIndex = 0;
let isShuffle    = false;
let isRepeat     = false;
let favorites    = JSON.parse(localStorage.getItem('favorites') || '[]');
const isAdmin    = window.USER_IS_ADMIN === true;
/* --------------------------- 2. ELEMENTOS DOM ----------------------------- */
const audio               = document.getElementById('audio');
const playPauseBtn        = document.getElementById('playPauseBtn');
const currentCover        = document.getElementById('current-cover');
const currentTitle        = document.getElementById('current-title');
const currentArtist       = document.getElementById('current-artist');
const progressBar         = document.getElementById('progressBar');
const currentTimeEl       = document.getElementById('currentTime');
const durationEl          = document.getElementById('duration');
const volumeSlider        = document.getElementById('volume');
const heartIcon           = document.getElementById('heartIcon');
const mainElement         = document.querySelector('main.song_side');
const artistContainer     = document.getElementById('artistCards');
const loadMoreArtistsBtn  = document.getElementById('loadMoreArtistsBtn');
/* ------------------------------ 3. TOAST ---------------------------------- */
const toast = document.createElement('div');
toast.id = 'toast-message';
toast.style.cssText = `
  position:fixed;top:20px;left:50%;transform:translateX(-50%);
  background:#222;color:#fff;padding:10px 20px;border-radius:10px;
  z-index:9999;display:none;font-family:inherit`;
document.body.appendChild(toast);
function showToast(msg, d = 3000) {
  toast.textContent = msg;
  toast.style.display = 'block';
  setTimeout(() => (toast.style.display = 'none'), d);
}
/* ------------------------ 4. FUNÇÕES UTILITÁRIAS -------------------------- */
const fmt = s => `${Math.floor(s/60)}:${Math.floor(s%60).toString().padStart(2,'0')}`;
function updatePlayBtn() {
  playPauseBtn.innerHTML = audio.paused
    ? '<i class="bi bi-play-fill"></i>'
    : '<i class="bi bi-pause-fill"></i>';
}
/* --------- Renderização de cards (grade) --------- */
function renderMusicList(list = musicList) {
  const grid = document.getElementById('musicGrid');
  if (!grid) return;
  grid.innerHTML = list.map(m => `
    <div class="music-card" id="music-${m.id}" onclick="playMusicById(${m.id})">
      <img src="${m.caminho_capa || 'https://via.placeholder.com/150'}" alt="Capa">
      <h5>${m.titulo}</h5>
      <small>${m.artista}</small>
      <button class="play-btn"><i class="bi bi-play-fill"></i></button>
    </div>`).join('');
}
/* ------------------------ 5. AGRUPAMENTO DE ARTISTAS ----------------------- */
function buildArtistFolders() {
  const folders = {};
  musicList.forEach(m => {
    (folders[m.artista] ??= { genre: '', songs: [] }).songs.push(m);
  });
  window.artistFolders = folders;
  window.artistList    = Object.keys(folders);
}
/* ------------------------ 6. PLAYER CONTROLS ------------------------------ */
function playMusicById(id) {
  const idx = musicList.findIndex(m => m.id === id);
  if (idx < 0) return;
  currentIndex = idx;

  const loc = musicList[idx];
  currentCover.src        = loc.caminho_capa || 'https://via.placeholder.com/50';
  currentTitle.textContent  = loc.titulo;
  currentArtist.textContent = loc.artista;

  audio.src = loc.caminho_arquivo || '';
  audio.currentTime = 0;
  audio.play().then(updatePlayBtn).catch(console.error);
  showToast(`🎧 Tocando ${loc.titulo}`);
}
const togglePlay    = () => { audio.paused ? audio.play() : audio.pause(); updatePlayBtn(); };
const toggleShuffle = () => { isShuffle = !isShuffle; showToast(isShuffle ? 'Shuffle ligado' : 'Shuffle desligado'); };
const toggleRepeat  = () => { isRepeat  = !isRepeat;  showToast(isRepeat  ? 'Repeat ligado'  : 'Repeat desligado'); };
const nextSong = () => {
  currentIndex = isShuffle
    ? Math.floor(Math.random() * musicList.length)
    : (currentIndex + 1) % musicList.length;
  playMusicById(musicList[currentIndex].id);
};
const prevSong = () => {
  currentIndex = isShuffle
    ? Math.floor(Math.random() * musicList.length)
    : (currentIndex - 1 + musicList.length) % musicList.length;
  playMusicById(musicList[currentIndex].id);
};

audio.addEventListener('timeupdate', () => {
  if (!isFinite(audio.duration)) return;
  progressBar.value         = (audio.currentTime / audio.duration) * 100;
  currentTimeEl.textContent = fmt(audio.currentTime);
  durationEl.textContent    = fmt(audio.duration);
});
/* ------------------------ 7. FAVORITOS ------------------------------------ */
function toggleLikeCurrent() {
  const id = musicList[currentIndex].id;
  const idx = favorites.indexOf(id);
  idx >= 0 ? favorites.splice(idx, 1) : favorites.push(id);
  localStorage.setItem('favorites', JSON.stringify(favorites));
  updateHeartIcon(); updateFavoritesMenu();
  showToast(idx >= 0 ? '💔 Removida dos favoritos' : '❤️ Adicionada aos favoritos');
}
function updateHeartIcon() {
  heartIcon.classList.toggle('bi-heart-fill', favorites.includes(musicList[currentIndex]?.id));
  heartIcon.classList.toggle('bi-heart',     !favorites.includes(musicList[currentIndex]?.id));
}
function updateFavoritesMenu() {
  const menu = document.getElementById('playlistMenu');
  if (!menu) return;
  let item = menu.querySelector('#favoritesMenuItem');
  if (!item) {
    item = document.createElement('li');
    item.id = 'favoritesMenuItem';
    item.innerHTML = '<i class="bi bi-heart"></i> <span>Favoritos</span>';
    menu.appendChild(item);
  }
  const ativo = favorites.length > 0;
  item.style.opacity       = ativo ? '1' : '0.5';
  item.style.pointerEvents = ativo ? 'auto' : 'none';
  item.onclick             = ativo ? loadFavorites : null;
}
function loadFavorites() {
  const favs = musicList.filter(m => favorites.includes(m.id));
  mainElement.innerHTML = favs.length
    ? `<section class="song_info"><h3>Minhas Curtidas</h3><div id="musicGrid" class="music-grid"></div></section>`
    : `<section class="song_info"><h3>Minhas Curtidas</h3><p>Nenhuma música curtida 😔</p></section>`;
  renderMusicList(favs);
}

/* ------------------- 8. ARTISTAS (grade lateral) -------------------------- */
function renderArtistCards(reset = false) {
  if (!artistContainer) return;
  if (reset) {
    artistContainer.innerHTML = '';
    loadMoreArtistsBtn.style.display = 'block';
    window.artistPage = 0;
  }
  const start = window.artistPage * ARTISTS_PER_PAGE;
  const end   = start + ARTISTS_PER_PAGE;
  window.artistList.slice(start, end).forEach(name => {
    const f = artistFolders[name];
    const img = f.songs[0]?.caminho_capa || 'https://via.placeholder.com/100';
    const card = document.createElement('div');
    card.className = 'artist-card';
    card.innerHTML = `<img src="${img}" alt="${name}"><h5>${name}</h5>`;
    card.onclick = () => {
      mainElement.innerHTML = `
        <section class="song_info">
          <h3>${name}</h3>
          <div id="musicGrid" class="music-grid"></div>
        </section>`;
      renderMusicList(f.songs);
      document.getElementById('musicGrid').scrollIntoView({ behavior: 'smooth' });
    };
    artistContainer.appendChild(card);
  });
  window.artistPage++;
  if (window.artistPage * ARTISTS_PER_PAGE >= artistList.length)
    loadMoreArtistsBtn.style.display = 'none';
}
/* ----------------------- 9. EVENTOS E LISTENERS --------------------------- */
progressBar.addEventListener('input', () => {
  if (isFinite(audio.duration)) audio.currentTime = (progressBar.value / 100) * audio.duration;
});
volumeSlider.addEventListener('input', () => {
  audio.volume = volumeSlider.value;
  localStorage.setItem('player_volume', audio.volume);
});
if (loadMoreArtistsBtn) loadMoreArtistsBtn.addEventListener('click', () => renderArtistCards());
/* ----------------------- 10. CONTEXT MENU (APENAS ADM) -------------------- */
function initContextMenu() {
  if (!isAdmin) return;
  const menu = document.createElement('div');
  menu.id = 'contextMenu';
  Object.assign(menu.style, {
    position: 'absolute',
    display: 'none',
    background: '#333',
    color: '#fff',
    border: '1px solid #444',
    borderRadius: '4px',
    zIndex: '10000'
  });
  menu.innerHTML = `
    <ul style="list-style:none;margin:0;padding:.5rem 0;">
      <li id="ctx-delete" style="padding:.5rem 1rem;cursor:pointer;">🗑️ Excluir</li>
      <li id="ctx-edit"   style="padding:.5rem 1rem;cursor:pointer;">✏️ Editar</li>
      <li id="ctx-add"    style="padding:.5rem 1rem;cursor:pointer;">🎶 Adicionar à playlist</li>
    </ul>`;
  document.body.appendChild(menu);
  document.body.addEventListener('contextmenu', e => {
    const card = e.target.closest('.music-card');
    if (!card) {
      menu.style.display = 'none';
      return;
    }
    e.preventDefault();
    menu.dataset.id = card.id.replace('music-', '');
    menu.style.top  = `${e.pageY}px`;
    menu.style.left = `${e.pageX}px`;
    menu.style.display = 'block';
  });
  document.body.addEventListener('click', e => {
    if (!e.target.closest('#contextMenu')) menu.style.display = 'none';
  });
  menu.querySelector('#ctx-delete').addEventListener('click', () => {
    excluirMusica(menu.dataset.id);
    menu.style.display = 'none';
  });
  menu.querySelector('#ctx-edit').addEventListener('click', () => {
    editarMusicaPrompt(menu.dataset.id);
    menu.style.display = 'none';
  });
  menu.querySelector('#ctx-add').addEventListener('click', () => {
    adicionarPlaylistPrompt(menu.dataset.id);
    menu.style.display = 'none';
  });
}
window.addEventListener('DOMContentLoaded', initContextMenu);
/* ----------------------- 11. DELETAR MÚSICAS (APENAS ADM) ---------------- */
async function excluirMusica(id) {
  try {
    // 1) Dispara requisição
    const res = await fetch('/api/delete_musica.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({ id })
    });
    // 2) Lê o raw response para debug
    const raw = await res.text();
    console.log('[delete_musica] RAW response:', raw);
    // 3) Limpa BOM/quebras e tenta parse
    const clean = raw.replace(/^\uFEFF/, '').trim();
    let json;
    try {
      json = JSON.parse(clean);
    } catch (e) {
      console.error('[delete_musica] JSON inválido:', e, clean);
      showToast('Resposta inválida do servidor', 2000);
      return;
    }
    // 4) Se HTTP não ok, mostra mensagem do servidor ou status
    if (!res.ok) {
      const msg = json.message || `Erro HTTP ${res.status}`;
      showToast(msg, 2000);
      return;
    }
    // 5) Trata resposta JSON
    if (json.status === 'success') {
      document.getElementById(`music-${id}`)?.remove();
      showToast(json.message, 2000);
    } else {
      showToast(json.message || 'Erro desconhecido', 2000);
    }
  } catch (err) {
    console.error('Falha na requisição delete_musica:', err);
    showToast('Falha na requisição', 2000);
  }
}
/* ----------------------- 12.  NICIALIZAÇÃO ------------------------------- */
window.addEventListener('DOMContentLoaded', () => {
  audio.volume = parseFloat(localStorage.getItem('player_volume')) || 0.10;
  volumeSlider.value = audio.volume;
  renderMusicList();
  updateFavoritesMenu();
  buildArtistFolders();
  renderArtistCards(true);
});

/* =================================================================
   13. FUNÇÕES EXTRAS PARA DASHBOARD (álbuns, saudação, busca)
   ================================================================= */
function definirSaudacao() {
  const el = document.getElementById('saudacao');
  if (!el) return;
  const h = new Date().getHours();
  el.textContent = h < 12 && h >= 5 ? 'Bom dia' : h < 18 ? 'Boa tarde' : 'Boa noite';
}
function montarAlbuns() {
  const tag = document.getElementById('musicListData');
  const box = document.getElementById('albumsContainer');
  if (!tag || !box) return;
  const tracks = JSON.parse(tag.textContent || '[]');
  if (!tracks.length) {
    box.innerHTML = "<p style='text-align:center;margin-top:20px'>Nenhuma música.</p>";
    return;
  }
  const byArtist = {};
  tracks.forEach(t => (byArtist[t.artista] ??= []).push(t));
  box.innerHTML = '';
  for (const [art, arr] of Object.entries(byArtist)) {
    const capa = arr[0].caminho_capa || 'https://via.placeholder.com/150';
    box.insertAdjacentHTML('beforeend', `
      <div class="album-card" onclick="abrirAlbum('${art.replace(/'/g,"\\'")}')">
        <img src="${capa}" alt="${art}">
        <h5>${art}</h5><small>${arr.length} faixa(s)</small>
      </div>`);
  }
}
function abrirAlbum(art) {
  const tracks = JSON.parse(document.getElementById('musicListData').textContent || '[]')
                   .filter(t => t.artista === art);
  const box = document.getElementById('albumsContainer');
  if (!box) return;
  box.innerHTML = `
    <div class="album-detalhes">
      <h3>${art}</h3>
      <button class="voltar-btn" onclick="montarAlbuns()">Voltar</button>
      <ul class="musicas-lista">
        ${tracks.map(m => `
          <li onclick="playMusicById(${m.id})">
            <img src="${m.caminho_capa || 'https://via.placeholder.com/50'}" alt="">
            <div class="musica-info"><span>${m.titulo}</span><br><span>${m.artista}</span></div>
            <i class="bi bi-play-circle-fill"></i>
          </li>`).join('')}
      </ul>
    </div>`;
}
function searchPlaylists() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('.playlist-card').forEach(card => {
    const nome = card.querySelector('h5').textContent.toLowerCase();
    card.style.display = nome.includes(q) ? '' : 'none';
  });
}
// Exponha a função no escopo global antes de usá-la:
window.navigateTo = async function(page) {
  // 1) Mostra o loading
  Swal.fire({
    title: 'Carregando...',
    didOpen: () => Swal.showLoading(),
    allowOutsideClick: false
  });
  try {
    // 2) Busca só o main via AJAX
    const res  = await fetch(page + '?ajax=1');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const html = await res.text();
    // 3) Extrai e injeta o <main>
    const doc     = new DOMParser().parseFromString(html, 'text/html');
    const newMain = doc.querySelector('main.song_side');
    const main    = document.querySelector('main.song_side');
    if (!newMain) {
      main.innerHTML = '<h2 style="padding:40px">Página não encontrada</h2>';
      Swal.fire('Erro', 'Página não encontrada', 'error');
      return;
    }
    main.innerHTML = newMain.innerHTML;
    history.pushState({}, '', page);
    // 4) Re–inicializa apenas o que precisar
    typeof definirSaudacao   === 'function' && definirSaudacao();
    typeof montarAlbuns       === 'function' && montarAlbuns();
    typeof renderMusicList    === 'function' && renderMusicList();
    typeof initContextMenu    === 'function' && initContextMenu();

    // 5) Fecha o loading
    Swal.close();
  }
  catch (err) {
    console.error('Erro ao navegar:', err);
    Swal.fire('Erro', 'Não foi possível carregar a página', 'error');
  }
};
// Registre os listeners apenas após o DOM estar pronto:
window.addEventListener('DOMContentLoaded', () => {
  // botão voltar
  window.addEventListener('popstate', () =>
    window.navigateTo(location.pathname)
  );
  // cliques no menu com data-path
  document.querySelectorAll('.menu_side [data-path]').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      window.navigateTo(link.getAttribute('data-path'));
    });
  });
});
/* =================================================================
   13. FUNÇÕES PARA CRIAR, EDITAR E DELETAR PLAYLIST
   ================================================================= */
// -- Toast para playlist --
const playlistToast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 2000,
  timerProgressBar: true
});
function showPlaylistToast(msg, icon='success') {
  playlistToast.fire({ icon, title: msg });
}
// -- Criar Playlist --
async function criarPlaylist() {
  // 1) Abre modal com SweetAlert2 pedindo nome e capa
  const { value, isConfirmed } = await Swal.fire({
    title: 'Nova playlist',
    html: `
      <input id="swal-nome" class="swal2-input" placeholder="Nome da playlist">
      <input type="file" id="swal-imagem" class="swal2-file" accept="image/*">`,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Criar',
    cancelButtonText: 'Cancelar',
    preConfirm: () => {
      const nome = document.getElementById('swal-nome').value.trim();
      if (!nome) {
        Swal.showValidationMessage('Digite um nome para a playlist');
        return;
      }
      const imgFile = document.getElementById('swal-imagem').files[0] || null;
      return { nome, imgFile };
    }
  });
  if (!isConfirmed || !value) return;

  const { nome, imgFile } = value;
  const formData = new FormData();
  formData.append('nome', nome);
  if (imgFile) formData.append('imagem', imgFile);

  try {
    const res = await fetch('/api/criar_playlist.php', {
      method: 'POST',
      body: formData
    });
    const json = await res.json();

    if (json.status === 'success') {
      await Swal.fire({
        icon: 'success',
        title: 'Sucesso',
        text: json.message,
        timer: 1500,
        showConfirmButton: false
      });
      // Insere novo card sem recarregar a página
      const container = document.querySelector('#playlists-container');
      if (container) {
        const card = document.createElement('div');
        card.id = `playlist-${json.id}`;
        card.className = 'playlist-card';
        card.innerHTML = `
          <h5>${nome}</h5>
          <button onclick="editarPlaylist(${json.id}, event)">✏️</button>
          <button onclick="deletarPlaylist(${json.id}, event)">🗑️</button>
        `;
        container.prepend(card);
      }
    } else {
      await Swal.fire({
        icon: 'error',
        title: 'Erro',
        text: json.message
      });
    }
  } catch (err) {
    console.error('Erro ao criar playlist:', err);
    await Swal.fire({
      icon: 'error',
      title: 'Erro de conexão',
      text: 'Não foi possível se comunicar com o servidor'
    });
  }
}
document.addEventListener('DOMContentLoaded', () => {
  // Função de toast para feedback
  function showPlaylistToast(msg, icon = 'success') {
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon,
      title: msg,
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
    });
  }
  // -- Editar --
  window.editarPlaylist = async (id, e) => {
    e.stopPropagation();
    const { value: nome } = await Swal.fire({
      title: 'Editar playlist',
      input: 'text',
      inputLabel: 'Novo nome',
      inputValue: document.querySelector(`#playlist-${id} h5`).innerText,
      showCancelButton: true,
      confirmButtonText: 'Salvar'
    });
    if (!nome) return;
    try {
      const res = await fetch('/api/editar_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id, nome })
      });
      const json = await res.json();
      if (json.status === 'success') {
        document.querySelector(`#playlist-${id} h5`).innerText = nome;
        showPlaylistToast(json.message, 'success');
      } else {
        showPlaylistToast(json.message, 'error');
      }
    } catch {
      showPlaylistToast('Falha na requisição', 'error');
    }
  };
  // - Deletar --
window.deletarPlaylist = async (id, e) => {
  e.stopPropagation();
  const { isConfirmed } = await Swal.fire({
    title: 'Excluir playlist?',
    text: 'Não poderá desfazer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sim, excluir',
    confirmButtonColor: '#d33'
  });
  if (!isConfirmed) return;
  try {
    // Atenção: caminho relativo (respeita o <base href>)
    const res = await fetch('/api/delete_playlist.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({ id })
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    // 1) Leia como texto puro e logue:
    const raw = await res.text();
    console.log('[delete_playlist] RAW response:', JSON.stringify(raw));

    // 2) Remove BOM e espaços/br que possam vir antes do JSON
    const clean = raw.replace(/^\uFEFF/, '').trim();
    console.log('[delete_playlist] CLEAN response:', JSON.stringify(clean));
    // 3) Agora sim parseie:
    let json;
    try {
      json = JSON.parse(clean);
    } catch (parseErr) {
      console.error('[delete_playlist] JSON inválido:', parseErr);
      showPlaylistToast('Resposta inválida do servidor', 'error');
      return;
    }
    // 4) Trate o resultado
    if (json.status === 'success') {
      document.getElementById(`playlist-${id}`)?.remove();
      showPlaylistToast(json.message, 'success');
    } else {
      showPlaylistToast(json.message || 'Erro desconhecido', 'error');
    }
  } catch (err) {
    console.error('[delete_playlist] Erro no fetch:', err);
    showPlaylistToast('Falha na requisição', 'error');
  }
};
});