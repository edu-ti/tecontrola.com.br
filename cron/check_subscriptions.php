<?php
// cron/check_subscriptions.php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Access denied";
    exit;
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

try {
    $stmt = $pdo->prepare("
        UPDATE `groups` 
        SET subscription_status = 'blocked', blocked_at = NOW()
        WHERE subscription_status = 'overdue' 
          AND next_due_date < DATE_SUB(NOW(), INTERVAL 5 DAY)
    ");
    $stmt->execute();
    $affected = $stmt->rowCount();
    
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
    $log_msg = "[" . date('Y-m-d H:i:s') . "] Bloqueados: $affected grupos.\n";
    file_put_contents("$log_dir/cron.log", $log_msg, FILE_APPEND);
    
    echo "Sucesso. $affected grupos bloqueados.\n";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
