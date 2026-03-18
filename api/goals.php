<?php
/**
 * API - Metas Financeiras
 * CRUD completo de metas por grupo
 */

session_start();
require_once '../db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['group_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$group_id = (int) $_SESSION['group_id'];
$method   = $_SERVER['REQUEST_METHOD'];
$input    = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'GET':
        listGoals($pdo, $group_id);
        break;
    case 'POST':
        createGoal($pdo, $group_id, $input);
        break;
    case 'PUT':
        $id = (int) ($_GET['id'] ?? 0);
        updateGoal($pdo, $group_id, $id, $input);
        break;
    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        deleteGoal($pdo, $group_id, $id);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
}

function listGoals($pdo, $group_id) {
    $stmt = $pdo->prepare("
        SELECT g.*,
               ROUND((g.current_amount / g.target_amount) * 100, 1) as progress_pct,
               DATEDIFF(g.deadline, CURDATE()) as days_remaining
        FROM financial_goals g
        WHERE g.group_id = ?
        ORDER BY g.deadline ASC, g.created_at DESC
    ");
    $stmt->execute([$group_id]);
    $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $goals]);
}

function createGoal($pdo, $group_id, $input) {
    $required = ['name', 'target_amount'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(422);
            echo json_encode(['error' => "Campo '{$field}' é obrigatório"]);
            return;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO financial_goals (group_id, name, target_amount, current_amount, deadline, type)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $group_id,
        $input['name'],
        (float) $input['target_amount'],
        (float) ($input['current_amount'] ?? 0),
        $input['deadline'] ?? null,
        $input['type'] ?? 'outro',
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
}

function updateGoal($pdo, $group_id, $id, $input) {
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID inválido']); return; }

    $stmt = $pdo->prepare("
        UPDATE financial_goals
        SET name = ?, target_amount = ?, current_amount = ?, deadline = ?, type = ?
        WHERE id = ? AND group_id = ?
    ");
    $stmt->execute([
        $input['name'],
        (float) $input['target_amount'],
        (float) $input['current_amount'],
        $input['deadline'] ?? null,
        $input['type'] ?? 'outro',
        $id, $group_id
    ]);

    echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
}

function deleteGoal($pdo, $group_id, $id) {
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID inválido']); return; }

    $stmt = $pdo->prepare("DELETE FROM financial_goals WHERE id = ? AND group_id = ?");
    $stmt->execute([$id, $group_id]);
    echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
}
