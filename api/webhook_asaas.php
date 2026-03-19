<?php
// api/webhook_asaas.php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

$headers = getallheaders();
$webhookToken = $_ENV['ASAAS_WEBHOOK_TOKEN'] ?? '';

// Validate Token
$receivedToken = $headers['asaas-access-token'] ?? $headers['Asaas-Access-Token'] ?? '';
if ($webhookToken && $receivedToken !== $webhookToken) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['event'], $input['payment'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$event = $input['event'];
$payment = $input['payment'];
$customer_id = $payment['customer'] ?? null;
$payment_id = $payment['id'] ?? null;
$amount = $payment['value'] ?? 0;
$dueDate = $payment['dueDate'] ?? null;
$billingType = $payment['billingType'] ?? null;

if (!$customer_id) {
    echo json_encode(['received' => true]);
    exit;
}

// Find group by customer id
$stmt = $pdo->prepare("SELECT id FROM `groups` WHERE asaas_customer_id = ? LIMIT 1");
$stmt->execute([$customer_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    echo json_encode(['received' => true]);
    exit;
}
$group_id = $group['id'];

try {
    if ($event === 'PAYMENT_CONFIRMED' || $event === 'PAYMENT_RECEIVED') {
        // Find next due date - assume 1 month
        $next_due = date('Y-m-d', strtotime('+1 month'));
        $stmt = $pdo->prepare("UPDATE `groups` SET subscription_status = 'active', next_due_date = ?, blocked_at = NULL WHERE id = ?");
        $stmt->execute([$next_due, $group_id]);
        
        // Register payment
        $stmt = $pdo->prepare("INSERT INTO subscription_payments (group_id, asaas_payment_id, amount, status, payment_method, due_date, paid_at) VALUES (?, ?, ?, 'confirmed', ?, ?, NOW())");
        $stmt->execute([$group_id, $payment_id, $amount, $billingType, $dueDate]);
        
    } elseif ($event === 'PAYMENT_OVERDUE') {
        $stmt = $pdo->prepare("UPDATE `groups` SET subscription_status = 'overdue' WHERE id = ? AND subscription_status != 'blocked'");
        $stmt->execute([$group_id]);
        
    } elseif ($event === 'PAYMENT_DELETED' || $event === 'SUBSCRIPTION_DELETED') {
        $stmt = $pdo->prepare("UPDATE `groups` SET subscription_status = 'blocked', blocked_at = NOW() WHERE id = ?");
        $stmt->execute([$group_id]);
    }
    
    http_response_code(200);
    echo json_encode(['received' => true]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
