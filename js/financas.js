/**
 * TeControla - Módulo Finanças Empresarial
 * Projeção 12 Meses | DRE | Metas Financeiras
 */

const Financas = (() => {

  // ─── INICIALIZAÇÃO ───────────────────────────────────────────────
  function init() {
    renderSection();
    loadProjection();
    loadGoals();
  }

  function renderSection() {
    const container = document.getElementById('financas-section');
    if (!container) return;
    container.innerHTML = `
      <div class="financas-tabs">
        <button class="tab-btn active" onclick="Financas.showTab('projecao')">📈 Projeção 12 Meses</button>
        <button class="tab-btn" onclick="Financas.showTab('dre')">📊 DRE</button>
        <button class="tab-btn" onclick="Financas.showTab('metas')">🎯 Metas</button>
      </div>
      <div id="tab-projecao" class="tab-content active"></div>
      <div id="tab-dre" class="tab-content"></div>
      <div id="tab-metas" class="tab-content"></div>
    `;
  }

  function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById(`tab-${tab}`).classList.add('active');
    event.target.classList.add('active');
    if (tab === 'dre') loadDRE();
  }

  // ─── PROJEÇÃO 12 MESES ────────────────────────────────────────────
  async function loadProjection() {
    const container = document.getElementById('tab-projecao');
    if (!container) return;
    container.innerHTML = '<p class="loading">Carregando projeção...</p>';

    try {
      const res = await fetch('/api/projections.php?action=projection');
      const json = await res.json();
      if (!json.success) throw new Error(json.error);
      renderProjection(json.data, container);
    } catch (e) {
      container.innerHTML = `<p class="error">Erro ao carregar projeção: ${e.message}</p>`;
    }
  }

  function renderProjection(data, container) {
    const labels   = data.map(d => d.month_label);
    const incomes  = data.map(d => d.projected_income);
    const expenses = data.map(d => d.projected_expense);
    const balances = data.map(d => d.balance);

    const totalIncome  = incomes.reduce((a, b) => a + b, 0);
    const totalExpense = expenses.reduce((a, b) => a + b, 0);
    const totalBalance = totalIncome - totalExpense;

    container.innerHTML = `
      <div class="financas-summary">
        <div class="summary-card income">
          <span>Receita Projetada (12m)</span>
          <strong>${formatCurrency(totalIncome)}</strong>
        </div>
        <div class="summary-card expense">
          <span>Despesa Projetada (12m)</span>
          <strong>${formatCurrency(totalExpense)}</strong>
        </div>
        <div class="summary-card balance ${totalBalance >= 0 ? 'positive' : 'negative'}">
          <span>Saldo Projetado (12m)</span>
          <strong>${formatCurrency(totalBalance)}</strong>
        </div>
      </div>

      <canvas id="chart-projection" height="100"></canvas>

      <table class="financas-table">
        <thead>
          <tr>
            <th>Mês</th>
            <th>Receita</th>
            <th>Despesa</th>
            <th>Saldo</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          ${data.map(d => `
            <tr class="${d.balance >= 0 ? 'row-positive' : 'row-negative'}">
              <td>${d.month_label}</td>
              <td>${formatCurrency(d.projected_income)}</td>
              <td>${formatCurrency(d.projected_expense)}</td>
              <td>${formatCurrency(d.balance)}</td>
              <td>${d.balance >= 0 ? '✅ Superávit' : '⚠️ Déficit'}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;

    // Gráfico com Chart.js
    if (typeof Chart !== 'undefined') {
      new Chart(document.getElementById('chart-projection'), {
        type: 'line',
        data: {
          labels,
          datasets: [
            { label: 'Receita', data: incomes,  borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.1)',  tension: 0.3, fill: true },
            { label: 'Despesa', data: expenses, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)',  tension: 0.3, fill: true },
            { label: 'Saldo',   data: balances, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', tension: 0.3, fill: false },
          ],
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: ctx => formatCurrency(ctx.raw) } } },
          scales: { y: { ticks: { callback: v => formatCurrency(v) } } }
        }
      });
    }
  }

  // ─── DRE ──────────────────────────────────────────────────────────
  async function loadDRE(year = new Date().getFullYear()) {
    const container = document.getElementById('tab-dre');
    if (!container) return;
    container.innerHTML = `
      <div class="dre-header">
        <label>Ano: <input type="number" id="dre-year" value="${year}" min="2020" max="2030" onchange="Financas.loadDRE(this.value)"></label>
      </div>
      <p class="loading">Carregando DRE...</p>
    `;
    try {
      const res = await fetch(`/api/projections.php?action=dre&year=${year}`);
      const json = await res.json();
      if (!json.success) throw new Error(json.error);
      renderDRE(json, container);
    } catch (e) {
      container.innerHTML += `<p class="error">Erro: ${e.message}</p>`;
    }
  }

  function renderDRE(data, container) {
    container.querySelector('.loading')?.remove();
    const t = data.totais;
    container.innerHTML += `
      <div class="financas-summary">
        <div class="summary-card income"><span>Receita Anual</span><strong>${formatCurrency(t.receita_bruta)}</strong></div>
        <div class="summary-card expense"><span>Despesas Anuais</span><strong>${formatCurrency(t.total_despesas)}</strong></div>
        <div class="summary-card balance ${t.resultado_operacional >= 0 ? 'positive' : 'negative'}">
          <span>Resultado (Margem ${t.margem_liquida}%)</span>
          <strong>${formatCurrency(t.resultado_operacional)}</strong>
        </div>
      </div>
      <table class="financas-table">
        <thead>
          <tr><th>Mês</th><th>Receita</th><th>Desp. Fixas</th><th>Desp. Variáveis</th><th>Cartão</th><th>Total Desp.</th><th>Resultado</th><th>Margem</th></tr>
        </thead>
        <tbody>
          ${data.months.map(m => `
            <tr class="${m.resultado_operacional >= 0 ? 'row-positive' : 'row-negative'}">
              <td>${m.month_label}</td>
              <td>${formatCurrency(m.receita_bruta)}</td>
              <td>${formatCurrency(m.despesas_fixas)}</td>
              <td>${formatCurrency(m.despesas_variaveis)}</td>
              <td>${formatCurrency(m.despesas_cartao)}</td>
              <td>${formatCurrency(m.total_despesas)}</td>
              <td>${formatCurrency(m.resultado_operacional)}</td>
              <td>${m.margem_liquida}%</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  // ─── METAS FINANCEIRAS ────────────────────────────────────────────
  async function loadGoals() {
    const container = document.getElementById('tab-metas');
    if (!container) return;

    container.innerHTML = `
      <button class="btn-primary" onclick="Financas.openGoalModal()">+ Nova Meta</button>
      <div id="goals-list"><p class="loading">Carregando metas...</p></div>
      <div id="goal-modal" class="modal hidden">
        <div class="modal-content">
          <h3 id="goal-modal-title">Nova Meta</h3>
          <form id="goal-form" onsubmit="Financas.saveGoal(event)">
            <input type="hidden" id="goal-id">
            <label>Nome: <input type="text" id="goal-name" required></label>
            <label>Valor Alvo (R$): <input type="number" id="goal-target" step="0.01" required></label>
            <label>Valor Atual (R$): <input type="number" id="goal-current" step="0.01" value="0"></label>
            <label>Prazo: <input type="date" id="goal-deadline"></label>
            <label>Tipo:
              <select id="goal-type">
                <option value="economia">💰 Economia</option>
                <option value="investimento">📈 Investimento</option>
                <option value="reserva">🛡️ Reserva de Emergência</option>
                <option value="outro">📌 Outro</option>
              </select>
            </label>
            <div class="modal-actions">
              <button type="submit" class="btn-primary">Salvar</button>
              <button type="button" class="btn-secondary" onclick="Financas.closeGoalModal()">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    `;

    try {
      const res = await fetch('/api/goals.php');
      const json = await res.json();
      renderGoals(json.data || []);
    } catch (e) {
      document.getElementById('goals-list').innerHTML = `<p class="error">Erro: ${e.message}</p>`;
    }
  }

  function renderGoals(goals) {
    const list = document.getElementById('goals-list');
    if (!goals.length) { list.innerHTML = '<p>Nenhuma meta cadastrada ainda.</p>'; return; }

    const typeIcon = { economia: '💰', investimento: '📈', reserva: '🛡️', outro: '📌' };
    list.innerHTML = goals.map(g => `
      <div class="goal-card">
        <div class="goal-header">
          <span class="goal-type">${typeIcon[g.type] || '📌'}</span>
          <h4>${g.name}</h4>
          <div class="goal-actions">
            <button onclick="Financas.openGoalModal(${JSON.stringify(g).replace(/"/g, '&quot;')})">✏️</button>
            <button onclick="Financas.deleteGoal(${g.id})">🗑️</button>
          </div>
        </div>
        <div class="goal-progress">
          <div class="progress-bar"><div class="progress-fill" style="width:${Math.min(g.progress_pct,100)}%"></div></div>
          <span>${g.progress_pct}% — ${formatCurrency(g.current_amount)} / ${formatCurrency(g.target_amount)}</span>
        </div>
        ${g.deadline ? `<small>⏳ Prazo: ${formatDate(g.deadline)} ${g.days_remaining >= 0 ? `(${g.days_remaining} dias)` : '(vencida)'}</small>` : ''}
      </div>
    `).join('');
  }

  function openGoalModal(goal = null) {
    document.getElementById('goal-modal').classList.remove('hidden');
    document.getElementById('goal-modal-title').textContent = goal ? 'Editar Meta' : 'Nova Meta';
    document.getElementById('goal-id').value       = goal?.id ?? '';
    document.getElementById('goal-name').value     = goal?.name ?? '';
    document.getElementById('goal-target').value   = goal?.target_amount ?? '';
    document.getElementById('goal-current').value  = goal?.current_amount ?? 0;
    document.getElementById('goal-deadline').value = goal?.deadline ?? '';
    document.getElementById('goal-type').value     = goal?.type ?? 'outro';
  }

  function closeGoalModal() {
    document.getElementById('goal-modal').classList.add('hidden');
  }

  async function saveGoal(e) {
    e.preventDefault();
    const id = document.getElementById('goal-id').value;
    const payload = {
      name:           document.getElementById('goal-name').value,
      target_amount:  document.getElementById('goal-target').value,
      current_amount: document.getElementById('goal-current').value,
      deadline:       document.getElementById('goal-deadline').value,
      type:           document.getElementById('goal-type').value,
    };
    const method = id ? 'PUT' : 'POST';
    const url    = id ? `/api/goals.php?id=${id}` : '/api/goals.php';
    await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    closeGoalModal();
    loadGoals();
  }

  async function deleteGoal(id) {
    if (!confirm('Remover esta meta?')) return;
    await fetch(`/api/goals.php?id=${id}`, { method: 'DELETE' });
    loadGoals();
  }

  // ─── UTILITÁRIOS ─────────────────────────────────────────────────
  function formatCurrency(v) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v ?? 0);
  }

  function formatDate(d) {
    if (!d) return '';
    const [y, m, day] = d.split('-');
    return `${day}/${m}/${y}`;
  }

  return { init, showTab, loadDRE, openGoalModal, closeGoalModal, saveGoal, deleteGoal };
})();

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('financas-section')) Financas.init();
});
