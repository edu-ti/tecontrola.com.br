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
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../app/helpers/auth.php';
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Falha ao carregar configuração da base de dados: ' . $e->getMessage()]);
    exit;
}

// Define variáveis globais para serem usadas nos ficheiros incluídos
$user_id   = $_SESSION['user_id']   ?? null;
$group_id  = $_SESSION['group_id']  ?? null;
$user_role = $_SESSION['user_role'] ?? null;

// Verifica autenticação para recursos protegidos
$public_actions = ['login', 'register'];
if (!in_array($action, $public_actions) && !$user_id) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Não autenticado.']);
    exit;
}

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
    case 'notifications':
        require 'notifications.php';
        break;
    case 'members':
        require 'members.php';
        break;
    case 'create_token':
        require 'create_token.php';
        break;

    // --- MÓDULO FINANCEIRO EMPRESARIAL ---
    case 'financeiro':
        if (!$group_id) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Não autorizado.']);
            exit;
        }
        header('Content-Type: application/json');
        require 'financeiro.php';
        break;
    // --- FIM DO MÓDULO FINANCEIRO ---

    default:
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Ação desconhecida.']);
        break;
}

exit;
?>
