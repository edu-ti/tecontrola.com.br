<?php
function loadEnv($path) {
    if (!file_exists($path)) return false;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($name !== NULL && $value !== NULL) {
            $_ENV[trim($name)] = trim(trim($value), '"\'');
        }
    }
    return true;
}

// Carregar .env a partir do mesmo diretório
loadEnv(__DIR__ . '/.env');
