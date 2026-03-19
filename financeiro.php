<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['group_id'])) {
    header('Location: default.php');
    exit;
}

require_once 'db_config.php';

$stmt = $pdo->prepare("SELECT group_type, show_financial_projection FROM `groups` WHERE id = ?");
$stmt->execute([$_SESSION['group_id']]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group || $group['group_type'] !== 'empresa' || (int)$group['show_financial_projection'] !== 1) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Projeção Financeira - TeControla</title>
<link rel="stylesheet" href="css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  :root {
    --green:#22c55e; --red:#ef4444; --blue:#3b82f6;
    --bg:#0f172a; --card:#1e293b; --border:#334155;
    --text:#f1f5f9; --muted:#94a3b8;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{background:var(--bg);color:var(--text);font-family:'Segoe UI',sans-serif;min-height:100vh}
  .top-bar{
    display:flex;align-items:center;justify-content:space-between;
    background:var(--card);border-bottom:1px solid var(--border);
    padding:12px 20px;position:sticky;top:0;z-index:10;
  }
  .top-bar h1{font-size:1.1rem;font-weight:700}
  .top-bar a{color:var(--muted);text-decoration:none;font-size:.85rem}
  .top-bar a:hover{color:var(--text)}
  .container{max-width:1100px;margin:0 auto;padding:24px 16px}
  .filter-bar{
    display:flex;gap:12px;align-items:center;flex-wrap:wrap;
    background:var(--card);border:1px solid var(--border);
    border-radius:10px;padding:14px 18px;margin-bottom:24px;
  }
  .filter-bar label{font-size:.85rem;color:var(--muted)}
  .filter-bar select,.filter-bar button{
    background:var(--bg);border:1px solid var(--border);border-radius:7px;
    color:var(--text);padding:7px 14px;font-size:.9rem;cursor:pointer;
  }
  .filter-bar button{background:var(--blue);border-color:var(--blue);font-weight:600}
  .filter-bar button:hover{opacity:.85}
  .kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px}
  .kpi-card{
    background:var(--card);border:1px solid var(--border);
    border-radius:10px;padding:16px;text-align:center;
  }
  .kpi-label{font-size:.75rem;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em}
  .kpi-value{font-size:1.3rem;font-weight:700}
  .kpi-value.green{color:var(--green)}.kpi-value.red{color:var(--red)}.kpi-value.blue{color:var(--blue)}
  .section{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:24px}
  .section h2{font-size:.85rem;font-weight:600;margin-bottom:16px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em}
  .chart-wrap{position:relative;height:280px}
  table{width:100%;border-collapse:collapse;font-size:.85rem}
  thead th{background:var(--bg);color:var(--muted);font-weight:600;text-align:right;padding:8px 10px;border-bottom:1px solid var(--border)}
  thead th:first-child{text-align:left}
  tbody td{padding:8px 10px;border-bottom:1px solid var(--border);text-align:right}
  tbody td:first-child{text-align:left;font-weight:600}
  tbody tr:hover{background:rgba(255,255,255,.03)}
  .pos{color:var(--green);font-weight:700}.neg{color:var(--red);font-weight:700}
  .dre-separator{border-top:2px solid var(--border)!important}
  .loading{text-align:center;padding:40px;color:var(--muted)}
  @media(max-width:600px){table{font-size:.75rem}thead th,tbody td{padding:6px 6px}}
</style>
</head>
<body>

<div class="top-bar">
  <h1>📈 Projeção Financeira</h1>
  <a href="index.php">← Voltar ao Painel</a>
</div>

<div class="container">

  <div class="filter-bar">
    <label>Ano:</label>
    <select id="sel-year"></select>
    <button onclick="loadAll()">Atualizar</button>
  </div>

  <div class="kpi-grid" id="kpi-grid">
    <div class="loading">Carregando...</div>
  </div>

  <div class="section">
    <h2>📊 Fluxo de Caixa Mensal</h2>
    <div class="chart-wrap"><canvas id="chart-fluxo"></canvas></div>
  </div>

  <div class="section">
    <h2>📅 Projeção Detalhada (12 meses)</h2>
    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th>Mês</th><th>Receitas</th><th>Fixas</th><th>Variáveis</th>
            <th>Cartão</th><th>Total Desp.</th><th>Saldo</th><th>Acumulado</th>
          </tr>
        </thead>
        <tbody id="tbl-body"><tr><td colspan="8" class="loading">Carregando...</td></tr></tbody>
      </table>
    </div>
  </div>

  <div class="section">
    <h2>📉 DRE Simplificado — <span id="dre-mes"></span></h2>
    <table style="max-width:460px">
      <tbody id="dre-body"><tr><td colspan="2" class="loading">Carregando...</td></tr></tbody>
    </table>
  </div>

</div>

<script>
const fmt = v => 'R$ ' + parseFloat(v).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2});
const cl  = v => parseFloat(v) >= 0 ? 'pos' : 'neg';
let fluxoChart = null;

// Popula seletor de ano
(()=>{
  const sel = document.getElementById('sel-year');
  const now = new Date().getFullYear();
  for(let y = now-1; y <= now+2; y++){
    const o = document.createElement('option');
    o.value = y; o.text = y;
    if(y === now) o.selected = true;
    sel.appendChild(o);
  }
})();

