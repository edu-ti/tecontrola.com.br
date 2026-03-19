<?php
require_once __DIR__ . '/subscription.php';

function checkAccess($group_id, $pdo) {
    $status = checkSubscriptionStatus($group_id, $pdo);
    if (!$status) return 'active'; // Se não tiver config, permite
    
    $subStatus = $status['subscription_status'];
    $trialEnds = $status['trial_ends_at'];
    
    if ($subStatus === 'blocked') {
        header('Location: pagamento_pendente.php?reason=blocked');
        exit;
    }
    
    if ($subStatus === 'trial') {
        if ($trialEnds && strtotime($trialEnds) < time()) {
            // Trial expired, block it
            $stmt = $pdo->prepare("UPDATE `groups` SET subscription_status = 'blocked', blocked_at = NOW() WHERE id = ?");
            $stmt->execute([$group_id]);
            header('Location: pagamento_pendente.php?reason=trial_expired');
            exit;
        }
    }
    
    return $subStatus; // 'active', 'overdue', 'trial' allowed to proceed
}
