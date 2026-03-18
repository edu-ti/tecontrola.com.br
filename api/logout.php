<?php
// Este ficheiro já espera que a sessão exista.

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Se desejar destruir a sessão completamente, apague também o cookie da sessão.
// Nota: Isto destruirá a sessão e não apenas os dados da sessão!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão.
session_destroy();

echo json_encode(['status' => 'success', 'message' => 'Logout bem-sucedido.']);
