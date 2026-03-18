<?php
// api/financeiro.php
// Módulo de Projeção Financeira Empresarial - TeControla
// Usa as variáveis globais $pdo e $group_id definidas pelo router.php

global $pdo, $group_id;

$method = $_SERVER['REQUEST_METHOD'];

function financeiro_error($message = 'Erro desconhecido.') {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'projecao';
    $year   = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

    try {
        // -------------------------------------------------------
        // ACTION: projecao (Projeção de Fluxo de Caixa 12 meses)
        // -------------------------------------------------------
        if ($action === 'projecao') {
            $projection = [];

            for ($m = 1; $m <= 12; $m++) {
                $month_str      = sprintf('%04d-%02d-01', $year, $m);
                $month_last_day = date('Y-m-t', strtotime($month_str));
                $month_label    = strftime('%b/%Y', strtotime($month_str));

                // -- Receitas do mês --
                $stmt = $pdo->prepare(
                    "SELECT COALESCE(SUM(amount),0) as total
                     FROM income
                     WHERE group_id = ? AND MONTH(income_date) = ? AND YEAR(income_date) = ?"
                );
                $stmt->execute([$group_id, $m, $year]);
                $receitas = (float)$stmt->fetchColumn();

                // -- Despesas Fixas (somadas todo mês pois são recorrentes) --
                $stmt = $pdo->prepare(
                    "SELECT COALESCE(SUM(amount),0) as total
                     FROM fixed_expenses
                     WHERE group_id = ?"
                );
                $stmt->execute([$group_id]);
                $despesas_fixas = (float)$stmt->fetchColumn();

                // -- Despesas Variáveis com parcelas ativas no mês --
                $stmt = $pdo->prepare(
                    "SELECT COALESCE(SUM(amount / installments), 0) as total
                     FROM variable_expenses
                     WHERE group_id = ?
                       AND purchase_date <= ?
                       AND LAST_DAY(DATE_ADD(purchase_date, INTERVAL (installments - initial_installment) MONTH)) >= ?"
                );
                $stmt->execute([$group_id, $month_last_day, $month_str]);
                $despesas_variaveis = (float)$stmt->fetchColumn();

                // -- Parcelas de cartão ativas no mês --
                $stmt = $pdo->prepare(
                    "SELECT COALESCE(SUM(p.amount / p.installments), 0) as total
                     FROM purchases p
                     WHERE p.group_id = ?
                       AND p.purchase_date <= ?
                       AND LAST_DAY(DATE_ADD(p.purchase_date, INTERVAL (p.installments - p.initial_installment) MONTH)) >= ?"
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
                    'saldo'             => round($saldo, 2),
                ];
            }

            // Calcula saldo acumulado
            $acumulado = 0;
            foreach ($projection as &$row) {
                $acumulado       += $row['saldo'];
                $row['acumulado'] = round($acumulado, 2);
            }
            unset($row);

            // KPIs
            $total_receitas_ano   = array_sum(array_column($projection, 'receitas'));
            $total_despesas_ano   = array_sum(array_column($projection, 'total_despesas'));
            $meses_deficit        = count(array_filter($projection, fn($r) => $r['saldo'] < 0));
            $burn_rate_medio      = $total_despesas_ano / 12;
            $receita_media        = $total_receitas_ano / 12;

            echo json_encode([
                'status'     => 'success',
                'year'       => $year,
                'projection' => $projection,
                'kpis'       => [
                    'total_receitas_ano'  => round($total_receitas_ano, 2),
                    'total_despesas_ano'  => round($total_despesas_ano, 2),
                    'resultado_ano'       => round($total_receitas_ano - $total_despesas_ano, 2),
                    'burn_rate_medio'     => round($burn_rate_medio, 2),
                    'receita_media'       => round($receita_media, 2),
                    'meses_deficit'       => $meses_deficit,
                    'meses_superavit'     => 12 - $meses_deficit,
                ],
            ]);
        }

        // -------------------------------------------------------
        // ACTION: dre (DRE Simplificado do mês selecionado)
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

            $custo_total  = $custo_fixo + $custo_variavel + $custo_cartao;
            $lucro_bruto  = $receita_bruta - $custo_variavel - $custo_cartao;
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
                    'margem_liquida'   => $receita_bruta > 0 ? round(($lucro_liquido / $receita_bruta) * 100, 1) : 0,
                ],
            ]);
        }

        else {
            financeiro_error('Ação inválida.');
        }

    } catch (PDOException $e) {
        financeiro_error('Erro na base de dados: ' . $e->getMessage());
    }

} elseif ($method === 'POST') {
    // Reservado para metas financeiras futuras
    financeiro_error('POST ainda não implementado neste módulo.');
} else {
    financeiro_error('Método HTTP não suportado.');
}
