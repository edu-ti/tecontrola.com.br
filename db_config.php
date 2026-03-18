<?php
// Arquivo de configuração do banco de dados

function hashPassword($password) {
    // PASSWORD_DEFAULT usa o algoritmo mais forte disponível na sua versão do PHP
    return password_hash($password, PASSWORD_DEFAULT);
}

// Definições do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'u540193243_te_controla_db');
define('DB_USER', 'u540193243_te_controla');
define('DB_PASS', 'g3st@03Du4rd0');
define('DB_CHARSET', 'utf8mb4');

// Data Source Name (DSN)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// Opções do PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Variável global para a conexão
$pdo = null;

try {
    // Cria a nova instância do PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // Se a conexão falhar, envia uma resposta de erro JSON e termina a execução.
    // Isso impede que o servidor envie uma página de erro HTML.
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    // Não exponha $e->getMessage() em produção por segurança
    echo json_encode(['status' => 'error', 'message' => 'Falha na conexão com o banco de dados.']);
    exit;
}
?>
