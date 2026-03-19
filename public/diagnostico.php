<?php
session_start();
if (empty($_SESSION['super_admin_logged_in'])) {
    header('HTTP/1.0 403 Forbidden');
    echo "<h1>403 Forbidden - Acesso Negado</h1>";
    exit;
}

// Ativa a exibição de todos os erros para um diagnóstico completo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico do Servidor</h1>";

// 1. Verificar a Versão do PHP
echo "<h2>1. Versão do PHP</h2>";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "<p style='color:green;'>Versão do PHP: " . PHP_VERSION . " (OK, é 7.4 ou superior)</p>";
} else {
    echo "<p style='color:red;'>Versão do PHP: " . PHP_VERSION . " (Atenção: Recomenda-se PHP 7.4 ou superior)</p>";
}

// 2. Verificar a Extensão PDO MySQL
echo "<h2>2. Extensão PDO MySQL</h2>";
if (extension_loaded('pdo_mysql')) {
    echo "<p style='color:green;'>A extensão pdo_mysql está instalada e ativada. (OK)</p>";
} else {
    echo "<p style='color:red;'>ERRO: A extensão pdo_mysql NÃO está ativada. Contacte o suporte da Hostinger para a ativar.</p>";
}


// 3. Testar a Conexão com a Base de Dados
echo "<h2>3. Teste de Conexão com a Base de Dados</h2>";

// Inclui as credenciais do seu ficheiro de configuração
require_once '../config/db.php';

// A variável $pdo é definida no db_config.php. Vamos verificar se foi criada.
if (isset($pdo) && $pdo instanceof PDO) {
    echo "<p style='color:green;'>Conexão com a base de dados '" . DB_NAME . "' estabelecida com sucesso! (OK)</p>";
} else {
    // Esta mensagem só será exibida se o try-catch em db_config.php for removido ou falhar em si.
    echo "<p style='color:red;'>ERRO: A variável \$pdo não foi criada. Verifique as credenciais e o bloco try-catch em db_config.php.</p>";
}

echo "<hr>";
echo "<p>Se todos os testes acima estiverem OK, o problema não está na configuração básica do servidor.</p>";

?>
