<?php
// Este ficheiro contém apenas a lógica para buscar as notificações.
// A sessão e a ligação à base de dados são tratadas pelo router.php.

global $pdo, $group_id; // Usa as variáveis globais definidas no router
requireAuth();

$notifications = [];
$today = date('Y-m-d');
$today_day = (int)date('d');
$days_to_check = 3; // Verifica vencimentos hoje, amanhã e depois de amanhã

try {
    // 1. Buscar Despesas Fixas
    $stmt_fixed = $pdo->prepare("
        SELECT id, description, due_day 
        FROM fixed_expenses 
        WHERE group_id = :group_id
    ");
    $stmt_fixed->execute(['group_id' => $group_id]);
    $fixed_expenses = $stmt_fixed->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fixed_expenses as $expense) {
        $due_day = (int)$expense['due_day'];
        
        for ($i = 0; $i < $days_to_check; $i++) {
            $check_day = (int)date('d', strtotime("+$i days"));
            
            if ($due_day === $check_day) {
                $due_date = date('Y-m-d', strtotime("+$i days"));
                $message = '';
                if ($i === 0) $message = '(Vence Hoje)';
                if ($i === 1) $message = '(Vence Amanhã)';
                
                $notifications[] = [
                    'id' => $expense['id'],
                    'type' => 'fixed_expense',
                    'description' => $expense['description'],
                    'due_date_formatted' => date('d/m/Y', strtotime($due_date)),
                    'due_day_message' => $message,
                    'sort_date' => $due_date
                ];
                break; // Sai do loop de dias se já encontrou
            }
        }
    }

    // 2. Buscar Vencimentos de Cartões
    $stmt_cards = $pdo->prepare("
        SELECT id, name, due_day 
        FROM cards 
        WHERE group_id = :group_id
    ");
    $stmt_cards->execute(['group_id' => $group_id]);
    $cards = $stmt_cards->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cards as $card) {
        $due_day = (int)$card['due_day'];
        
        for ($i = 0; $i < $days_to_check; $i++) {
            $check_day = (int)date('d', strtotime("+$i days"));
            
            if ($due_day === $check_day) {
                $due_date = date('Y-m-d', strtotime("+$i days"));
                $message = '';
                if ($i === 0) $message = '(Vence Hoje)';
                if ($i === 1) $message = '(Vence Amanhã)';

                $notifications[] = [
                    'id' => $card['id'],
                    'type' => 'card',
                    'description' => 'Fatura ' . $card['name'],
                    'due_date_formatted' => date('d/m/Y', strtotime($due_date)),
                    'due_day_message' => $message,
                    'sort_date' => $due_date
                ];
                break; // Sai do loop de dias se já encontrou
            }
        }
    }

    // Ordenar as notificações por data de vencimento
    usort($notifications, function($a, $b) {
        return strtotime($a['sort_date']) - strtotime($b['sort_date']);
    });

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => $notifications]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
?>

