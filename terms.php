<?php
$termos_duck = "Termos de Uso - Duck Music!!!   ";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $termos_duck; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333e33;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a3a 100%);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: #f9ff00;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .titulo-principal {
            color: #4ecdc4; 
            text-align: center;
            margin-bottom: 20px;
            font-size: 2.2em;
        }

        .subtitulo {
            color: #4ecdc4;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
            margin-top: 25px;
        }

        .texto, .lista {
            margin-bottom: 15px;
        }

        .lista {
            padding-left: 20px;
        }

        .item-lista {
            margin-bottom: 8px;
        }

        .destaque {
            background-color:rgb(150, 83, 238);
            padding: 15px;
            border-left: 4px solid #4ecdc4;
            margin: 20px 0;
            border-radius: 4px;
        }

        .data-atualizacao {
            text-align: right;
            font-style: italic;
            color: #666;
            margin-bottom: 20px;
        }

        .contato {
            margin-top: 30px;
            background-color:rgb(209, 233, 87);
            padding: 15px;
            border-radius: 5px;
        }

        .rodape {
            text-align: center;
            margin-top: 30px;
            color: #777;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="titulo-principal">Termos de Uso</h1>
        <p class="data-atualizacao"><strong>Última atualização:</strong> <?php echo date('d/m/Y'); ?></p>

        <div class="destaque">
            <strong>Duck Music</strong> é um site gratuito que respeita os direitos autorais. 
            Nosso objetivo é oferecer música de forma legal e acessível para todos.
        </div>

        <h2 class="subtitulo">1. Definições</h2>
        <p class="texto"><strong>"Duck Music"</strong> é uma plataforma de streaming musical gratuita desenvolvida para fins educacionais e de entretenimento.</p>
        <p class="texto"><strong>"Usuário"</strong> é qualquer pessoa que acesse ou utilize o site.</p>
        <p class="texto"><strong>"Conteúdo"</strong> refere-se a músicas, textos, imagens e outros materiais disponíveis no site.</p>

        <h2 class="subtitulo">2. Uso do Site</h2>
        <p class="texto">O Duck Music permite:</p>
        <ul class="lista">
            <li class="item-lista">Ouvir músicas gratuitamente para uso pessoal</li>
            <li class="item-lista">Criar playlists e favoritar músicas</li>
            <li class="item-lista">Compartilhar músicas através de links</li>
        </ul>

        <p class="texto">É <strong>proibido</strong>:</p>
        <ul class="lista">
            <li class="item-lista">Utilizar o conteúdo para fins comerciais</li>
            <li class="item-lista">Modificar, redistribuir ou vender o conteúdo do site</li>
            <li class="item-lista">Realizar scraping ou tentativas de acesso não autorizado</li>
        </ul>

        <h2 class="subtitulo">3. Direitos Autorais</h2>
        <p class="texto">O Duck Music opera dentro da legalidade:</p>
        <ul class="lista">
            <li class="item-lista">Todas as músicas são disponibilizadas com autorização ou através de licenças adequadas</li>
            <li class="item-lista">Respeitamos os direitos dos artistas e gravadoras</li>
            <li class="item-lista">Se você é detentor de direitos e identificou algum problema, entre em contato para remoção imediata</li>
        </ul>

        <h2 class="subtitulo">4. Responsabilidades</h2>
        <p class="texto">O Duck Music não se responsabiliza por:</p>
        <ul class="lista">
            <li class="item-lista">Uso indevido do conteúdo por terceiros</li>
            <li class="item-lista">Problemas técnicos temporários</li>
            <li class="item-lista">Conteúdo de sites externos vinculados</li>
        </ul>

        <div class="contato">
            <h2 class="subtitulo">Contato</h2>
            <p class="texto">Para questões sobre estes termos:</p>
            <p class="texto"><strong>Email:</strong> contato@duckmusic.com</p>
        </div>

        <div class="rodape">
            <p class="texto">Duck Music &copy; <?php echo date('Y'); ?> - Todos os direitos reservados</p>
        </div>
    </div>
</body>
</html>