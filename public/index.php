<?php
session_start();

$group_sub_status = 'active';
if (isset($_SESSION['group_id'])) {
    require_once '../config/db.php';
    require_once '../app/helpers/access_guard.php';
    $group_sub_status = checkAccess($_SESSION['group_id'], $pdo);
}

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
// Passa a função do utilizador para o JavaScript
$user_role = $is_logged_in ? $_SESSION['user_role'] : 'membro'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Te Controla - Finanças</title>
    
    <!-- PWA Manifest e Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4a90e2">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Te Controla">
    <link rel="apple-touch-icon" href="icons/icon-192x192.png">
    <!-- Logótipo na aba do navegador -->
    <link rel="icon" type="image/png" href="./icon/icon.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!--<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>!-->
    
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="text-gray-800">

    <!-- Passa a função do utilizador para o Alpine.js -->
    <input type="hidden" id="userRole" value="<?php echo htmlspecialchars($user_role); ?>">

    <!-- Auth Container -->
    <div x-data="{ showLogin: true, 
                   regPassword: '', 
                   passwordStrength() {
                       let s = 0; 
                       if(this.regPassword.length > 5) s+=1; 
                       if(/[A-Z]/.test(this.regPassword)) s+=1; 
                       if(/[0-9]/.test(this.regPassword)) s+=1; 
                       return s; 
                   } 
                 }" 
         x-show="!<?php echo $is_logged_in ? 'true' : 'false'; ?>" class="min-h-screen flex items-center justify-center bg-gray-100 p-4" x-cloak>
        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
            
            <!-- Logótipo Centralizado -->
            <div class="flex justify-center mb-6">
                <img src="./imagens/logo.png" alt="Te Controla Logotipo" class="h-28">
            </div>
            
            <!-- Tabs Visuais -->
            <div class="flex border-b mb-6 border-gray-200">
                <button @click="showLogin = true" :class="showLogin ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="w-1/2 py-2 text-center border-b-2 font-semibold transition-colors duration-200 focus:outline-none">Login</button>
                <button @click="showLogin = false" :class="!showLogin ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="w-1/2 py-2 text-center border-b-2 font-semibold transition-colors duration-200 focus:outline-none">Cadastro</button>
            </div>

            <!-- Formulário de Login -->
            <div id="login-form-container" x-show="showLogin" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <form id="login-form">
                    <div class="mb-4 text-left">
                        <label for="login-email" class="block text-gray-600 mb-1 text-sm font-medium">Email</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 top-3 text-gray-400"></i>
                            <input type="email" id="login-email" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50" required placeholder="seu@email.com">
                        </div>
                    </div>
                    <div class="mb-6 text-left">
                        <label for="login-password" class="block text-gray-600 mb-1 text-sm font-medium">Senha</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-3 text-gray-400"></i>
                            <input type="password" id="login-password" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50" required placeholder="••••••••">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-bold shadow-md">Entrar na Conta</button>
                </form>
            </div>

            <!-- Formulário de Cadastro -->
            <div id="register-form-container" x-show="!showLogin" style="display: none;" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <form id="register-form">
                    <div class="mb-4 text-left">
                        <label for="register-name" class="block text-gray-600 mb-1 text-sm font-medium">Nome Completo</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3 top-3 text-gray-400"></i>
                            <input type="text" id="register-name" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50" required placeholder="João da Silva">
                        </div>
                    </div>
                    <div class="mb-4 text-left">
                        <label for="register-email" class="block text-gray-600 mb-1 text-sm font-medium">Email</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 top-3 text-gray-400"></i>
                            <input type="email" id="register-email" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50" required placeholder="seu@email.com">
                        </div>
                    </div>
                    <div class="mb-4 text-left">
                        <label for="register-password" class="block text-gray-600 mb-1 text-sm font-medium">Senha</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-3 text-gray-400"></i>
                            <input type="password" x-model="regPassword" id="register-password" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50" required placeholder="Mínimo 6 caracteres">
                        </div>
                        <!-- Indicador de Força da Senha -->
                        <div class="mt-2" x-show="regPassword.length > 0">
                            <div class="flex h-1.5 w-full bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full transition-all duration-300" 
                                     :class="{'w-1/3 bg-red-500': passwordStrength() === 1, 
                                              'w-2/3 bg-yellow-500': passwordStrength() === 2, 
                                              'w-full bg-green-500': passwordStrength() >= 3,
                                              'w-0': passwordStrength() === 0}"></div>
                            </div>
                            <p class="text-xs mt-1" :class="{'text-red-500': passwordStrength() === 1, 'text-yellow-600': passwordStrength() === 2, 'text-green-600': passwordStrength() >= 3}" x-text="['Muito fraca', 'Fraca', 'Média', 'Forte'][passwordStrength()]"></p>
                        </div>
                    </div>
                    <div class="mb-6 text-left">
                        <label for="register-token" class="block text-gray-600 mb-1 text-sm font-medium">Chave de Convite (Token)</label>
                        <div class="relative">
                            <i class="fas fa-key absolute left-3 top-3 text-gray-400"></i>
                            <input type="text" id="register-token" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50" placeholder="Token fornecido pelo Admin" required>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-200 font-bold shadow-md">Criar Minha Conta</button>
                </form>
            </div>
        </div>
    </div>

    <!-- App Container -->
    <div id="app-container" x-data="app()" x-init="init()" class="<?php echo $is_logged_in ? 'block' : 'hidden'; ?>" x-cloak>
        <header class="bg-white shadow-md sticky top-0 z-20">
            <nav class="container mx-auto px-4 sm:px-6 py-4 flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <img src="./imagens/logo-horizontal.png" alt="Logo" class="h-25 w-25">
                    
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-gray-600 mr-2 text-sm hidden sm:block">Olá, <?php echo htmlspecialchars($user_name); ?></span>
                    
                    <!-- Botão Projeção Financeira -->
                    <a x-cloak x-show="data.group_settings && data.group_settings.group_type === 'empresa' && Number(data.group_settings.show_financial_projection) === 1" href="financeiro.php" class="bg-emerald-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-emerald-700 transition duration-200 text-sm font-semibold flex items-center space-x-2">
                        <i class="fas fa-chart-line"></i>
                        <span class="hidden sm:inline">Projeção Financeira</span>
                    </a>

                    <!-- Botão de Gerir Membros (Apenas para Admins) -->
                    <button x-show="userRole === 'admin'" @click="openManageMembersModal()" class="bg-indigo-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-200 text-sm font-semibold flex items-center space-x-2">
                        <i class="fas fa-users"></i>
                        <span class="hidden sm:inline">Gerir Membros</span>
                    </button>
                    
                    <!-- Painel de Notificações -->
                    <div class="relative">
                        <button @click="notificationPanelOpen = !notificationPanelOpen" class="text-gray-600 hover:text-blue-600 relative">
                            <i class="fas fa-bell text-xl"></i>
                            <span x-show="notifications.length > 0" class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center" x-text="notifications.length"></span>
                        </button>
                        <!-- Dropdown do Painel -->
                        <div x-show="notificationPanelOpen" @click.away="notificationPanelOpen = false" x-transition class="absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-lg shadow-xl overflow-hidden z-30 border">
                            <div class="p-3 border-b">
                                <h4 class="font-semibold text-gray-700">Notificações</h4>
                            </div>
                            <div x-show="notifications.length === 0" class="p-4 text-center text-gray-500">
                                <i class="fas fa-check-circle text-3xl text-green-500 mb-2"></i>
                                <p>Nenhum vencimento próximo.</p>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                <template x-for="notification in notifications" :key="notification.id + notification.type">
                                    <div class="flex items-center p-3 hover:bg-gray-50 border-b space-x-3">
                                        <div class="text-blue-500">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-800" x-text="notification.description"></p>
                                            <p class="text-xs text-gray-500">
                                                Vence em: <span x-text="notification.due_date_formatted"></span>
                                                <span x-text="notification.due_day_message" :class="notification.due_day_message === '(Vence Hoje)' ? 'text-red-600 font-semibold' : 'text-orange-600'"></span>
                                            </p>
                                        </div>
                                        <a :href="createGoogleCalendarLink(notification)" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-blue-600" title="Adicionar ao Google Agenda">
                                            <i class="fas fa-calendar-plus"></i>
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <button @click="logout()" class="bg-red-500 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-red-600 transition duration-200 text-sm font-semibold">Sair</button>
                </div>
            </nav>
        </header>

        <main class="container mx-auto p-4 sm:p-6">
            <?php if ($group_sub_status === 'overdue'): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i> Atenção: Pagamento Pendente!</p>
                <p class="text-sm mt-1">Identificamos que o pagamento da sua assinatura está em atraso. O acesso será bloqueado em breve se não for regularizado.</p>
            </div>
            <?php endif; ?>
            
            <!-- Header -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
               <!-- Seletor de Mês/Ano -->
                <div class="bg-white p-4 rounded-xl shadow-lg flex items-center justify-center space-x-2">
                     <select x-model="month" @change="fetchData()" class="p-2 border rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <template x-for="(monthName, index) in months">
                            <option :value="index + 1" x-text="monthName" :selected="(index + 1) == month"></option>
                        </template>
                     </select>
                    <!-- ATUALIZAÇÃO AQUI: Chama 'fetchData()' -->
                     <select x-model="year" @change="fetchData()" class="p-2 border rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <template x-for="y in yearOptions">
                            <option :value="y" x-text="y" :selected="y == year"></option>
                        </template>
                     </select>
                </div>
                <!-- Resumos -->
                <div class="bg-white p-4 rounded-xl shadow-lg text-center">
                    <h3 class="text-sm text-gray-500">Entradas do Mês</h3>
                    <p class="text-xl sm:text-2xl font-bold text-green-600" x-text="formatCurrency(totals.income)"></p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-lg text-center">
                     <h3 class="text-sm text-gray-500">Saídas do Mês</h3>
                    <p class="text-xl sm:text-2xl font-bold text-red-600" x-text="formatCurrency(totals.expenses)"></p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-lg text-center">
                     <h3 class="text-sm text-gray-500">Saldo do Mês</h3>
                    <p class="text-xl sm:text-2xl font-bold" :class="totals.income - totals.expenses >= 0 ? 'text-blue-600' : 'text-red-600'" x-text="formatCurrency(totals.income - totals.expenses)"></p>
                </div>
            </div>
            
            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
                
                <!-- Coluna Esquerda (Gráfico) -->
                <div class="lg:col-span-1 bg-white p-4 sm:p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-700 mb-6">Despesas por Categoria</h2>
                    
                    <!-- NOVO GRÁFICO EM HTML (Substitui o <canvas>) -->
                    <div class="space-y-4">
                        
                        <!-- Mensagem de "Sem Dados" -->
                        <template x-if="!data.chart_data || data.chart_data.data.length === 0">
                            <div class="text-center text-gray-500 py-10">
                                <i class="fas fa-chart-pie text-3xl mb-2"></i>
                                <p>Sem dados de despesas para este mês.</p>
                            </div>
                        </template>
                        
                        <!-- Template para as barras do gráfico -->
                        <template x-for="(label, index) in (data.chart_data ? data.chart_data.labels : [])" :key="index">
                            <div class="space-y-1">
                                <!-- Rótulo (Ex: Água) e Valor (Ex: R$ 25,00) -->
                                <div class="flex justify-between text-sm font-medium text-gray-600">
                                    <span x-text="label"></span>
                                    <span x-text="formatCurrency(data.chart_data.data[index])"></span>
                                </div>
                                <!-- Barra de Progresso -->
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" 
                                         :style="{ width: (data.chart_data.data[index] / chartMaxAmount * 100) + '%' }">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>    
                    <!-- Fim do Gráfico em HTML -->

                <!-- Coluna Direita (Tabelas) -->
                <div class="lg:col-span-2 space-y-6 sm:space-y-8">
                    <!-- Entradas -->
                    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
                        <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-700">Entradas</h2>
                            <button @click="openModal('income')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-semibold">+ Entrada</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm table-responsive-sm">
                                <thead><tr><th class="py-2 px-3 border-b-2 text-left">Data</th><th class="py-2 px-3 border-b-2 text-left">Descrição</th><th class="py-2 px-3 border-b-2 text-right">Valor</th><th class="w-20 text-right"></th></tr></thead>
                                <tbody>
                                    <template x-if="data.income.length === 0"><tr><td colspan="4" class="text-center py-4 text-gray-500">Nenhuma entrada cadastrada.</td></tr></template>
                                    <template x-for="item in data.income" :key="item.id">
                                        <tr>
                                            <td class="py-2 px-3 border-b" x-text="formatDate(item.income_date)"></td>
                                            <td class="py-2 px-3 border-b" x-text="item.description"></td>
                                            <td class="py-2 px-3 border-b text-right text-green-600" x-text="formatCurrency(item.amount)"></td>
                                            <td class="py-2 px-3 border-b text-right space-x-2">
                                                <button @click="openEditModal('income', item)" class="text-gray-400 hover:text-blue-500" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                                                <button @click="deleteItem('income', item.id)" class="text-gray-400 hover:text-red-500" title="Excluir"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Despesas Fixas -->
                    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
                        <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-700">Despesas Fixas</h2>
                            <button @click="openModal('fixed_expense')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-semibold">+ Despesa</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm table-responsive-sm">
                                <thead><tr><th class="py-2 px-3 border-b-2 text-left">Venc.</th><th class="py-2 px-3 border-b-2 text-left">Descrição</th><th class="py-2 px-3 border-b-2 text-left">Categoria</th><th class="py-2 px-3 border-b-2 text-right">Valor</th><th class="w-20 text-right"></th></tr></thead>
                                <tbody>
                                    <template x-if="data.fixed_expenses.length === 0"><tr><td colspan="5" class="text-center py-4 text-gray-500">Nenhuma despesa fixa cadastrada.</td></tr></template>
                                    <template x-for="item in data.fixed_expenses" :key="item.id">
                                        <tr>
                                            <td class="py-2 px-3 border-b text-center" x-text="item.due_day"></td>
                                            <td class="py-2 px-3 border-b" x-text="item.description"></td>
                                            <td class="py-2 px-3 border-b" x-text="item.category_name || '-'"></td>
                                            <td class="py-2 px-3 border-b text-right text-red-600" x-text="formatCurrency(item.amount)"></td>
                                            <td class="py-2 px-3 border-b text-right space-x-2">
                                                <button @click="openEditModal('fixed_expense', item)" class="text-gray-400 hover:text-blue-500" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                                                <button @click="deleteItem('fixed_expense', item.id)" class="text-gray-400 hover:text-red-500" title="Excluir"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Despesas Variáveis -->
                    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
                        <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-700">Desp. Variáveis</h2>
                            <button @click="openModal('variable_expense')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-semibold">+ Despesa</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm table-responsive-sm">
                                <thead><tr><th class="py-2 px-3 border-b-2 text-left">Data</th><th class="py-2 px-3 border-b-2 text-left">Descrição</th><th class="py-2 px-3 border-b-2 text-left">Cat.</th><th class="py-2 px-3 border-b-2 text-center">Parc.</th><th class="py-2 px-3 border-b-2 text-right">Valor</th><th class="w-24 text-right"></th></tr></thead>
                                <tbody>
                                    <template x-if="data.variable_expenses.length === 0"><tr><td colspan="6" class="text-center py-4 text-gray-500">Nenhuma despesa variável para este mês.</td></tr></template>
                                    <template x-for="item in data.variable_expenses" :key="item.id">
                                        <tr>
                                            <td class="py-2 px-3 border-b" x-text="formatDate(item.purchase_date)"></td>
                                            <td class="py-2 px-3 border-b" x-text="item.description"></td>
                                            <td class="py-2 px-3 border-b" x-text="item.category_name || '-'"></td>
                                            <td class="py-2 px-3 border-b text-center" x-text="`${item.current_installment}/${item.installments}`"></td>
                                            <td class="py-2 px-3 border-b text-right text-red-600" x-text="formatCurrency(item.amount / item.installments)"></td>
                                            <td class="py-2 px-3 border-b text-right space-x-2">
                                                <button @click="openDetailsModal(item)" class="text-gray-400 hover:text-green-500" title="Detalhes do Financiamento"><i class="fas fa-info-circle"></i></button>
                                                <button @click="openEditModal('variable_expense', item)" class="text-gray-400 hover:text-blue-500" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                                                <button @click="deleteItem('variable_expense', item.id)" class="text-gray-400 hover:text-red-500" title="Excluir"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Despesas Cartões (Ocupa a largura total abaixo) -->
            <div class="mt-6 sm:mt-8 bg-white p-4 sm:p-6 rounded-xl shadow-lg">
                <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-700">Despesas Cartões</h2>
                    <div class="flex space-x-2">
                         <button @click="openModal('category')" class="bg-purple-600 text-white px-3 py-2 rounded-lg hover:bg-purple-700 transition text-sm font-semibold">Gerir Categorias</button>
                         <button @click="openModal('card')" class="bg-gray-200 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-300 transition text-sm font-semibold">Gerir Cartões</button>
                         <button @click="openModal('purchase')" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-semibold">+ Compra</button>
                    </div>
                </div>
                <div x-show="data.cards.length > 0" class="mb-4">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-4 overflow-x-auto">
                            <template x-for="card in data.cards" :key="card.id">
                                <a href="#" @click.prevent="activeCardId = card.id" :class="activeCardId == card.id ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" x-text="card.name"></a>
                            </template>
                        </nav>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm table-responsive-sm">
                        <thead>
                            <tr>
                                <th class="py-2 px-3 border-b-2 text-left">Data</th>
                                <th class="py-2 px-3 border-b-2 text-left">Descrição</th>
                                <th class="py-2 px-3 border-b-2 text-left">Cat.</th>
                                <th class="py-2 px-3 border-b-2 text-center">Parc.</th>
                                <th class="py-2 px-3 border-b-2 text-right">Valor</th>
                                <th class="w-24 text-right"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="data.cards.length === 0"><tr><td colspan="6" class="text-center py-4 text-gray-500">Nenhum cartão cadastrado.</td></tr></template>
                            <template x-if="data.cards.length > 0 && (data.purchases[activeCardId] || []).length === 0"><tr><td colspan="6" class="text-center py-4 text-gray-500">Nenhuma compra para este cartão neste mês.</td></tr></template>
                            <template x-for="purchase in (data.purchases[activeCardId] || [])" :key="purchase.id">
                                <tr>
                                    <td class="py-2 px-3 border-b" x-text="formatDate(purchase.purchase_date)"></td>
                                    <td class="py-2 px-3 border-b" x-text="purchase.description"></td>
                                    <td class="py-2 px-3 border-b" x-text="purchase.category_name || '-'"></td>
                                    <td class="py-2 px-3 border-b text-center" x-text="`${purchase.current_installment}/${purchase.installments}`"></td>
                                    <td class="py-2 px-3 border-b text-right text-red-600" x-text="formatCurrency(purchase.amount / purchase.installments)"></td>
                                    <td class="py-2 px-3 border-b text-right space-x-2">
                                        <button @click="openDetailsModal(purchase)" class="text-gray-400 hover:text-green-500" title="Detalhes do Financiamento"><i class="fas fa-info-circle"></i></button>
                                        <button @click="openEditModal('purchase', purchase)" class="text-gray-400 hover:text-blue-500" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                                        <button @click="deleteItem('purchase', purchase.id)" class="text-gray-400 hover:text-red-500" title="Excluir"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                         <tfoot>
                            <tr x-show="data.cards.length > 0">
                                <td colspan="4" class="pt-4 font-bold text-right">Total Fatura:</td>
                                <td class="pt-4 text-right font-bold text-red-700" x-text="formatCurrency(cardTotal)"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </main>
        
        <!-- Modal Genérico (Adicionar/Editar) -->
        <div x-show="modal.open" @keydown.escape.window="closeModal()" class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full flex items-center justify-center p-4 z-40" x-transition.opacity>
            <div @click.away="closeModal()" class="relative mx-auto p-6 sm:p-8 border w-full max-w-lg shadow-lg rounded-xl bg-white" x-transition.scale>
                <h3 class="text-xl sm:text-2xl font-bold mb-6 text-gray-800" x-text="modal.title"></h3>
                
                <!-- Formulário Principal -->
                <form @submit.prevent="submitForm()" x-show="modal.type !== 'category' && modal.type !== 'card'">
                    <div class="space-y-4">
                        <!-- Entrada -->
                        <template x-if="modal.type === 'income'">
                            <div class="space-y-4">
                                <div><label class="block mb-1 text-sm">Tipo de Entrada</label><select x-model="form.income_type" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required><option value="SALARIO">Salário</option><option value="EXTRAS">Extras</option><option value="OUTROS">Outros</option></select></div>
                                <div><label class="block mb-1 text-sm">Descrição</label><input type="text" x-model="form.description" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                                <div><label class="block mb-1 text-sm">Responsável (Opcional)</label><input type="text" x-model="form.responsible" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                                <div><label class="block mb-1 text-sm">Valor</label><input type="number" step="0.01" x-model="form.amount" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                                <div><label class="block mb-1 text-sm">Data</label><input type="date" x-model="form.income_date" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                            </div>
                        </template>
                        <!-- Despesa Fixa -->
                        <template x-if="modal.type === 'fixed_expense'">
                            <div class="space-y-4">
                                <div><label class="block mb-1 text-sm">Descrição</label><input type="text" x-model="form.description" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                                <div><label class="block mb-1 text-sm">Categoria</label>
                                    <select x-model="form.category_id" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><option value="">Nenhuma</option><template x-for="cat in data.categories"><option :value="cat.id" x-text="cat.name"></option></template></select>
                                </div>
                                <div><label class="block mb-1 text-sm">Responsável (Opcional)</label><input type="text" x-model="form.responsible" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                                <div><label class="block mb-1 text-sm">Valor</label><input type="number" step="0.01" x-model="form.amount" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                                <div><label class="block mb-1 text-sm">Dia do Vencimento</label><input type="number" min="1" max="31" x-model="form.due_day" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                            </div>
                        </template>
                        <!-- Despesa Variável -->
                        <template x-if="modal.type === 'variable_expense'">
                            <div class="space-y-4">
                                <div><label class="block mb-1 text-sm">Descrição</label><input type="text" x-model="form.description" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                                <div><label class="block mb-1 text-sm">Categoria</label>
                                    <select x-model="form.category_id" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><option value="">Nenhuma</option><template x-for="cat in data.categories"><option :value="cat.id" x-text="cat.name"></option></template></select>
                                </div>
                                <div><label class="block mb-1 text-sm">Responsável (Opcional)</label><input type="text" x-model="form.responsible" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                                <div><label class="block mb-1 text-sm">Data da Compra</label><input type="date" x-model="form.purchase_date" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                   <div><label class="block mb-1 text-sm">Valor Total</label><input type="number" step="0.01" x-model="form.amount" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                                   <div><label class="block mb-1 text-sm">Parc. Inicial</label><input type="number" min="1" x-model="form.initial_installment" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                                   <div><label class="block mb-1 text-sm">Parc. Total</label><input type="number" min="1" x-model="form.installments" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                               </div>
                            </div>
                        </template>
                        <!-- Compra no Cartão -->
                        <template x-if="modal.type === 'purchase'">
                            <div class="space-y-4">
                               <div><label class="block mb-1 text-sm">Cartão</label><select x-model="form.card_id" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required><option value="">Selecione...</option><template x-for="card in data.cards"><option :value="card.id" x-text="card.name"></option></template></select></div>
                               <div><label class="block mb-1 text-sm">Descrição</label><input type="text" x-model="form.description" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                               <div><label class="block mb-1 text-sm">Categoria</label>
                                    <select x-model="form.category_id" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><option value="">Nenhuma</option><template x-for="cat in data.categories"><option :value="cat.id" x-text="cat.name"></option></template></select>
                                </div>
                               <div><label class="block mb-1 text-sm">Comprado por (Opcional)</label><input type="text" x-model="form.purchased_by" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                               <div><label class="block mb-1 text-sm">Data da Compra</label><input type="date" x-model="form.purchase_date" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                               <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                   <div><label class="block mb-1 text-sm">Valor Total</label><input type="number" step="0.01" x-model="form.amount" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                                   <div><label class="block mb-1 text-sm">Parc. Inicial</label><input type="number" min="1" x-model="form.initial_installment" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                                   <div><label class="block mb-1 text-sm">Parc. Total</label><input type="number" min="1" x-model="form.installments" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                               </div>
                               <div><label class="block mb-1 text-sm">Descrição Detalhada (Opcional)</Slabel><textarea x-model="form.notes" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2"></textarea></div>
                            </div>
                        </template>
                    </div>
                    <div class="flex justify-end pt-6 sm:pt-8 space-x-4">
                        <button type="button" @click="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Salvar</button>
                    </div>
                </form>

                <!-- Formulário de Gerir Categorias -->
                <div x-show="modal.type === 'category'" class="space-y-4">
                    <form @submit.prevent="submitCategoryForm()">
                        <label class="block mb-1 text-sm" x-text="categoryForm.id ? 'Editar Categoria' : 'Adicionar Nova Categoria'"></label>
                        <div class="flex space-x-2">
                            <input type="text" placeholder="Nome da categoria" x-model="categoryForm.name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium" x-text="categoryForm.id ? 'Salvar' : 'Criar'"></button>
                            <button type="button" x-show="categoryForm.id" @click="categoryForm = { id: null, name: '' }" class="px-3 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300" title="Cancelar edição">&times;</button>
                        </div>
                    </form>
                    <hr class="my-4">
                    <h4 class="font-semibold text-gray-700">Categorias Existentes</h4>
                    <div class="max-h-48 overflow-y-auto space-y-2">
                        <template x-if="data.categories.length === 0">
                            <p class="text-gray-500 text-sm">Nenhuma categoria criada.</p>
                        </template>
                        <template x-for="cat in data.categories" :key="cat.id">
                            <div class="flex justify-between items-center p-2 rounded-lg bg-gray-50">
                                <span x-text="cat.name"></span>
                                <div class="space-x-2">
                                    <button @click="editCategory(cat)" class="text-gray-400 hover:text-blue-500" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                                    <button @click="deleteItem('category', cat.id)" class="text-gray-400 hover:text-red-500" title="Excluir"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="flex justify-end pt-4">
                        <button type="button" @click="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium">Fechar</button>
                    </div>
                </div>
                
                <!-- Formulário de Gerir Cartões -->
                <div x-show="modal.type === 'card'" class="space-y-4">
                    <form @submit.prevent="submitForm()">
                        <div class="space-y-4">
                            <div><label class="block mb-1 text-sm">Nome do Cartão</label><input type="text" x-model="form.name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block mb-1 text-sm">Dia do Vencimento</label><input type="number" min="1" max="31" x-model="form.due_day" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                                <div><label class="block mb-1 text-sm">Dia do Fechamento</label><input type="number" min="1" max="31" x-model="form.closing_day" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                            </div>
                        </div>
                        <div class="flex justify-end pt-6 space-x-4">
                            <button type="button" @click="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium">Cancelar</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Adicionar Cartão</button>
                        </div>
                    </form>
                    <hr class="my-4">
                    <h4 class="font-semibold text-gray-700">Cartões Existentes</h4>
                    <div class="max-h-48 overflow-y-auto space-y-2">
                         <template x-if="data.cards.length === 0">
                            <p class="text-gray-500 text-sm">Nenhum cartão criado.</p>
                        </template>
                        <template x-for="card in data.cards" :key="card.id">
                            <div class="flex justify-between items-center p-2 rounded-lg bg-gray-50">
                                <div>
                                    <span x-text="card.name" class="font-medium"></span>
                                    <span class="text-xs text-gray-500 block" x-text="`Fecha dia ${card.closing_day}, Vence dia ${card.due_day}`"></span>
                                D/div>
                                <div class="space-x-2">
                                    <button @click="openEditModal('card', card)" class="text-gray-400 hover:text-blue-500" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                                    <button @click="deleteItem('card', card.id)" class="text-gray-400 hover:text-red-500" title="Excluir"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal de Detalhes do Financiamento -->
        <div x-show="detailsModal.show" @keydown.escape.window="closeDetailsModal()" class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full flex items-center justify-center p-4 z-40" x-transition.opacity>
            <div @click.away="closeDetailsModal()" class="relative mx-auto p-6 sm:p-8 border w-full max-w-lg shadow-lg rounded-xl bg-white" x-transition.scale>
                <h3 class="text-xl sm:text-2xl font-bold mb-6 text-gray-800" x-text="detailsModal.title"></h3>
                <div class="space-y-4">
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <label class="block text-sm font-medium text-blue-800">Valor Total:</label>
                        <p class="text-2xl font-bold text-blue-900" x-text="formatCurrency(detailsModal.summary.valorTotal)"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-600">Total de Parcelas</label>
                            <p class="text-xl font-bold text-gray-900" x-text="detailsModal.summary.totalParcelas"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-600">Valor da Parcela</label>
                            <p class="text-xl font-bold text-gray-900" x-text="formatCurrency(detailsModal.summary.valorParcela)"></p>
                        </div>
                    </div>
                     <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="bg-green-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-green-700">Parcelas Pagas</label>
                            <p class="text-xl font-bold text-green-800" x-text="detailsModal.summary.parcelasPagas"></p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-red-700">Parcelas Faltantes</label>
                            <p class="text-xl font-bold text-red-800" x-text="detailsModal.summary.parcelasFaltantes"></p>
                        </div>
                    </div>
                    <div class="bg-green-100 p-4 rounded-lg text-center">
                        <label class="block text-sm font-medium text-green-800">Valor Pago:</label>
                        <p class="text-2xl font-bold text-green-900" x-text="formatCurrency(detailsModal.summary.valorPago)"></p>
                    </div>
                    <div class="bg-red-100 p-4 rounded-lg text-center">
                        <label class="block text-sm font-medium text-red-800">Valor Restante:</label>
                        <p class="text-2xl font-bold text-red-900" x-text="formatCurrency(detailsModal.summary.valorRestante)"></p>
                    </div>
                </div>
                <div class="flex justify-end pt-6 sm:pt-8">
                    <button type="button" @click="closeDetailsModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium">Fechar</button>
                </div>
            </div>
        </div>

        <!-- Modal de Gestão de Membros -->
        <div x-show="manageModal.open" @keydown.escape.window="closeManageMembersModal()" class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full flex items-center justify-center p-4 z-40" x-transition.opacity>
            <div @click.away="closeManageMembersModal()" class="relative mx-auto p-6 sm:p-8 border w-full max-w-lg shadow-lg rounded-xl bg-white" x-transition.scale>
                <h3 class="text-xl sm:text-2xl font-bold mb-6 text-gray-800">Gerir Membros do Grupo</h3>
                
                <!-- Secção de Membros -->
                <h4 class="font-semibold text-gray-700 mb-2">Membros Atuais</h4>
                <div class="max-h-48 overflow-y-auto space-y-2 mb-6">
                    <template x-if="manageModal.members.length === 0">
                        <p class="text-gray-500 text-sm">Apenas você está neste grupo.</p>
                    </template>
                    <template x-for="member in manageModal.members" :key="member.id">
                        <div class="flex justify-between items-center p-2 rounded-lg bg-gray-50">
                            <div>
                                <span class="font-medium" x-text="member.name"></span>
                                <span class="text-xs text-gray-500 block" x-text="member.email"></span>
                            </div>
                            <!-- O admin não se pode remover a si mesmo -->
                            <button x-show="member.role !== 'admin'" @click="removeMember(member.id, member.name)" class="text-gray-400 hover:text-red-500" title="Remover Membro">
                                <i class="fas fa-trash"></i>
                            </button>
                            <span x-show="member.role === 'admin'" class="text-xs text-blue-600 font-medium bg-blue-100 px-2 py-0.5 rounded-full">Admin</span>
                        </div>
                    </template>
                </div>

                <!-- Secção de Convites -->
                <h4 class="font-semibold text-gray-700 mb-2">Convites Pendentes</h4>
                <div class="space-y-2 mb-4">
                    <button @click="createInviteToken()" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition font-semibold text-sm">
                        <i class="fas fa-plus-circle"></i> Gerar Novo Link de Convite
                    </button>
                    <div class="max-h-32 overflow-y-auto space-y-2 pt-2">
                        <template x-if="manageModal.tokens.length === 0">
                            <p class="text-gray-500 text-sm text-center py-2">Nenhum convite pendente.</p>
                        </template>
                        <template x-for="(token, index) in manageModal.tokens" :key="index">
                            <div class="flex items-center space-x-2">
                                <input type="text" :value="token.token" readonly class="w-full p-2 border rounded-lg bg-gray-50 text-sm">
                                <button @click="copyToClipboard(token.token)" class="text-gray-500 hover:text-blue-600" title="Copiar Token">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
                
                <div class="flex justify-end pt-4">
                    <button type="button" @click="closeManageMembersModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium">Fechar</button>
                </div>
            </div>
        </div>

        <!-- Notificação Toast -->
        <div x-show="toast.show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform translate-y-2" class="fixed bottom-5 right-5 z-50">
            <div class="rounded-lg shadow-lg p-4" :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'">
                <div class="flex items-center">
                    <i class="fas text-white" :class="toast.type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                    <p class="text-white ml-3 font-medium" x-text="toast.message"></p>
                </div>
            </div>
        </div>
        
        <!-- Modal de Confirmação -->
        <div x-show="confirmModal.show" class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full flex items-center justify-center p-4 z-50" x-transition.opacity>
            <div @click.away="closeConfirmModal()" class="relative mx-auto p-6 sm:p-8 border w-full max-w-md shadow-lg rounded-xl bg-white" x-transition.scale>
                <h3 class="text-xl font-bold mb-4 text-gray-800" x-text="confirmModal.title"></h3>
                <p class="text-gray-600 mb-6" x-text="confirmModal.message"></p>
                <div class="flex justify-end space-x-4">
                    <button type="button" @click="closeConfirmModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium">Cancelar</button>
                    <button type="button" @click="confirmAction()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">Confirmar</button>
                </div>
            </div>
        </div>

    </div>

    <script src="js/script.js"></script>
</body>
</html>