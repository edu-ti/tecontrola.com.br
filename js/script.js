// Lógica de autenticação
document.addEventListener('DOMContentLoaded', () => {
    const loginFormContainer = document.getElementById('login-form-container');
    const registerFormContainer = document.getElementById('register-form-container');
    const showRegisterLink = document.getElementById('show-register');
    const showLoginLink = document.getElementById('show-login');

    // Funções para alternar entre os formulários de login e registo
    // Estas funções são tratadas pelo Alpine.js, mas mantemos os IDs para o submit
    // Se o Alpine falhar, os links @click.prevent="showLogin = false" assumem
    
    const registerForm = document.getElementById('register-form');
    if(registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const res = await fetch('api/router.php?action=register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: registerForm.querySelector('#register-name').value,
                    email: registerForm.querySelector('#register-email').value,
                    password: registerForm.querySelector('#register-password').value,
                    token: registerForm.querySelector('#register-token').value
                })
            });
            const result = await res.json();
            if (result.status === 'success') {
                alert('Conta criada com sucesso! Por favor, faça o login.');
                window.location.reload();
            } else {
                alert(result.message || 'Erro ao criar conta.');
            }
        });
    }

    const loginForm = document.getElementById('login-form');
    if(loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const res = await fetch('api/router.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email: loginForm.querySelector('#login-email').value,
                    password: loginForm.querySelector('#login-password').value
                })
            });
            const result = await res.json();
            if (result.status === 'success') {
                window.location.reload();
            } else {
                alert(result.message || 'Erro ao fazer login.');
            }
        });
    }
});


