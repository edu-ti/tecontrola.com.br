<?php
// Este ficheiro cria um novo token de convite para um membro
// A sessão, $pdo, $group_id, e $user_role já foram definidos em router.php

// Apenas admins de grupo podem criar tokens de convite
requireAuth();
if ($user_role !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Apenas administradores do grupo podem criar convites.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

try {
    // 1. Gerar um token de convite único
    $token = 'MEMBER-' . bin2hex(random_bytes(16));

    // 2. Inserir o token na base de dados, associado a este grupo
    $stmt = $pdo->prepare("INSERT INTO registration_tokens (token, group_id, is_used) VALUES (?, ?, ?)");
    $stmt->execute([$token, $group_id, 0]);

    echo json_encode(['status' => 'success', 'message' => 'Novo token de convite gerado!', 'token' => $token]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro na base de dados ao criar o token: ' . $e->getMessage()]);
}