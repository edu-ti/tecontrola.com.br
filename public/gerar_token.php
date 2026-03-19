<?php
// Inclui o arquivo de configuração do banco de dados
require_once '../config/db.php';

// Gera um token único e seguro
$token = bin2hex(random_bytes(16));

try {
    // Insere o novo token na tabela. O group_id fica NULO por padrão.
    // O sistema de registro usará isso para saber que deve criar um novo grupo.
    $stmt = $pdo->prepare("INSERT INTO registration_tokens (token) VALUES (?)");
    $stmt->execute([$token]);

    echo "<h1>Chave de Cadastro Gerada com Sucesso!</h1>";
    echo "<p>Use a chave abaixo para se cadastrar no sistema. A primeira pessoa a usar esta chave criará a conta conjunta (o grupo familiar), e a segunda pessoa será adicionada a essa mesma conta.</p>";
    echo "<p>Sua chave é: <strong>" . htmlspecialchars($token) . "</strong></p>";
    echo "<p><strong style='color:red;'>ATENÇÃO:</strong> Por motivos de segurança, delete este arquivo do seu servidor assim que terminar de usá-lo.</p>";

} catch (PDOException $e) {
    // Em caso de erro, exibe uma mensagem
    echo "<h1>Erro ao gerar a chave!</h1>";
    echo "<p>Não foi possível inserir a chave no banco de dados. Verifique a conexão e a estrutura da tabela.</p>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
}
