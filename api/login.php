<?php
// A variável $pdo já foi definida em router.php

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email'], $input['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Email e senha são obrigatórios.']);
    exit;
}

$email = $input['email'];
$password = $input['password'];

try {
    // Seleciona o utilizador, o ID do seu grupo e a sua função
    $stmt = $pdo->prepare("SELECT id, email, password, name, group_id, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Login bem-sucedido
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['group_id'] = $user['group_id'];
        $_SESSION['user_role'] = $user['role']; // Guarda a função do utilizador (admin ou membro)

        echo json_encode(['status' => 'success']);
    } else {
        // Credenciais inválidas
        echo json_encode(['status' => 'error', 'message' => 'Email ou senha incorretos.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro no servidor.']);
}