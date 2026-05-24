-- DuckMusic - Schema PostgreSQL (Neon)
-- Gerado em 2026-05-17

-- Função para auto-update de timestamps
CREATE OR REPLACE FUNCTION atualizar_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.ultima_modificacao = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION atualizar_data_atualizacao()
RETURNS TRIGGER AS $$
BEGIN
    NEW.data_atualizacao = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Tabela usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    nome_completo VARCHAR(100) NOT NULL,
    nome_usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_nascimento DATE DEFAULT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    avatar VARCHAR(255) DEFAULT 'avatar-padrao.jpg',
    biografia TEXT DEFAULT NULL,
    generos_favoritos TEXT DEFAULT NULL,
    tema VARCHAR(50) DEFAULT 'escuro',
    ordem_exibicao VARCHAR(50) DEFAULT 'recentes',
    nivel_admin INTEGER NOT NULL DEFAULT 0,
    possui_estrela_apoio SMALLINT NOT NULL DEFAULT 0,
    status SMALLINT DEFAULT 1
);
CREATE INDEX IF NOT EXISTS idx_usuarios_nivel_admin ON usuarios (nivel_admin);

DROP TRIGGER IF EXISTS trg_usuarios_atualizacao ON usuarios;
CREATE TRIGGER trg_usuarios_atualizacao
    BEFORE UPDATE ON usuarios
    FOR EACH ROW EXECUTE FUNCTION atualizar_data_atualizacao();

