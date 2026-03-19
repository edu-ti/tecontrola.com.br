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
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
    
    $log_file = $log_dir . '/cron.log';
    
    $now = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$now] === INÍCIO DA ROTINA ===\n", FILE_APPEND);

    // 1. Move trials expirados para overdue
    $stmtTrials = $pdo->prepare("
        UPDATE `groups` 
        SET subscription_status = 'overdue'
        WHERE subscription_status = 'trial' 
          AND trial_ends_at < CURDATE()
    ");
    $stmtTrials->execute();
    $trialsAffected = $stmtTrials->rowCount();
    $now = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$now] Trials expirados: $trialsAffected grupos movidos para 'overdue'\n", FILE_APPEND);

    // 2. Bloqueia inadimplentes com 5+ dias de atraso (seja trial expirado ou fatura recorrente)
    $stmtBlock = $pdo->prepare("
        UPDATE `groups` 
        SET subscription_status = 'blocked', blocked_at = NOW()
        WHERE subscription_status = 'overdue' 
          AND (
               (next_due_date IS NOT NULL AND next_due_date < DATE_SUB(CURDATE(), INTERVAL 5 DAY))
               OR 
               (next_due_date IS NULL AND trial_ends_at IS NOT NULL AND trial_ends_at < DATE_SUB(CURDATE(), INTERVAL 5 DAY))
          )
    ");
    $stmtBlock->execute();
    $blockedAffected = $stmtBlock->rowCount();
    $now = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$now] Bloqueados por inadimplência: $blockedAffected grupo(s)\n", FILE_APPEND);

    $now = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$now] === FIM DA ROTINA ===\n", FILE_APPEND);
    
    echo "Sucesso. Trials expirados: $trialsAffected. Bloqueados: $blockedAffected.\n";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
