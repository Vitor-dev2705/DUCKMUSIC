<?php

/**
 * Gera codigo de verificacao de 6 digitos.
 */
function gerarCodigoVerificacao() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Envia email usando Resend API.
 * Requer env var RESEND_API_KEY.
 */
function enviarEmail($para, $assunto, $html) {
    $apiKey = getenv('RESEND_API_KEY')
        ?: ($_SERVER['RESEND_API_KEY'] ?? '')
        ?: ($_ENV['RESEND_API_KEY'] ?? '');
    $apiKey = trim($apiKey);
    if (!$apiKey) return false;

    $payload = json_encode([
        'from' => 'DuckMusic <onboarding@resend.dev>',
        'to'   => [$para],
        'subject' => $assunto,
        'html' => $html
    ]);

    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Authorization: Bearer $apiKey\r\nContent-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];

    $ctx = stream_context_create($opts);
    $result = @file_get_contents('https://api.resend.com/emails', false, $ctx);

    return $result !== false;
}

/**
 * Envia codigo de verificacao para o email.
 */
function enviarCodigoVerificacao($email, $codigo, $nome) {
    $assunto = "Seu codigo de verificacao - DuckMusic";

    $html = '
    <div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto;background:#121212;color:#fff;padding:32px;border-radius:12px;">
        <div style="text-align:center;margin-bottom:24px;">
            <h1 style="color:#1db954;font-size:24px;margin:0;">DuckMusic</h1>
        </div>
        <p style="color:#b3b3b3;font-size:16px;">Ola, <strong style="color:#fff;">' . htmlspecialchars($nome) . '</strong>!</p>
        <p style="color:#b3b3b3;font-size:15px;">Use o codigo abaixo para verificar seu e-mail:</p>
        <div style="text-align:center;margin:28px 0;">
            <div style="display:inline-block;background:#282828;padding:16px 32px;border-radius:8px;letter-spacing:8px;font-size:32px;font-weight:700;color:#1db954;">' . $codigo . '</div>
        </div>
        <p style="color:#b3b3b3;font-size:13px;text-align:center;">Este codigo expira em <strong>15 minutos</strong>.</p>
        <hr style="border:none;border-top:1px solid #282828;margin:24px 0;">
        <p style="color:#666;font-size:12px;text-align:center;">Se voce nao criou uma conta no DuckMusic, ignore este e-mail.</p>
    </div>';

    return enviarEmail($email, $assunto, $html);
}
