

document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. Lógica de Abas (Tabs) ---
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

    // --- 2. Lógica do Modal de Criar Playlist ---
    const modal = document.getElementById('modalCriarPlaylist');
    const form = document.getElementById('formCriarPlaylist');
    const feedback = document.getElementById('modalFeedback');

    const btnsAbrir = document.querySelectorAll('.btn-trigger-modal-playlist, #btnAbrirModalCriarPlaylist, #btnAbrirModalCriarPlaylistSidebar');

    btnsAbrir.forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            if (modal) {
                modal.style.display = "flex"; 
                form.reset();
                showFeedback('', ''); 
            }
        };
    });

    document.querySelectorAll('#closeModalCriarPlaylist, #btnCancelarCriarPlaylist').forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            modal.style.display = "none";
        };
    });
    console.log("Modal e botões configurados:", { modal, form, feedback, btnsAbrir });

    window.onclick = (event) => {
        if (event.target == modal) modal.style.display = "none";
    };

    // --- 3. Enviar Formulário via Fetch ---
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const nomePlaylist = formData.get('playlist_nome').trim();

            if (!nomePlaylist) {
                showFeedback('O nome da playlist é obrigatório.', 'error');
                return;
            }

            try {
                // Tentativa de fetch com tratamento de erro robusto
                const response = await fetch('/paginas/criar_playlist.php', { 
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                
                let data;
                try {
                    const jsonStart = text.indexOf('{');
                    const cleanJson = text.substring(jsonStart);
                    data = JSON.parse(cleanJson);
                } catch (e) {
                    console.error("Resposta inválida do servidor:", text);
                    showFeedback('Erro interno do servidor. Verifique o console.', 'error');
                    return;
                }

                if (data.status === 'success') {
                    showFeedback('Playlist criada com sucesso!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showFeedback(data.message || 'Erro ao criar playlist.', 'error');
                }
            } catch (error) {
                console.error("Erro na requisição:", error);
                showFeedback('Erro de comunicação com o servidor.', 'error');
            }
        };
    }

    function showFeedback(msg, type) {
        if (!feedback) return;
        feedback.textContent = msg;
        feedback.style.display = msg ? 'block' : 'none';
        feedback.style.marginTop = '10px';
        feedback.style.color = type === 'success' ? '#2ecc71' : '#e74c3c';
    }

    // --- 4. Lógica de Favoritos ---
    document.addEventListener('click', function(e) {
        const btnFav = e.target.closest('.btn-fav');
        if (!btnFav) return;

        e.preventDefault();
        e.stopPropagation();
        const musicaId = btnFav.getAttribute('data-id');
        toggleFavorite(musicaId, btnFav);
    });

    async function toggleFavorite(id, button) {
        try {
            const response = await fetch('../acoes/favoritar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `musica_id=${encodeURIComponent(id)}`
            });
            const data = await response.json();

            if (data.status === 'success') {
                const icon = button.querySelector('i');
                if (data.favoritado) {
                    icon.classList.replace('far', 'fas');
                    icon.classList.add('favorito');
                } else {
                    icon.classList.replace('fas', 'far');
                    icon.classList.remove('favorito');

                    const activeTab = document.querySelector('.library-tab.active');
                    if (activeTab && activeTab.getAttribute('data-tab') === 'favoritas') {
                        const card = button.closest('.card');
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();
                            checkEmptyState('favoritas');
                        }, 300);
                    }
                }
            }
        } catch (error) {
            console.error("Erro ao favoritar:", error);
        }
    }

    // --- 5. Lógica de Tocar Música ---
    document.addEventListener('click', function(e) {
        const card = e.target.closest('.card');
        if (card && !e.target.closest('.btn-fav')) {
            const musicaData = {
                id: card.dataset.id,
                audio: card.dataset.audio,
                titulo: card.dataset.titulo,
                artista: card.dataset.artista,
                capa: card.dataset.capa
            };

            if (window.playSong) {
                window.playSong(musicaData);
            } else if (window.updatePlayer) {
                window.updatePlayer(musicaData);
            } else {
                window.dispatchEvent(new CustomEvent('playSong', { detail: musicaData }));
            }
        }
    });

    function checkEmptyState(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        const cards = container.querySelectorAll('.card');
        if (cards.length === 0) {
            container.innerHTML = `
                <div class="empty-library-message" style="text-align: center; padding: 40px; width: 100%;">
                    <i class="fas fa-heart-broken" style="font-size: 3rem; color: #555; margin-bottom: 15px;"></i>
                    <h3 style="color: #fff;">Sua biblioteca está vazia</h3>
                </div>`;
        }
    }
});