function apiUrl(subaction, extra='') {
  return `api/router.php?action=financeiro&subaction=${subaction}&${extra}`;
}

async function loadAll() {
  const year = document.getElementById('sel-year').value;
  await Promise.all([loadProjecao(year), loadDRE(year)]);
}

async function loadProjecao(year) {
  try {
    const res  = await fetch(apiUrl('projecao', `year=${year}`));
    const json = await res.json();
    if (json.status !== 'success') { console.error(json.message); return; }

    const { projection, kpis } = json;

    // KPIs
    document.getElementById('kpi-grid').innerHTML = `
      <div class="kpi-card"><div class="kpi-label">Receita Anual</div>
        <div class="kpi-value blue">${fmt(kpis.total_receitas_ano)}</div></div>
      <div class="kpi-card"><div class="kpi-label">Despesa Anual</div>
        <div class="kpi-value red">${fmt(kpis.total_despesas_ano)}</div></div>
      <div class="kpi-card"><div class="kpi-label">Resultado Ano</div>
        <div class="kpi-value ${kpis.resultado_ano>=0?'green':'red'}">${fmt(kpis.resultado_ano)}</div></div>
      <div class="kpi-card"><div class="kpi-label">Burn Rate Médio</div>
        <div class="kpi-value red">${fmt(kpis.burn_rate_medio)}/mês</div></div>
      <div class="kpi-card"><div class="kpi-label">Receita Média</div>
        <div class="kpi-value blue">${fmt(kpis.receita_media)}/mês</div></div>
      <div class="kpi-card"><div class="kpi-label">Meses Déficit</div>
        <div class="kpi-value ${kpis.meses_deficit>0?'red':'green'}">${kpis.meses_deficit} / 12</div></div>
    `;

    // Tabela
    document.getElementById('tbl-body').innerHTML = projection.map(r => `
      <tr>
        <td>${r.month}</td><td>${fmt(r.receitas)}</td><td>${fmt(r.despesas_fixas)}</td>
        <td>${fmt(r.despesas_variaveis)}</td><td>${fmt(r.parcelas_cartao)}</td>
        <td>${fmt(r.total_despesas)}</td>
        <td class="${cl(r.saldo)}">${fmt(r.saldo)}</td>
        <td class="${cl(r.acumulado)}">${fmt(r.acumulado)}</td>
      </tr>
    `).join('');

    // Gráfico
    if(fluxoChart) fluxoChart.destroy();
    fluxoChart = new Chart(document.getElementById('chart-fluxo').getContext('2d'), {
      data: {
        labels: projection.map(r=>r.month),
        datasets: [
          {type:'bar', label:'Receitas', data:projection.map(r=>r.receitas),
           backgroundColor:'rgba(34,197,94,.6)', borderColor:'#22c55e', borderWidth:1},
          {type:'bar', label:'Despesas', data:projection.map(r=>r.total_despesas),
           backgroundColor:'rgba(239,68,68,.6)', borderColor:'#ef4444', borderWidth:1},
          {type:'line', label:'Saldo', data:projection.map(r=>r.saldo),
           borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,.15)', tension:.35, fill:true, pointRadius:4},
        ]
      },
      options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{legend:{labels:{color:'#94a3b8'}}},
        scales:{
          x:{ticks:{color:'#94a3b8'},grid:{color:'rgba(255,255,255,.05)'}},
          y:{ticks:{color:'#94a3b8',callback:v=>'R$'+v.toLocaleString('pt-BR')},grid:{color:'rgba(255,255,255,.05)'}}
        }
      }
    });
  } catch(e){ console.error('Erro projeção:', e); }
}

async function loadDRE(year) {
  const month = new Date().getMonth()+1;
  const meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
  document.getElementById('dre-mes').textContent = meses[month-1]+'/'+year;
  try {
    const res  = await fetch(apiUrl('dre', `month=${month}&year=${year}`));
    const json = await res.json();
    if(json.status !== 'success') { console.error(json.message); return; }
    const d = json.dre;
    document.getElementById('dre-body').innerHTML = `
      <tr><td>Receita Bruta</td><td class="pos">${fmt(d.receita_bruta)}</td></tr>
      <tr><td>(-) Custos Variáveis</td><td class="neg">- ${fmt(d.custos_variaveis)}</td></tr>
      <tr class="dre-separator"><td><strong>Lucro Bruto</strong></td>
        <td class="${cl(d.lucro_bruto)}"><strong>${fmt(d.lucro_bruto)}</strong></td></tr>
      <tr><td>(-) Custos Fixos</td><td class="neg">- ${fmt(d.custos_fixos)}</td></tr>
      <tr class="dre-separator"><td><strong>Lucro Líquido</strong></td>
        <td class="${cl(d.lucro_liquido)}"><strong>${fmt(d.lucro_liquido)}</strong></td></tr>
      <tr><td>Margem Líquida</td>
        <td class="${d.margem_liquida>=0?'pos':'neg'}">${d.margem_liquida}%</td></tr>
    `;
  } catch(e){ console.error('Erro DRE:', e); }
}

loadAll();
</script>
</body>
</html>
