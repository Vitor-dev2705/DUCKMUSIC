<?php
/**
 * Funções de validação centralizadas para o projeto DuckMusic.
 */

/**
 * Valida a senha de acordo com as regras de segurança.
 *
 * - Mínimo 8 caracteres
 * - Pelo menos 1 letra maiúscula, 1 número e 1 caractere especial
 * - Não pode conter o login/nome de usuário (se informado)
 * - Não pode ser uma senha comum presente em lista curta
 *
 * @param string $senha  senha em texto plano
 * @param string|null $login  (opcional) login ou nome de usuário a ser evitado
 * @return array ['ok' => bool, 'msg' => string]
 */
function validarSenha(string $senha, ?string $login = null): array {
    // Comprimento mínimo
    if (strlen($senha) < 8) {
        return ['ok' => false, 'msg' => 'A senha deve ter ao menos 8 caracteres.'];
    }
    // Padrão de complexidade: 1 maiúscula, 1 número, 1 caractere especial
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $senha)) {
        return ['ok' => false, 'msg' => 'A senha precisa conter 1 maiúscula, 1 número e 1 caractere especial.'];
    }
    // Evitar que a senha contenha o login ou parte do nome de usuário
    if ($login !== null && stripos($senha, $login) !== false) {
        return ['ok' => false, 'msg' => 'A senha não pode conter o nome de usuário ou login.'];
    }
    // Lista simples de senhas comuns (ampliar conforme necessidade)
    $senhasComuns = ['12345678', 'password', 'qwerty', 'abc12345', '11111111'];
    if (in_array(strtolower($senha), $senhasComuns, true)) {
        return ['ok' => false, 'msg' => 'Escolha uma senha mais segura (evite senhas comuns).'];
    }
    return ['ok' => true, 'msg' => ''];
}

/**
 * Validação da data de nascimento já existente (mantida aqui para centralizar).
 */
function validarDataNascimento(string $data): bool {
    $hoje = new DateTime();
    $nascimento = DateTime::createFromFormat('Y-m-d', $data);
    if (!$nascimento) return false;
    $idade = $hoje->diff($nascimento)->y;
    return $idade >= 13 && $idade <= 150;
}
?>