-- Tabela artistas
CREATE TABLE IF NOT EXISTS artistas (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE,
    foto_perfil VARCHAR(255) DEFAULT NULL,
    biografia TEXT DEFAULT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_modificacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TRIGGER IF EXISTS trg_artistas_modificacao ON artistas;
CREATE TRIGGER trg_artistas_modificacao
    BEFORE UPDATE ON artistas
    FOR EACH ROW EXECUTE FUNCTION atualizar_timestamp();

-- Tabela albuns
CREATE TABLE IF NOT EXISTS albuns (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    id_artista INTEGER DEFAULT NULL REFERENCES artistas(id) ON DELETE SET NULL,
    ano_lancamento INTEGER DEFAULT NULL,
    caminho_capa VARCHAR(255) DEFAULT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_modificacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_albuns_artista ON albuns (id_artista);

DROP TRIGGER IF EXISTS trg_albuns_modificacao ON albuns;
CREATE TRIGGER trg_albuns_modificacao
    BEFORE UPDATE ON albuns
    FOR EACH ROW EXECUTE FUNCTION atualizar_timestamp();

-- Tabela generos
CREATE TABLE IF NOT EXISTS generos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT DEFAULT NULL
);

-- Tabela musicas
CREATE TABLE IF NOT EXISTS musicas (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    id_artista INTEGER DEFAULT NULL REFERENCES artistas(id) ON DELETE SET NULL,
    id_album INTEGER DEFAULT NULL REFERENCES albuns(id) ON DELETE SET NULL,
    id_genero INTEGER DEFAULT NULL REFERENCES generos(id) ON DELETE SET NULL,
    duracao INTEGER DEFAULT NULL,
    caminho_arquivo VARCHAR(255) NOT NULL,
    caminho_capa VARCHAR(255) DEFAULT NULL,
    id_usuario_upload INTEGER DEFAULT NULL,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_modificacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    visualizacoes INTEGER DEFAULT 0,
    status SMALLINT DEFAULT 1
);
CREATE INDEX IF NOT EXISTS idx_musicas_artista ON musicas (id_artista);
CREATE INDEX IF NOT EXISTS idx_musicas_album ON musicas (id_album);
CREATE INDEX IF NOT EXISTS idx_musicas_genero ON musicas (id_genero);

DROP TRIGGER IF EXISTS trg_musicas_modificacao ON musicas;
CREATE TRIGGER trg_musicas_modificacao
    BEFORE UPDATE ON musicas
    FOR EACH ROW EXECUTE FUNCTION atualizar_timestamp();

-- Tabela avaliacoes
CREATE TABLE IF NOT EXISTS avaliacoes (
    id SERIAL PRIMARY KEY,
    id_usuario INTEGER NOT NULL,
    id_musica INTEGER NOT NULL,
    nota INTEGER DEFAULT NULL CHECK (nota BETWEEN 1 AND 5),
    comentario TEXT DEFAULT NULL,
    data_avaliacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela favoritos
CREATE TABLE IF NOT EXISTS favoritos (
    id_usuario INTEGER NOT NULL,
    id_musica INTEGER NOT NULL,
    data_favoritado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario, id_musica)
);

-- Tabela playlists
CREATE TABLE IF NOT EXISTS playlists (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT DEFAULT NULL,
    id_usuario_criador INTEGER NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_modificacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    caminho_capa VARCHAR(255) DEFAULT NULL,
    publica SMALLINT DEFAULT 1,
    status SMALLINT DEFAULT 1
);

DROP TRIGGER IF EXISTS trg_playlists_modificacao ON playlists;
CREATE TRIGGER trg_playlists_modificacao
    BEFORE UPDATE ON playlists
    FOR EACH ROW EXECUTE FUNCTION atualizar_timestamp();

-- Tabela musicas_playlists
CREATE TABLE IF NOT EXISTS musicas_playlists (
    id_playlist INTEGER NOT NULL,
    id_musica INTEGER NOT NULL,
    data_adicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ordem INTEGER DEFAULT NULL,
    id_usuario_adicionou INTEGER DEFAULT NULL,
    PRIMARY KEY (id_playlist, id_musica)
);

-- Tabela doacoes
CREATE TABLE IF NOT EXISTS doacoes (
    id SERIAL PRIMARY KEY,
    id_usuario INTEGER NOT NULL,
    id_preferencia_mp VARCHAR(255) DEFAULT NULL,
    id_pagamento_mp VARCHAR(255) DEFAULT NULL,
    status_mp VARCHAR(50) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

DROP TRIGGER IF EXISTS trg_doacoes_atualizacao ON doacoes;
CREATE TRIGGER trg_doacoes_atualizacao
    BEFORE UPDATE ON doacoes
    FOR EACH ROW EXECUTE FUNCTION atualizar_data_atualizacao();

-- Tabela logs_registro
CREATE TABLE IF NOT EXISTS logs_registro (
    id SERIAL PRIMARY KEY,
    nome_usuario VARCHAR(100) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    sucesso SMALLINT DEFAULT NULL,
    data_hora TIMESTAMP DEFAULT NULL
);

-- Tabela redefinicoes_senha
CREATE TABLE IF NOT EXISTS redefinicoes_senha (
    id SERIAL PRIMARY KEY,
    id_usuario INTEGER NOT NULL,
    token VARCHAR(255) NOT NULL,
    data_expiracao TIMESTAMP NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilizado SMALLINT DEFAULT 0
);

-- Tabela exclusoes_musicas
CREATE TABLE IF NOT EXISTS exclusoes_musicas (
    id SERIAL PRIMARY KEY,
    id_musica_original INTEGER DEFAULT NULL,
    titulo VARCHAR(255) NOT NULL,
    artista VARCHAR(255) DEFAULT NULL,
    id_usuario_excluiu INTEGER DEFAULT NULL,
    motivo TEXT DEFAULT NULL,
    data_exclusao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela historico_reproducao
CREATE TABLE IF NOT EXISTS historico_reproducao (
    id SERIAL PRIMARY KEY,
    id_usuario INTEGER DEFAULT NULL,
    id_musica INTEGER DEFAULT NULL,
    data_reproducao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL
);

-- Tabela logs_atividade
CREATE TABLE IF NOT EXISTS logs_atividade (
    id SERIAL PRIMARY KEY,
    id_usuario INTEGER DEFAULT NULL,
    tipo_atividade VARCHAR(50) DEFAULT NULL,
    descricao TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela sessoes
CREATE TABLE IF NOT EXISTS sessoes (
    id_sessao VARCHAR(128) PRIMARY KEY,
    id_usuario INTEGER NOT NULL,
    data_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_atividade TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    endereco_ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    dispositivo VARCHAR(100) DEFAULT NULL
);

DROP TRIGGER IF EXISTS trg_sessoes_atividade ON sessoes;
CREATE TRIGGER trg_sessoes_atividade
    BEFORE UPDATE ON sessoes
    FOR EACH ROW
    EXECUTE FUNCTION atualizar_timestamp();