// Lógica da aplicação principal com Alpine.js
function app() {
    return {
        month: new Date().getMonth() + 1,
        year: new Date().getFullYear(),
        months: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
        yearOptions: [],
        data: { income: [], fixed_expenses: [], variable_expenses: [], cards: [], purchases: {}, categories: [] },
        activeCardId: null,
        modal: { open: false, type: '', title: ''},
        form: {},
        categoryForm: { id: null, name: '' },
        toast: { show: false, message: '', type: 'success' },
        confirmModal: { show: false, title: 'Confirmar Exclusão', message: '', onConfirm: () => {} },
        chartMaxAmount: 1, // Adicionado: Para calcular a % da barra (evita divisão por zero)
        detailsModal: { show: false, title: '', summary: {} },
        notifications: [],
        notificationPanelOpen: false,
        userRole: 'membro', // Padrão
        
        // Novo estado para o modal de gestão
        manageModal: { open: false, members: [], tokens: [] },

        init() {
            // Lê a função do utilizador a partir do elemento hidden
            const roleEl = document.getElementById('userRole');
            if (roleEl) {
                this.userRole = roleEl.value;
            }

            const currentYear = new Date().getFullYear();
            this.year = currentYear;
            this.month = new Date().getMonth() + 1;
            for (let i = 0; i < 5; i++) { this.yearOptions.push(currentYear - 2 + i); }
            
            if (document.getElementById('app-container').classList.contains('block')) {
                this.fetchData();
                this.fetchNotifications();
            }
        },
        async fetchData() {
            try {
                const res = await fetch(`api/router.php?action=data&month=${this.month}&year=${this.year}`);
                if (!res.ok) {
                    if (res.status === 401) window.location.reload();
                    const errorData = await res.json().catch(() => null);
                    throw new Error(errorData?.message || `HTTP error! status: ${res.status}`);
                }
                const result = await res.json();
                
                if (result.status === 'success') {
                    this.data = result.data;
                    
                    if (this.data.cards.length > 0 && (!this.activeCardId || !this.data.cards.find(c => c.id == this.activeCardId))) {
                        this.activeCardId = this.data.cards[0].id;
                    }
                    
                   // 5. Prepara os dados para o novo gráfico HTML
                    const chartData = this.data.chart_data;
                    if (chartData && chartData.data.length > 0) {
                        // Calcula o valor máximo para a barra de 100%
                        this.chartMaxAmount = Math.max(...chartData.data);
                    } else {
                        // Reseta para 1 para evitar divisão por zero
                        this.chartMaxAmount = 1;
                    }
                    
                } else { this.showToast(result.message || 'Erro ao carregar dados.', 'error'); }
            } catch (e) {
                console.error("Fetch error: ", e);
                this.showToast(e.message || 'Não foi possível carregar os dados.', 'error');
            }
        },

        async fetchNotifications() {
            try {
                const res = await fetch(`api/router.php?action=notifications`);
                const result = await res.json();
                if (result.status === 'success') {
                    this.notifications = result.data;
                }
            } catch (e) {
                console.error("Erro ao buscar notificações: ", e);
            }
        },


        get totals() {
            const incomeTotal = this.data.income.reduce((s, i) => s + parseFloat(i.amount), 0);
            const fixedTotal = this.data.fixed_expenses.reduce((s, i) => s + parseFloat(i.amount), 0);
            const variableTotal = this.data.variable_expenses.reduce((s, i) => s + (parseFloat(i.amount) / i.installments), 0);
            const purchasesTotal = Object.values(this.data.purchases).flat().reduce((s, i) => s + (parseFloat(i.amount) / i.installments), 0);
            return { income: incomeTotal, expenses: fixedTotal + variableTotal + purchasesTotal };
        },
        get cardTotal() {
            if (!this.activeCardId || !this.data.purchases[this.activeCardId]) return 0;
            return this.data.purchases[this.activeCardId].reduce((s, i) => s + (parseFloat(i.amount) / i.installments), 0);
        },
        openModal(type) {
            this.modal.type = type;
            this.form = {}; 
            this.categoryForm = { id: null, name: '' };
            const today = new Date().toISOString().split('T')[0];
            switch (type) {
                case 'income': this.modal.title = 'Adicionar Entrada'; this.form = { income_date: today, income_type: 'SALARIO' }; break;
                case 'fixed_expense': this.modal.title = 'Adicionar Despesa Fixa'; break;
                case 'variable_expense': this.modal.title = 'Adicionar Despesa Variável'; this.form = { purchase_date: today, installments: 1, initial_installment: 1 }; break;
                case 'card': this.modal.title = 'Gerir Cartões'; break;
                case 'purchase': this.modal.title = 'Adicionar Compra no Cartão'; this.form = { purchase_date: today, installments: 1, initial_installment: 1 }; break;
                case 'category': this.modal.title = 'Gerir Categorias'; break;
            }
            this.modal.open = true;
        },
        openEditModal(type, item) {
            this.modal.type = type;
            this.form = { ...item };
            if (this.form.category_id === null || this.form.category_id === undefined) {
                this.form.category_id = '';
            }
            switch (type) {
                case 'income': this.modal.title = 'Editar Entrada'; break;
                case 'fixed_expense': this.modal.title = 'Editar Despesa Fixa'; break;
                case 'variable_expense': this.modal.title = 'Editar Despesa Variável'; break;
                case 'card': this.modal.title = 'Editar Cartão'; break;
                case 'purchase': this.modal.title = 'Editar Compra no Cartão'; break;
            }
            this.modal.open = true;
        },
        closeModal() {
            this.modal.open = false;
        },

        openDetailsModal(item) {
            const valorTotal = parseFloat(item.amount);
            const totalParcelas = parseInt(item.installments);
            const valorParcela = valorTotal / totalParcelas;
            const initialInstallment = parseInt(item.initial_installment) || 1;
            
            const purchaseDateParts = item.purchase_date.split('-');
            const purchaseDate = new Date(purchaseDateParts[0], purchaseDateParts[1] - 1, purchaseDateParts[2]);
            
            const today = new Date();
            
            let firstPaymentDate; 
            let dueDay; 

            if (item.card_id && this.data.cards.length > 0) {
                const card = this.data.cards.find(c => c.id == item.card_id);
                if (!card) {
                    this.showToast('Erro: Cartão não encontrado para calcular detalhes.', 'error');
                    return;
                }
                
                const closingDay = parseInt(card.closing_day); 
                dueDay = parseInt(card.due_day);            

                const purchaseDay = purchaseDate.getDate(); 
                
                firstPaymentDate = new Date(purchaseDate.getFullYear(), purchaseDate.getMonth(), dueDay); 

                if (purchaseDay > closingDay) {
                    firstPaymentDate.setMonth(firstPaymentDate.getMonth() + 1);
                }

                firstPaymentDate.setMonth(firstPaymentDate.getMonth() + initialInstallment - 1);

            } else {
                // Para Despesas Variáveis, usamos o dia da compra como referência para o vencimento
                dueDay = purchaseDate.getDate();
                
                firstPaymentDate = new Date(purchaseDate.getFullYear(), purchaseDate.getMonth(), dueDay);
                firstPaymentDate.setMonth(firstPaymentDate.getMonth() + initialInstallment - 1);
            }
            
            let parcelasPagas = 0;
            if (today >= firstPaymentDate) {
                const monthsDiff = (today.getFullYear() - firstPaymentDate.getFullYear()) * 12 + (today.getMonth() - firstPaymentDate.getMonth());
                
                parcelasPagas = Math.max(0, monthsDiff);
                
                if (today.getDate() >= dueDay) {
                    parcelasPagas += 1;
                }
            }

            parcelasPagas = Math.min(parcelasPagas, totalParcelas);

            const parcelasFaltantes = totalParcelas - parcelasPagas;
            const valorPago = parcelasPagas * valorParcela;
            const valorRestante = parcelasFaltantes * valorParcela;
            
            this.detailsModal.title = `Detalhes de: ${item.description}`;
            this.detailsModal.summary = {
                valorTotal,
                totalParcelas,
                parcelasPagas,
                parcelasFaltantes,
                valorParcela,
                valorPago,
                valorRestante
            };
            this.detailsModal.show = true;
        },
        closeDetailsModal() {
            this.detailsModal.show = false;
        },
        
        async submitForm() {
            this.form.type = this.modal.type;
            const res = await fetch('api/router.php?action=data', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.form)
            });
            const result = await res.json();
            if (result.status === 'success') {
                this.showToast('Dados salvos com sucesso!', 'success');
                this.fetchData();
                this.closeModal();
            } else {
                this.showToast(result.message || 'Erro ao salvar.', 'error');
            }
        },
        deleteItem(type, id) {
            this.showConfirm('Tem certeza que deseja excluir? Esta ação não pode ser desfeita.', async () => {
                const res = await fetch(`api/router.php?action=data&type=${type}&id=${id}`, { method: 'DELETE' });
                const result = await res.json();
                if (result.status === 'success') {
                    this.showToast('Item excluído com sucesso.', 'success');
                    this.fetchData();
                } else {
                    this.showToast(result.message || 'Erro ao excluir.', 'error');
                }
            });
        },
        async logout() {
            await fetch('api/router.php?action=logout');
            window.location.reload();
        },
        
        // --- Funções de Categoria ---
        editCategory(cat) {
            this.categoryForm.id = cat.id;
            this.categoryForm.name = cat.name;
        },
        async submitCategoryForm() {
            const payload = {
                type: 'category',
                id: this.categoryForm.id,
                name: this.categoryForm.name
            };
            const res = await fetch('api/router.php?action=data', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.status === 'success') {
                this.showToast(`Categoria ${this.categoryForm.id ? 'atualizada' : 'adicionada'}!`, 'success');
                this.categoryForm = { id: null, name: '' }; 
                this.fetchData();
            } else {
                this.showToast(result.message || 'Erro ao salvar categoria.', 'error');
            }
        },

        // --- Funções de Gestão de Membros ---
        async openManageMembersModal() {
            this.manageModal.open = true;
            await this.fetchMembers();
        },
        closeManageMembersModal() {
            this.manageModal.open = false;
        },
        async fetchMembers() {
            try {
                const res = await fetch('api/router.php?action=members');
                const result = await res.json();
                if (result.status === 'success') {
                    this.manageModal.members = result.data.members;
                    this.manageModal.tokens = result.data.tokens;
                } else {
                    this.showToast(result.message, 'error');
                }
            } catch (e) {
                this.showToast('Erro ao buscar membros.', 'error');
            }
        },
        async createInviteToken() {
            try {
                const res = await fetch('api/router.php?action=create_token', { method: 'POST' });
                const result = await res.json();
                if (result.status === 'success') {
                    this.showToast('Novo convite gerado!', 'success');
                    this.fetchMembers(); // Atualiza a lista de tokens
                } else {
                    this.showToast(result.message, 'error');
                }
            } catch (e) {
                this.showToast('Erro ao gerar convite.', 'error');
            }
        },
        removeMember(id, name) {
            this.showConfirm(`Tem certeza que deseja remover ${name} do grupo?`, async () => {
                try {
                    const res = await fetch(`api/router.php?action=members&id=${id}`, { method: 'DELETE' });
                    const result = await res.json();
                    if (result.status === 'success') {
                        this.showToast('Membro removido com sucesso.', 'success');
                        this.fetchMembers(); // Atualiza a lista de membros
                    } else {
                        this.showToast(result.message, 'error');
                    }
                } catch (e) {
                    this.showToast('Erro ao remover membro.', 'error');
                }
            });
        },
        copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast('Token copiado!', 'success');
                }).catch(err => {
                    this.showToast('Falha ao copiar.', 'error');
                });
            } else {
                // Fallback para 'execCommand'
                const textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    this.showToast('Token copiado!', 'success');
                } catch (err) {
                    this.showToast('Falha ao copiar.', 'error');
                }
                document.body.removeChild(textArea);
            }
        },

        // --- Funções Utilitárias (Formatação e Notificação) ---
        formatCurrency(v) { return (parseFloat(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); },
        formatDate(d) { if (!d) return ''; const [y, m, day] = d.split('-'); return `${day}/${m}/${y}`; },
        
        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => this.toast.show = false, 4000);
        },
        showConfirm(message, onConfirmCallback) {
            this.confirmModal.message = message;
            this.confirmModal.onConfirm = onConfirmCallback;
            this.confirmModal.show = true;
        },
        closeConfirmModal() {
            this.confirmModal.show = false;
        },
        confirmAction() {
            this.confirmModal.onConfirm();
            this.closeConfirmModal();
        },

        // Função para Google Agenda
        createGoogleCalendarLink(notification) {
            const title = encodeURIComponent(notification.description);
            // Formata a data para YYYYMMDD
            const [day, month, year] = notification.due_date_formatted.split('/');
            const date = `${year}${month}${day}`;
            
            return `https://www.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${date}/${date}&details=Vencimento+gerado+automaticamente+pelo+Te+Controla.&sf=true&output=xml`;
        }
    }
}

// Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then(registration => { console.log('SW registado:', registration); })
            .catch(error => { console.error('Falha no registo do SW:', error); });
    });
}