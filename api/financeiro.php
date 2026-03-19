<?php
// api/financeiro.php
// Módulo de Projeção Financeira Empresarial - TeControla
// Usa as variáveis globais $pdo e $group_id definidas pelo router.php

global $pdo, $group_id;

$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['subaction'] ?? 'projecao'; // sub-ação: projecao | dre

function financeiro_error($message = 'Erro desconhecido.') {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

$stmt_group = $pdo->prepare("SELECT group_type, show_financial_projection FROM `groups` WHERE id = ?");
$stmt_group->execute([$group_id]);
$group_settings = $stmt_group->fetch(PDO::FETCH_ASSOC);

if (!$group_settings || $group_settings['group_type'] !== 'empresa' || (int)$group_settings['show_financial_projection'] !== 1) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Módulo financeiro empresarial não habilitado para este grupo.'
    ]);
    exit;
}

if ($method === 'GET') {
    $year = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

    try {

        // -------------------------------------------------------
        // SUBACTION: projecao
        // -------------------------------------------------------
        if ($action === 'projecao') {
            $projection = [];

            for ($m = 1; $m <= 12; $m++) {
                $month_str      = sprintf('%04d-%02d-01', $year, $m);
                $month_last_day = date('Y-m-t', strtotime($month_str));
                $month_label    = date('M/Y', strtotime($month_str));

                // Receitas do mês
                $stmt = $pdo->prepare(
                    "SELECT COALESCE(SUM(amount),0)
                     FROM income
                     WHERE group_id=? AND MONTH(income_date)=? AND YEAR(income_date)=?"
                );
                $stmt->execute([$group_id, $m, $year]);
                $receitas = (float)$stmt->fetchColumn();

                // Despesas fixas (recorrentes todo mês)
                $stmt = $pdo->prepare(
                    "SELECT COALESCE(SUM(amount),0) FROM fixed_expenses WHERE group_id=?"
                );
                $stmt->execute([$group_id]);
                $despesas_fixas = (float)$stmt->fetchColumn();

                // Despesas variáveis com parcelas ativas
                $stmt = $pdo->prepare(
                    "SELECT COALESCE(SUM(amount/installments),0)
                     FROM variable_expenses
                     WHERE group_id=?
                       AND purchase_date <= ?
                       AND LAST_DAY(DATE_ADD(purchase_date, INTERVAL (installments-initial_installment) MONTH)) >= ?"
                );
                $stmt->execute([$group_id, $month_last_day, $month_str]);
                $despesas_variaveis = (float)$stmt->fetchColumn();

                // Parcelas de cartão ativas
                $stmt = $pdo->prepare(
                    "SELECT COALESCE(SUM(p.amount/p.installments),0)
                     FROM purchases p
                     WHERE p.group_id=?
                       AND p.purchase_date <= ?
                       AND LAST_DAY(DATE_ADD(p.purchase_date, INTERVAL (p.installments-p.initial_installment) MONTH)) >= ?"
                );
                $stmt->execute([$group_id, $month_last_day, $month_str]);
                $parcelas_cartao = (float)$stmt->fetchColumn();

                $total_despesas = $despesas_fixas + $despesas_variaveis + $parcelas_cartao;
                $saldo          = $receitas - $total_despesas;

                $projection[] = [
                    'month'              => $month_label,
                    'month_num'          => $m,
                    'receitas'           => round($receitas, 2),
                    'despesas_fixas'     => round($despesas_fixas, 2),
                    'despesas_variaveis' => round($despesas_variaveis, 2),
                    'parcelas_cartao'    => round($parcelas_cartao, 2),
                    'total_despesas'     => round($total_despesas, 2),
                    'saldo'              => round($saldo, 2),
                ];
            }

            // Acumulado
            $acumulado = 0;
            foreach ($projection as &$row) {
                $acumulado       += $row['saldo'];
                $row['acumulado'] = round($acumulado, 2);
            }
            unset($row);

            // KPIs
            $total_receitas_ano = array_sum(array_column($projection, 'receitas'));
            $total_despesas_ano = array_sum(array_column($projection, 'total_despesas'));
            $meses_deficit      = count(array_filter($projection, fn($r) => $r['saldo'] < 0));

            echo json_encode([
                'status'     => 'success',
                'year'       => $year,
                'projection' => $projection,
                'kpis'       => [
                    'total_receitas_ano' => round($total_receitas_ano, 2),
                    'total_despesas_ano' => round($total_despesas_ano, 2),
                    'resultado_ano'      => round($total_receitas_ano - $total_despesas_ano, 2),
                    'burn_rate_medio'    => round($total_despesas_ano / 12, 2),
                    'receita_media'      => round($total_receitas_ano / 12, 2),
                    'meses_deficit'      => $meses_deficit,
                    'meses_superavit'    => 12 - $meses_deficit,
                ],
            ]);
        }

        // -------------------------------------------------------
        // SUBACTION: dre
        // -------------------------------------------------------
        elseif ($action === 'dre') {
            $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
            $month_str      = sprintf('%04d-%02d-01', $year, $month);
            $month_last_day = date('Y-m-t', strtotime($month_str));

            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM income WHERE group_id=? AND MONTH(income_date)=? AND YEAR(income_date)=?");
            $stmt->execute([$group_id, $month, $year]);
            $receita_bruta = (float)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM fixed_expenses WHERE group_id=?");
            $stmt->execute([$group_id]);
            $custo_fixo = (float)$stmt->fetchColumn();

            $stmt = $pdo->prepare(
                "SELECT COALESCE(SUM(amount/installments),0) FROM variable_expenses
                 WHERE group_id=? AND purchase_date<=? AND LAST_DAY(DATE_ADD(purchase_date, INTERVAL (installments-initial_installment) MONTH))>=?"
            );
            $stmt->execute([$group_id, $month_last_day, $month_str]);
            $custo_variavel = (float)$stmt->fetchColumn();

            $stmt = $pdo->prepare(
                "SELECT COALESCE(SUM(p.amount/p.installments),0) FROM purchases p
                 WHERE p.group_id=? AND p.purchase_date<=? AND LAST_DAY(DATE_ADD(p.purchase_date, INTERVAL (p.installments-p.initial_installment) MONTH))>=?"
            );
            $stmt->execute([$group_id, $month_last_day, $month_str]);
            $custo_cartao = (float)$stmt->fetchColumn();

            $custo_total   = $custo_fixo + $custo_variavel + $custo_cartao;
            $lucro_bruto   = $receita_bruta - $custo_variavel - $custo_cartao;
            $lucro_liquido = $receita_bruta - $custo_total;

            echo json_encode([
                'status' => 'success',
                'month'  => $month,
                'year'   => $year,
                'dre'    => [
                    'receita_bruta'    => round($receita_bruta, 2),
                    'custos_variaveis' => round($custo_variavel + $custo_cartao, 2),
                    'lucro_bruto'      => round($lucro_bruto, 2),
                    'custos_fixos'     => round($custo_fixo, 2),
                    'lucro_liquido'    => round($lucro_liquido, 2),
                    'margem_liquida'   => $receita_bruta > 0
                        ? round(($lucro_liquido / $receita_bruta) * 100, 1) : 0,
                ],
            ]);
        }

        else {
            financeiro_error('Sub-ação inválida. Use subaction=projecao ou subaction=dre');
        }

    } catch (PDOException $e) {
        financeiro_error('Erro na base de dados: ' . $e->getMessage());
    }

} else {
    financeiro_error('Método HTTP não suportado.');
}
