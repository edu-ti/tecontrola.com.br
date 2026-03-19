<?php
session_start();
require_once '../config/env.php';

// --- CONFIGURAÇÃO DE SEGURANÇA ---
define('SUPER_ADMIN_PASSWORD', $_ENV['SUPER_ADMIN_PASSWORD'] ?? 'g3st@03Du4rd0');
// --- FIM DA CONFIGURAÇÃO ---

$error = '';
$success = '';
$is_logged_in = (isset($_SESSION['super_admin_logged_in']) && $_SESSION['super_admin_logged_in'] === true);

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
    $remaining_lockout = ceil(($_SESSION['lockout_time'] - time()) / 60);
    $error = "Muitas tentativas falhadas. Tente novamente em {$remaining_lockout} minutos.";
} elseif (isset($_SESSION['lockout_time']) && time() >= $_SESSION['lockout_time']) {
    unset($_SESSION['lockout_time']);
    $_SESSION['login_attempts'] = 0;
}

// Lógica de Login
if (isset($_POST['password']) && !$is_logged_in && empty($error)) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Token CSRF inválido!';
    } else {
        if (hash_equals(SUPER_ADMIN_PASSWORD, $_POST['password'])) {
            session_regenerate_id(true);
            $_SESSION['super_admin_logged_in'] = true;
            $_SESSION['login_attempts'] = 0;
            $is_logged_in = true;
        } else {
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['lockout_time'] = time() + (10 * 60);
                $error = 'Muitas tentativas falhadas. Login bloqueado por 10 minutos.';
            } else {
                $error = 'Senha incorreta!';
            }
        }
    }
}

