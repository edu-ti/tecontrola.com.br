<?php
/**
 * API - Projeção Financeira de 12 Meses
 * Calcula automaticamente a projeção baseada nos dados históricos do grupo
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
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? 'projection';
        if ($action === 'projection') getProjection($pdo, $group_id);
        elseif ($action === 'dre') getDRE($pdo, $group_id);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
}

/**
 * Gera projeção de 12 meses baseada nos dados históricos
 */
function getProjection($pdo, $group_id) {
    $projections = [];
    $now = new DateTime();

    // Média de receitas dos últimos 3 meses
    $stmt = $pdo->prepare("
        SELECT AVG(monthly_total) as avg_income FROM (
            SELECT SUM(amount) as monthly_total
            FROM income
            WHERE group_id = ?
              AND income_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
            GROUP BY YEAR(income_date), MONTH(income_date)
        ) sub
    ");
    $stmt->execute([$group_id]);
    $avg_income = (float) ($stmt->fetchColumn() ?? 0);

    // Total de despesas fixas mensais
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_fixed
        FROM fixed_expenses
        WHERE group_id = ?
    ");
    $stmt->execute([$group_id]);
    $total_fixed = (float) $stmt->fetchColumn();

    // Média de despesas variáveis dos últimos 3 meses
    $stmt = $pdo->prepare("
        SELECT AVG(monthly_total) as avg_variable FROM (
            SELECT SUM(amount / installments) as monthly_total
            FROM variable_expenses
            WHERE group_id = ?
              AND purchase_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
            GROUP BY YEAR(purchase_date), MONTH(purchase_date)
        ) sub
    ");
    $stmt->execute([$group_id]);
    $avg_variable = (float) ($stmt->fetchColumn() ?? 0);

    // Projetar 12 meses
    for ($i = 0; $i < 12; $i++) {
        $month_date = clone $now;
        $month_date->modify("+{$i} months");
        $month = (int) $month_date->format('m');
        $year  = (int) $month_date->format('Y');

        // Parcelas futuras de cartão para este mês
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount / installments), 0) as installment_total
            FROM purchases
            WHERE group_id = ?
              AND DATE_ADD(purchase_date, INTERVAL (installments - initial_installment) MONTH)
                  >= DATE(CONCAT(?, '-', ?, '-01'))
              AND purchase_date <= LAST_DAY(DATE(CONCAT(?, '-', ?, '-01')))
        ");
        $stmt->execute([$group_id, $year, $month, $year, $month]);
        $installments_future = (float) $stmt->fetchColumn();

        // Verificar se há projeção manual salva
        $stmt = $pdo->prepare("
            SELECT projected_income, projected_expense, notes
            FROM financial_projections
            WHERE group_id = ? AND month = ? AND year = ?
        ");
        $stmt->execute([$group_id, $month, $year]);
        $manual = $stmt->fetch(PDO::FETCH_ASSOC);

        $proj_income  = $manual ? (float)$manual['projected_income']  : round($avg_income, 2);
        $proj_expense = $manual ? (float)$manual['projected_expense'] : round($total_fixed + $avg_variable + $installments_future, 2);
        $balance      = round($proj_income - $proj_expense, 2);

        $projections[] = [
            'month'            => $month,
            'year'             => $year,
            'month_label'      => strftime('%b/%Y', mktime(0, 0, 0, $month, 1, $year)),
            'projected_income' => $proj_income,
            'projected_expense'=> $proj_expense,
            'balance'          => $balance,
            'is_manual'        => (bool) $manual,
            'notes'            => $manual['notes'] ?? null,
        ];
    }

    echo json_encode(['success' => true, 'data' => $projections]);
}

/**
 * Gera DRE simplificado mensal e anual
 */
function getDRE($pdo, $group_id) {
    $year = (int) ($_GET['year'] ?? date('Y'));
    $dre  = [];

    for ($m = 1; $m <= 12; $m++) {
        // Receitas
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM income
            WHERE group_id = ? AND YEAR(income_date) = ? AND MONTH(income_date) = ?
        ");
        $stmt->execute([$group_id, $year, $m]);
        $receita = (float) $stmt->fetchColumn();

        // Despesas fixas
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM fixed_expenses
            WHERE group_id = ?
        ");
        $stmt->execute([$group_id]);
        $desp_fixas = (float) $stmt->fetchColumn();

        // Despesas variáveis
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount / installments), 0)
            FROM variable_expenses
            WHERE group_id = ? AND YEAR(purchase_date) = ? AND MONTH(purchase_date) = ?
        ");
        $stmt->execute([$group_id, $year, $m]);
        $desp_variaveis = (float) $stmt->fetchColumn();

        // Cartão
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount / installments), 0)
            FROM purchases
            WHERE group_id = ? AND YEAR(purchase_date) = ? AND MONTH(purchase_date) = ?
        ");
        $stmt->execute([$group_id, $year, $m]);
        $desp_cartao = (float) $stmt->fetchColumn();

        $total_despesas     = $desp_fixas + $desp_variaveis + $desp_cartao;
        $resultado_operacional = $receita - $total_despesas;
        $margem_liquida     = $receita > 0 ? round(($resultado_operacional / $receita) * 100, 1) : 0;

        $dre[$m] = [
            'month'                  => $m,
            'month_label'            => date('M/Y', mktime(0, 0, 0, $m, 1, $year)),
            'receita_bruta'          => round($receita, 2),
            'despesas_fixas'         => round($desp_fixas, 2),
            'despesas_variaveis'     => round($desp_variaveis, 2),
            'despesas_cartao'        => round($desp_cartao, 2),
            'total_despesas'         => round($total_despesas, 2),
            'resultado_operacional'  => round($resultado_operacional, 2),
            'margem_liquida'         => $margem_liquida,
        ];
    }

    $totais = [
        'receita_bruta'         => array_sum(array_column($dre, 'receita_bruta')),
        'total_despesas'        => array_sum(array_column($dre, 'total_despesas')),
        'resultado_operacional' => array_sum(array_column($dre, 'resultado_operacional')),
    ];
    $totais['margem_liquida'] = $totais['receita_bruta'] > 0
        ? round(($totais['resultado_operacional'] / $totais['receita_bruta']) * 100, 1) : 0;

    echo json_encode(['success' => true, 'year' => $year, 'months' => array_values($dre), 'totais' => $totais]);
}
