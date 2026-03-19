<?php
session_start();

// --- CONFIGURAÇÃO DE SEGURANÇA ---
// Defina aqui a sua senha de Super Admin.
// MUDE ISTO PARA ALGO MUITO SEGURO!
define('SUPER_ADMIN_PASSWORD', 'g3st@03Du4rd0');
// --- FIM DA CONFIGURAÇÃO ---

$error = '';
$success = '';
$is_logged_in = (isset($_SESSION['super_admin_logged_in']) && $_SESSION['super_admin_logged_in'] === true);

// Lógica de Login
if (isset($_POST['password'])) {
    if ($_POST['password'] === SUPER_ADMIN_PASSWORD) {
        $_SESSION['super_admin_logged_in'] = true;
        $is_logged_in = true;
    } else {
        $error = 'Senha incorreta!';
    }
}

// Lógica de Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['super_admin_logged_in']);
    $is_logged_in = false;
    header('Location: admin.php');
    exit;
}

// Lógica de Criação de Grupo
if ($is_logged_in && isset($_POST['group_name'])) {
    try {
        require_once 'db_config.php';

        $group_name = trim($_POST['group_name']);
        $group_type = ($_POST['group_type'] ?? 'pessoal') === 'empresa' ? 'empresa' : 'pessoal';
        $show_projection = isset($_POST['show_financial_projection']) ? 1 : 0;

        if ($group_type !== 'empresa') {
            $show_projection = 0;
        }

        if (empty($group_name)) {
            $error = "O nome do grupo não pode estar vazio.";
        } else {
            $pdo->beginTransaction();

            $stmt_group = $pdo->prepare("
                INSERT INTO `groups` (name, group_type, show_financial_projection)
                VALUES (?, ?, ?)
            ");
            $stmt_group->execute([$group_name, $group_type, $show_projection]);
            $group_id = $pdo->lastInsertId();

            $token = 'ADMIN-' . bin2hex(random_bytes(16));
            $stmt_token = $pdo->prepare("
                INSERT INTO registration_tokens (token, group_id, is_used)
                VALUES (?, ?, ?)
            ");
            $stmt_token->execute([$token, $group_id, 0]);

            $pdo->commit();

            $success = "Grupo '<strong>" . htmlspecialchars($group_name) . "</strong>' criado com sucesso!<br>
            Token de Administrador:<br><strong class='token'>" . htmlspecialchars($token) . "</strong>";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Erro na base de dados: " . $e->getMessage();
    }
}


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Te Controla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/admin.css">
</head>

<body>
    <div class="container">
        <h1 class="header">Painel Super Admin</h1>
        <p class="subtitle">Gestão de Grupos e Clientes</p>

        <?php if (!$is_logged_in): ?>
            <!-- Formulário de Login -->
            <div class="card">
                <form method="POST" action="admin.php">
                    <label for="password">Senha de Acesso:</label>
                    <input type="password" id="password" name="password" required>
                    <button type="submit">Entrar</button>
                    <?php if ($error): ?>
                        <p class="message error"><?= $error ?></p><?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <!-- Painel de Gestão -->
            <a href="admin.php?logout=true" class="logout-link">Sair do Painel</a>
            <div class="card">
                <h2 class="card-title">Criar Novo Grupo de Clientes</h2>
                <form method="POST" action="admin.php">
                    <label for="group_name">Nome do Grupo (ex: Família de João):</label>
                    <input type="text" id="group_name" name="group_name" required>
                    
                    <label for="group_type">Tipo de Grupo:</label>
                    <select id="group_type" name="group_type" onchange="toggleProjectionField()" style="margin-bottom: 1rem; width: 100%; padding: 0.5rem; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="pessoal">Pessoal / Familiar</option>
                        <option value="empresa">Empresa</option>
                    </select>

                    <div id="projection_container" style="display: none; margin-bottom: 1rem; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="show_financial_projection" name="show_financial_projection" value="1" style="width: auto; margin: 0;">
                        <label for="show_financial_projection" style="display: inline;">Habilitar Módulo de Projeção Financeira</label>
                    </div>

                    <button type="submit">Criar Grupo e Gerar Token</button>

                    <script>
                        function toggleProjectionField() {
                            const type = document.getElementById('group_type').value;
                            const container = document.getElementById('projection_container');
                            if (type === 'empresa') {
                                container.style.display = 'flex';
                            } else {
                                container.style.display = 'none';
                                document.getElementById('show_financial_projection').checked = false;
                            }
                        }
                    </script>
                </form>

                <?php if ($error): ?>
                    <p class="message error"><?= $error ?></p><?php endif; ?>
                <?php if ($success): ?>
                    <p class="message success"><?= $success ?></p><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>