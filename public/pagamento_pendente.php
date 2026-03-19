<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$reason = $_GET['reason'] ?? 'blocked';
$message = 'Seu acesso foi suspenso temporariamente. Isso pode ocorrer devido ao fim do período de testes (trial) ou faturas pendentes em sua assinatura.';
if ($reason === 'trial_expired') {
    $message = 'Seu período de teste grátis expirou! Assine agora para continuar usando o sistema.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Pendente - Te Controla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-center">
        <i class="fas fa-lock text-5xl text-red-500 mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Acesso Bloqueado</h2>
        <p class="text-gray-600 mb-6"><?= htmlspecialchars($message) ?></p>
        
        <div class="space-y-4">
            <button onclick="alert('Funcionalidade de PIX via Asaas em breve!')" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition duration-200 font-semibold flex items-center justify-center">
                <i class="fas fa-qrcode mr-2"></i> Pagar via PIX Agora
            </button>
            <button onclick="alert('Funcionalidade de Cartão via Asaas em breve!')" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold flex items-center justify-center">
                <i class="fas fa-credit-card mr-2"></i> Atualizar Cartão de Crédito
            </button>
            <a href="mailto:suporte@tecontrola.com.br" class="block w-full bg-gray-200 text-gray-800 py-2 rounded-lg hover:bg-gray-300 transition duration-200 font-medium mt-4">
                Falar com o Suporte
            </a>
            <a href="../api/router.php?action=logout" class="block mt-4 text-sm text-red-600 hover:underline">Sair</a>
        </div>
    </div>
</body>
</html>
