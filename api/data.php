<?php
// Este ficheiro contém apenas a lógica para manipular os dados.
// A sessão e a ligação à base de dados são tratadas pelo router.php.

global $pdo, $group_id; // Usa as variáveis globais definidas no router

$method = $_SERVER['REQUEST_METHOD'];

function handle_error($message = 'Erro desconhecido.') {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

if ($method === 'GET') {
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    
    $data = [
        'income' => [], 'fixed_expenses' => [], 'variable_expenses' => [],
        'cards' => [], 'purchases' => [], 'categories' => [],
        'chart_data' => ['labels' => [], 'data' => []] // Adiciona o container dos dados do gráfico
    ];
    
    $current_month_date = "$year-$month-01";

    try {
        // Buscar Configurações do Grupo
        $stmt = $pdo->prepare("SELECT id, name, group_type, show_financial_projection FROM `groups` WHERE id = ?");
        $stmt->execute([$group_id]);
        $data['group_settings'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Buscar Categorias
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE group_id = ? ORDER BY name ASC");
        $stmt->execute([$group_id]);
        $data['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar Entradas
        $stmt = $pdo->prepare("SELECT * FROM income WHERE group_id = ? AND MONTH(income_date) = ? AND YEAR(income_date) = ? ORDER BY income_date DESC");
        $stmt->execute([$group_id, $month, $year]);
        $data['income'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar Despesas Fixas
        $stmt = $pdo->prepare("SELECT f.*, c.name as category_name FROM fixed_expenses f LEFT JOIN categories c ON f.category_id = c.id WHERE f.group_id = ? ORDER BY f.due_day ASC");
        $stmt->execute([$group_id]);
        $data['fixed_expenses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar Despesas Variáveis
        $stmt = $pdo->prepare("
            SELECT v.*, c.name as category_name,
            (TIMESTAMPDIFF(MONTH, v.purchase_date, ?) + v.initial_installment) AS current_installment
            FROM variable_expenses v
            LEFT JOIN categories c ON v.category_id = c.id
            WHERE v.group_id = ?
            AND v.purchase_date <= LAST_DAY(?) 
            AND LAST_DAY(DATE_ADD(v.purchase_date, INTERVAL (v.installments - v.initial_installment) MONTH)) >= ?
            ORDER BY v.purchase_date DESC"
        );
        $stmt->execute([$current_month_date, $group_id, $current_month_date, $current_month_date]);
        $data['variable_expenses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar Cartões
        $stmt = $pdo->prepare("SELECT * FROM cards WHERE group_id = ? ORDER BY name ASC");
        $stmt->execute([$group_id]);
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data['cards'] = $cards;

        // Buscar Compras dos Cartões
        foreach ($cards as $card) {
            $stmt = $pdo->prepare("
                SELECT p.*, c.name as category_name,
                (TIMESTAMPDIFF(MONTH, p.purchase_date, ?) + p.initial_installment) AS current_installment
                FROM purchases p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.card_id = ?
                AND p.group_id = ?
                AND p.purchase_date <= LAST_DAY(?)
                AND LAST_DAY(DATE_ADD(p.purchase_date, INTERVAL (p.installments - p.initial_installment) MONTH)) >= ?
                ORDER BY p.purchase_date DESC"
            );
            $stmt->execute([$current_month_date, $card['id'], $group_id, $current_month_date, $current_month_date]);
            $data['purchases'][$card['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // --- INÍCIO DA LÓGICA DO GRÁFICO (Movido para cá) ---
        $stmt_chart = $pdo->prepare("
            SELECT
                c.name AS category_name,
                SUM(e.amount) AS total_amount
            FROM (
                -- Despesas Fixas (valor total é considerado todo mês)
                SELECT category_id, amount FROM fixed_expenses WHERE group_id = :group_id1 AND category_id IS NOT NULL
                UNION ALL
                -- Despesas Variáveis (apenas o valor da parcela do mês)
                SELECT category_id, amount / installments AS amount
                FROM variable_expenses
                WHERE group_id = :group_id2 AND category_id IS NOT NULL
                AND purchase_date <= LAST_DAY(:current_month_date1) 
                AND LAST_DAY(DATE_ADD(purchase_date, INTERVAL (installments - initial_installment) MONTH)) >= :current_month_date2
                UNION ALL
                -- Compras no Cartão (apenas o valor da parcela do mês)
                SELECT category_id, amount / installments AS amount
                FROM purchases
                WHERE group_id = :group_id3 AND category_id IS NOT NULL
                AND purchase_date <= LAST_DAY(:current_month_date3)
                AND LAST_DAY(DATE_ADD(purchase_date, INTERVAL (installments - initial_installment) MONTH)) >= :current_month_date4
            ) AS e
            JOIN categories c ON e.category_id = c.id
            WHERE c.group_id = :group_id4
            GROUP BY c.id, c.name
            HAVING SUM(e.amount) > 0
            ORDER BY total_amount DESC
        ");

        $stmt_chart->execute([
            ':group_id1' => $group_id,
            ':group_id2' => $group_id,
            ':group_id3' => $group_id,
            ':group_id4' => $group_id,
            ':current_month_date1' => $current_month_date,
            ':current_month_date2' => $current_month_date,
            ':current_month_date3' => $current_month_date,
            ':current_month_date4' => $current_month_date
        ]);

        $chart_results = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

        $data['chart_data'] = [
            'labels' => array_column($chart_results, 'category_name'),
            'data' => array_column($chart_results, 'total_amount')
        ];
        // --- FIM DA LÓGICA DO GRÁFICO ---
        
        echo json_encode(['status' => 'success', 'data' => $data]);

    } catch (PDOException $e) {
        handle_error('Erro na base de dados: ' . $e->getMessage());
    }

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) handle_error('Dados inválidos.');
    $type = $input['type'] ?? '';
    $id = $input['id'] ?? null;

    try {
        switch ($type) {
            case 'income':
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE income SET description=?, amount=?, income_date=?, income_type=?, responsible=? WHERE id=? AND group_id=?");
                    $stmt->execute([$input['description'], $input['amount'], $input['income_date'], $input['income_type'], $input['responsible'] ?? null, $id, $group_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO income (group_id, description, amount, income_date, income_type, responsible) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$group_id, $input['description'], $input['amount'], $input['income_date'], $input['income_type'], $input['responsible'] ?? null]);
                }
                break;
            case 'fixed_expense':
                $category_id = empty($input['category_id']) ? null : $input['category_id'];
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE fixed_expenses SET description=?, amount=?, due_day=?, responsible=?, category_id=? WHERE id=? AND group_id=?");
                    $stmt->execute([$input['description'], $input['amount'], $input['due_day'], $input['responsible'] ?? null, $category_id, $id, $group_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO fixed_expenses (group_id, description, amount, due_day, responsible, category_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$group_id, $input['description'], $input['amount'], $input['due_day'], $input['responsible'] ?? null, $category_id]);
                }
                break;
            case 'variable_expense':
                 $category_id = empty($input['category_id']) ? null : $input['category_id'];
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE variable_expenses SET description=?, amount=?, purchase_date=?, installments=?, initial_installment=?, responsible=?, category_id=? WHERE id=? AND group_id=?");
                    $stmt->execute([$input['description'], $input['amount'], $input['purchase_date'], $input['installments'], $input['initial_installment'], $input['responsible'] ?? null, $category_id, $id, $group_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO variable_expenses (group_id, description, amount, purchase_date, installments, initial_installment, responsible, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$group_id, $input['description'], $input['amount'], $input['purchase_date'], $input['installments'], $input['initial_installment'], $input['responsible'] ?? null, $category_id]);
                }
                break;
            case 'card':
                if ($id) {
                     $stmt = $pdo->prepare("UPDATE cards SET name=?, due_day=?, closing_day=? WHERE id=? AND group_id=?");
                     $stmt->execute([$input['name'], $input['due_day'], $input['closing_day'], $id, $group_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO cards (group_id, name, due_day, closing_day) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$group_id, $input['name'], $input['due_day'], $input['closing_day']]);
                }
                break;
            case 'purchase':
                $category_id = empty($input['category_id']) ? null : $input['category_id'];
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE purchases SET card_id=?, description=?, notes=?, purchased_by=?, amount=?, purchase_date=?, installments=?, initial_installment=?, category_id=? WHERE id=? AND group_id=?");
                    $stmt->execute([$input['card_id'], $input['description'], $input['notes'] ?? null, $input['purchased_by'] ?? null, $input['amount'], $input['purchase_date'], $input['installments'], $input['initial_installment'], $category_id, $id, $group_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO purchases (group_id, card_id, description, notes, purchased_by, amount, purchase_date, installments, initial_installment, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$group_id, $input['card_id'], $input['description'], $input['notes'] ?? null, $input['purchased_by'] ?? null, $input['amount'], $input['purchase_date'], $input['installments'], $input['initial_installment'], $category_id]);
                }
                break;
             case 'category':
                if (empty($input['name'])) handle_error('O nome da categoria é obrigatório.');
                if ($id) {
                     $stmt = $pdo->prepare("UPDATE categories SET name=? WHERE id=? AND group_id=?");
                     $stmt->execute([$input['name'], $id, $group_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (group_id, name) VALUES (?, ?)");
                    $stmt->execute([$group_id, $input['name']]);
                }
                break;
            default: handle_error('Tipo de ação inválido.');
        }
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) { handle_error('Erro ao salvar na base de dados: ' . $e->getMessage()); }

} elseif ($method === 'DELETE') {
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? 0;
    if (!$type || !$id) handle_error('Parâmetros inválidos para exclusão.');
    
    $table_map = [
        'income' => 'income', 
        'fixed_expense' => 'fixed_expenses', 
        'variable_expense' => 'variable_expenses',
        'card' => 'cards', 
        'purchase' => 'purchases',
        'category' => 'categories'
    ];

    if (!array_key_exists($type, $table_map)) handle_error('Tipo inválido para exclusão.');
    $table = $table_map[$type];

    try {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ? AND group_id = ?");
        $stmt->execute([$id, $group_id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            handle_error('Item não encontrado ou pertence a outro grupo.');
        }
    } catch (PDOException $e) { handle_error('Erro ao excluir da base de dados: ' . $e->getMessage()); }
}

