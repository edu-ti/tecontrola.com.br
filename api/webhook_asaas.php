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

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true);

if (!$input || !isset($input['event'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$event = $input['event'];
$payment = $input['payment'] ?? [];

$customer_id = $payment['customer'] ?? null;
$payment_id = $payment['id'] ?? null;
$amount = $payment['value'] ?? 0;
$dueDate = $payment['dueDate'] ?? null;
$billingType = $payment['billingType'] ?? null;
$externalReference = $payment['externalReference'] ?? null;
$subscription_id = $payment['subscription'] ?? null;

// Resgate adicional para eventos puros de assinatura
if (!$subscription_id && isset($input['subscription']['id'])) {
    $subscription_id = $input['subscription']['id'];
}
if (!$customer_id && isset($input['subscription']['customer'])) {
    $customer_id = $input['subscription']['customer'];
}
if (!$externalReference && isset($input['subscription']['externalReference'])) {
    $externalReference = $input['subscription']['externalReference'];
}

// 2.1 — Log de auditoria
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$logMsg = sprintf("[%s] EVENT=%s customer=%s payment=%s\n", date('Y-m-d H:i:s'), $event, $customer_id ?? 'null', $payment_id ?? 'null');
file_put_contents($log_dir . '/webhook.log', $logMsg, FILE_APPEND);

if (!$customer_id && !$externalReference) {
    echo json_encode(['received' => true, 'warning' => 'No customer or externalReference']);
    exit;
}

// Find group by customer id
$group = null;
if ($customer_id) {
    $stmt = $pdo->prepare("SELECT id FROM `groups` WHERE asaas_customer_id = ? LIMIT 1");
    $stmt->execute([$customer_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 2.4 — Lookup por externalReference como fallback
if (!$group && $externalReference) {
    $stmt = $pdo->prepare("SELECT id FROM `groups` WHERE id = ? LIMIT 1");
    $stmt->execute([$externalReference]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$group) {
    echo json_encode(['received' => true, 'warning' => 'Group not found']);
    exit;
}
$group_id = $group['id'];

try {
    if ($event === 'PAYMENT_CONFIRMED' || $event === 'PAYMENT_RECEIVED' || $event === 'PAYMENT_RESTORED') {
        $next_due = date('Y-m-d', strtotime('+1 month'));
        $stmt = $pdo->prepare("UPDATE `groups` SET subscription_status = 'active', next_due_date = ?, blocked_at = NULL WHERE id = ?");
        $stmt->execute([$next_due, $group_id]);
        
        if ($payment_id) {
            $stmt = $pdo->prepare("INSERT INTO subscription_payments (group_id, asaas_payment_id, amount, status, payment_method, due_date, paid_at) VALUES (?, ?, ?, 'confirmed', ?, ?, NOW())");
            $stmt->execute([$group_id, $payment_id, $amount, $billingType, $dueDate]);
        }
        
    } elseif ($event === 'PAYMENT_OVERDUE') {
        $stmt = $pdo->prepare("UPDATE `groups` SET subscription_status = 'overdue' WHERE id = ? AND subscription_status != 'blocked'");
        $stmt->execute([$group_id]);
        
    } elseif ($event === 'PAYMENT_DELETED' || $event === 'SUBSCRIPTION_DELETED') {
        $stmt = $pdo->prepare("UPDATE `groups` SET subscription_status = 'blocked', blocked_at = NOW() WHERE id = ?");
        $stmt->execute([$group_id]);
        
    } elseif ($event === 'SUBSCRIPTION_RENEWED') {
        $next_due = date('Y-m-d', strtotime('+1 month'));
        $stmt = $pdo->prepare("UPDATE `groups` SET subscription_status = 'active', next_due_date = ?, blocked_at = NULL WHERE id = ?");
        $stmt->execute([$next_due, $group_id]);
        
        if ($payment_id) {
            $stmt = $pdo->prepare("INSERT INTO subscription_payments (group_id, asaas_payment_id, amount, status, payment_method, due_date, paid_at) VALUES (?, ?, ?, 'confirmed', ?, ?, NOW())");
            $stmt->execute([$group_id, $payment_id, $amount, $billingType, $dueDate]);
        }
        
    } elseif ($event === 'SUBSCRIPTION_CREATED') {
        if ($subscription_id) {
            $stmt = $pdo->prepare("UPDATE `groups` SET asaas_subscription_id = ? WHERE id = ? AND (asaas_subscription_id IS NULL OR asaas_subscription_id = '')");
            $stmt->execute([$subscription_id, $group_id]);
        }
    }
    
    http_response_code(200);
    echo json_encode(['received' => true]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