// Lógica de Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['super_admin_logged_in']);
    session_regenerate_id(true);
    $is_logged_in = false;
    header('Location: admin.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Variáveis para listar grupos
$all_groups = [];
$stats = ['active'=>0, 'trial'=>0, 'overdue'=>0, 'blocked'=>0];

if ($is_logged_in) {
    try {
        require_once '../config/db.php';
        
        // Tratar Ações nos Grupos
        if (isset($_POST['action']) && isset($_POST['group_id'])) {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $error = 'Token CSRF inválido!';
            } else {
                $gid = (int)$_POST['group_id'];
                $act = $_POST['action'];
                if ($act === 'block') {
                    $pdo->prepare("UPDATE `groups` SET subscription_status='blocked', blocked_at=NOW() WHERE id=?")->execute([$gid]);
                    $success = "Grupo bloqueado com sucesso!";
                } elseif ($act === 'unblock') {
                    $pdo->prepare("UPDATE `groups` SET subscription_status='active', blocked_at=NULL WHERE id=?")->execute([$gid]);
                    $success = "Grupo desbloqueado e ativado!";
                } elseif ($act === 'set_trial') {
                    $days = (int)($_POST['trial_days'] ?? 7);
                    $trial_end = date('Y-m-d', strtotime("+$days days"));
                    $pdo->prepare("UPDATE `groups` SET subscription_status='trial', trial_ends_at=? WHERE id=?")->execute([$trial_end, $gid]);
                    $success = "Trial renovado por $days dias!";
                }
            }
        }

        // Lógica de Criação de Grupo
        if (isset($_POST['group_name']) && empty($_POST['action'])) {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $error = 'Token CSRF inválido!';
            } else {
                $group_name = trim($_POST['group_name']);
                $group_type = ($_POST['group_type'] ?? 'pessoal') === 'empresa' ? 'empresa' : 'pessoal';
                $show_projection = isset($_POST['show_financial_projection']) ? 1 : 0;
                $initial_status = $_POST['initial_status'] ?? 'trial_7';
                
                if ($group_type !== 'empresa') {
                    $show_projection = 0;
                }

                if (empty($group_name)) {
                    $error = "O nome do grupo não pode estar vazio.";
                } else {
                    $pdo->beginTransaction();

                    $status_enum = 'trial';
                    $trial_ends = null;
                    if ($initial_status === 'active') {
                        $status_enum = 'active';
                    } else {
                        $days = (int)str_replace('trial_', '', $initial_status);
                        $trial_ends = date('Y-m-d', strtotime("+$days days"));
                    }

                    $stmt_group = $pdo->prepare("
                        INSERT INTO `groups` (name, group_type, show_financial_projection, subscription_status, trial_ends_at)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt_group->execute([$group_name, $group_type, $show_projection, $status_enum, $trial_ends]);
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
            }
        }

        // Fetch Groups and Summarize
        $stmt = $pdo->query("SELECT * FROM `groups` ORDER BY id DESC");
        $all_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($all_groups as $g) {
            $s = $g['subscription_status'];
            if (isset($stats[$s])) $stats[$s]++;
        }

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
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
    <title>Super Admin SaaS - Te Controla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="bg-gray-100 p-8 font-sans">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">Painel Super Admin SaaS</h1>
        <p class="text-gray-600 mb-8">Gestão de Grupos, Assinaturas e Clientes</p>

        <?php if (!$is_logged_in): ?>
            <!-- Formulário de Login -->
            <div class="bg-white p-6 rounded-lg shadow-md max-w-sm mx-auto">
                <form method="POST" action="admin.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <label for="password" class="block font-medium mb-2 text-gray-700">Senha de Acesso Mestre:</label>
                    <input type="password" id="password" name="password" class="w-full border p-2 mb-4 rounded" required>
                    <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-2 rounded hover:bg-blue-700">Entrar no Painel</button>
                    <?php if ($error): ?>
                        <p class="text-red-500 mt-4 text-sm"><?= $error ?></p>
                    <?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <!-- Painel de Gestão -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-700">Visão Geral</h2>
                <a href="admin.php?logout=true" class="text-red-600 font-medium hover:underline"><i class="fas fa-sign-out-alt"></i> Sair do Painel</a>
            </div>

            <!-- Resumo -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-4 items-center rounded-lg shadow border-l-4 border-green-500">
                    <h3 class="text-gray-500 text-sm font-bold uppercase">Ativos</h3>
                    <p class="text-3xl font-bold text-gray-800"><?= $stats['active'] ?></p>
                </div>
                <div class="bg-white p-4 items-center rounded-lg shadow border-l-4 border-blue-500">
                    <h3 class="text-gray-500 text-sm font-bold uppercase">Em Trial</h3>
                    <p class="text-3xl font-bold text-gray-800"><?= $stats['trial'] ?></p>
                </div>
                <div class="bg-white p-4 items-center rounded-lg shadow border-l-4 border-yellow-500">
                    <h3 class="text-gray-500 text-sm font-bold uppercase">Inadimplentes</h3>
                    <p class="text-3xl font-bold text-gray-800"><?= $stats['overdue'] ?></p>
                </div>
                <div class="bg-white p-4 items-center rounded-lg shadow border-l-4 border-red-500">
                    <h3 class="text-gray-500 text-sm font-bold uppercase">Bloqueados</h3>
                    <p class="text-3xl font-bold text-gray-800"><?= $stats['blocked'] ?></p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?= $success ?></div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Criar Grupo -->
                <div class="bg-white p-6 rounded-lg shadow-md md:col-span-1 border-t-4 border-blue-600">
                    <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-plus-circle"></i> Novo Grupo</h2>
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        
                        <label class="block mb-1 text-gray-700 font-medium text-sm">Nome do Grupo:</label>
                        <input type="text" name="group_name" class="w-full border border-gray-300 p-2 mb-4 rounded" required placeholder="Ex: Acme Corp">
                        
                        <label class="block mb-1 text-gray-700 font-medium text-sm">Tipo de Grupo:</label>
                        <select id="group_type" name="group_type" onchange="toggleProjectionField()" class="w-full border border-gray-300 p-2 mb-4 rounded">
                            <option value="pessoal">Pessoal / Familiar</option>
                            <option value="empresa">Empresa</option>
                        </select>

                        <div id="projection_container" style="display: none;" class="mb-4 items-center">
                            <input type="checkbox" id="show_financial_projection" name="show_financial_projection" value="1" class="mr-2">
                            <label for="show_financial_projection" class="text-sm text-gray-700">Projeção Financeira Ativa</label>
                        </div>

                        <label class="block mb-1 text-gray-700 font-medium text-sm">Status Inicial (SaaS):</label>
                        <select name="initial_status" class="w-full border border-gray-300 p-2 mb-6 rounded">
                            <option value="trial_7">Trial 7 Dias</option>
                            <option value="trial_14">Trial 14 Dias</option>
                            <option value="trial_30">Trial 30 Dias</option>
                            <option value="active">Assinatura Ativa (Paga)</option>
                        </select>

                        <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-2 rounded shadow hover:bg-blue-700">Criar Grupo</button>

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
                </div>

                <!-- Lista de Grupos -->
                <div class="bg-white p-6 rounded-lg shadow-md md:col-span-2 border-t-4 border-gray-600 overflow-x-auto">
                    <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-users"></i> Grupos Registrados</h2>
                    <table class="w-full text-sm text-left text-gray-500 whitespace-nowrap">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-3 py-2">ID</th>
                                <th class="px-3 py-2">Nome</th>
                                <th class="px-3 py-2">Plano / Tipo</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Expira em</th>
                                <th class="px-3 py-2">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_groups as $g): ?>
                                <?php 
                                    $s = $g['subscription_status'];
                                    $badge = 'bg-gray-200 text-gray-800';
                                    if($s == 'active') $badge = 'bg-green-100 text-green-800';
                                    if($s == 'trial') $badge = 'bg-blue-100 text-blue-800';
                                    if($s == 'overdue') $badge = 'bg-yellow-100 text-yellow-800';
                                    if($s == 'blocked') $badge = 'bg-red-100 text-red-800';
                                    
                                    $expires = '-';
                                    if ($s === 'trial' && $g['trial_ends_at']) $expires = date('d/m/Y', strtotime($g['trial_ends_at']));
                                    if ($s === 'active' && $g['next_due_date']) $expires = date('d/m/Y', strtotime($g['next_due_date']));
                                ?>
                                <tr class="border-b">
                                    <td class="px-3 py-2 font-bold"><?= $g['id'] ?></td>
                                    <td class="px-3 py-2 font-medium text-gray-900"><?= htmlspecialchars($g['name']) ?></td>
                                    <td class="px-3 py-2">
                                        <?= strtoupper($g['subscription_plan'] ?? 'N/A') ?> 
                                        <span class="text-xs opacity-50">(<?= $g['group_type'] ?>)</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="px-2 py-1 rounded text-xs font-semibold <?= $badge ?>"><?= strtoupper($s) ?></span>
                                    </td>
                                    <td class="px-3 py-2"><?= $expires ?></td>
                                    <td class="px-3 py-2 flex items-center gap-2">
                                        <form method="POST" action="admin.php" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
                                            <?php if ($s !== 'blocked'): ?>
                                                <input type="hidden" name="action" value="block">
                                                <button type="submit" class="px-2 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600" title="Bloquear"><i class="fas fa-ban"></i></button>
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="unblock">
                                                <button type="submit" class="px-2 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600" title="Desbloquear"><i class="fas fa-check"></i></button>
                                            <?php endif; ?>
                                        </form>

                                        <form method="POST" action="admin.php" class="inline flex items-center">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
                                            <input type="hidden" name="action" value="set_trial">
                                            <select name="trial_days" class="text-xs border p-1 rounded ml-1 bg-gray-50" required>
                                                <option value="7">Trial 7</option>
                                                <option value="14">Trial 14</option>
                                                <option value="30">Trial 30</option>
                                            </select>
                                            <button type="submit" class="ml-1 px-2 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600" title="Renovar Trial"><i class="fas fa-sync-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($all_groups)): ?>
                                <tr>
                                    <td colspan="6" class="px-3 py-4 text-center text-gray-500">Nenhum grupo encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>