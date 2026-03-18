<?php
// A variável $pdo já foi definida em router.php

// Obtém os dados enviados via POST
$input = json_decode(file_get_contents('php://input'), true);

// Validação robusta dos dados de entrada
if (!$input || !isset($input['email'], $input['password'], $input['token'], $input['name']) || empty(trim($input['name']))) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Dados de cadastro incompletos ou inválidos.']);
    exit;
}

$email = $input['email'];
$password = $input['password'];
$token = $input['token'];
$name = trim($input['name']);

try {
    // Inicia a transação
    $pdo->beginTransaction();

    // 1. Procura o token
    $stmt = $pdo->prepare("SELECT id, group_id, is_used FROM registration_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $token_data = $stmt->fetch();

    if (!$token_data) {
        throw new Exception('Chave de cadastro inválida.');
    }

    if ($token_data['is_used']) {
        throw new Exception('Esta chave de cadastro já foi utilizada e não é mais válida.');
    }

    $group_id = $token_data['group_id'];
    $user_role = 'membro'; // Padrão

    // Verifica se é um token de Admin (criado pelo Super Admin)
    if (strpos($token, 'ADMIN-') === 0) {
        // É um token de Admin. O group_id já foi pré-definido pelo Super Admin.
        // Este utilizador será o admin do grupo.
        $user_role = 'admin';
    } else {
        // É um token de Membro (convite). O group_id deve existir.
        if (empty($group_id)) {
            throw new Exception('Esta chave de convite é inválida (não está associada a nenhum grupo).');
        }
    }

    // 2. Verifica se o e-mail já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Este e-mail já está cadastrado.');
    }

    // 3. Cria o novo utilizador
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, name, group_id, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$email, $hashed_password, $name, $group_id, $user_role]);

    // 4. Marca o token como usado
    $stmt_update_token = $pdo->prepare("UPDATE registration_tokens SET is_used = 1 WHERE id = ?");
    $stmt_update_token->execute([$token_data['id']]);

    // Confirma a transação
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Utilizador criado com sucesso.']);

} catch (Exception $e) {
    // Se ocorrer algum erro, reverte a transação
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400); // Usa um código de erro apropriado
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}