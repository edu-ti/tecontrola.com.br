<?php
// Ativa a exibição de todos os erros do PHP para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicia a sessão para ter acesso às variáveis de login/grupo
session_start();

// Define a ação a ser executada a partir do parâmetro GET
$action = $_GET['action'] ?? null;

// Se nenhuma ação for especificada, termina com um erro.
if (!$action) {
    http_response_code(400); // Bad Request
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Ação não especificada.']);
    exit;
}

// A configuração da base de dados é necessária para todas as ações
try {
    // __DIR__ aponta para a pasta atual (api), /../ volta para a raiz (public_html)
    require_once __DIR__ . '/../db_config.php';
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Falha ao carregar configuração da base de dados: ' . $e->getMessage()]);
    exit;
}

// Define variáveis globais para serem usadas nos ficheiros incluídos
$user_id = $_SESSION['user_id'] ?? null;
$group_id = $_SESSION['group_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

// Roteia a ação para o ficheiro PHP correspondente
switch ($action) {
    case 'register':
        require 'register.php';
        break;
    case 'login':
        require 'login.php';
        break;
    case 'logout':
        require 'logout.php';
        break;
    case 'data':
        require 'data.php';
        break;
        
    // --- ROTA DO GRÁFICO REMOVIDA ---
    // A lógica foi movida para 'data.php' para evitar a condição de corrida
    /*
    case 'chart_data':
        require 'charts.php';
        break;
    */
    case 'notifications':
        require 'notifications.php';
        break;
    // --- FIM DAS ROTAS CORRIGIDAS ---

    // --- ROTAS DE GESTÃO DE GRUPO ---
    case 'members':
        require 'members.php';
        break;
    case 'create_token':
        require 'create_token.php';
        break;
    // --- FIM DAS ROTAS ADICIONADAS ---
    default:
        http_response_code(404); // Not Found
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Ação desconhecida.']);
        break;
}

exit;
?>