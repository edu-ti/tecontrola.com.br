<?php
// Este ficheiro contém a lógica para gerir membros de um grupo
// A sessão, $pdo, $group_id, $user_id e $user_role já foram definidos em router.php

$method = $_SERVER['REQUEST_METHOD'];
requireAuth();

// Apenas admins de grupo podem gerir membros
if ($user_role !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Apenas administradores do grupo podem gerir membros.']);
    exit;
}

if ($method === 'GET') {
    try {
        // Busca todos os membros do grupo
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE group_id = ?");
        $stmt->execute([$group_id]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Busca tokens de convite pendentes
        $stmt_tokens = $pdo->prepare("SELECT token FROM registration_tokens WHERE group_id = ? AND is_used = 0");
        $stmt_tokens->execute([$group_id]);
        $tokens = $stmt_tokens->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => ['members' => $members, 'tokens' => $tokens]]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
    }

} elseif ($method === 'DELETE') {
    // Lógica para remover um membro
    $id_to_delete = $_GET['id'] ?? null;

    if (empty($id_to_delete)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID do membro não especificado.']);
        exit;
    }

    // O admin não se pode remover a si mesmo
    if ($id_to_delete == $user_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'O administrador não pode remover a si mesmo.']);
        exit;
    }

    try {
        // Verifica se o utilizador a ser removido pertence ao mesmo grupo
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND group_id = ?");
        $stmt->execute([$id_to_delete, $group_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Membro removido com sucesso.']);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Membro não encontrado neste grupo.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
    }
}