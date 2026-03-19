# Te Controla - Gestão Financeira

Plataforma de gestão financeira pessoal e empresarial desenvolvida em PHP puro, Alpine.js e Tailwind CSS.

## 🚀 Novidades / SaaS
O sistema evoluiu para um modelo Multi-Tenant/SaaS utilizando Asaas para o processamento de assinaturas:
- **Painel Super Administrador:** Visão geral e gestão de planos, provisionamentos de trials (7, 14, 30 dias) ou permissão direta.
- **Asaas V3:** Integração nativa com API do Asaas v3.
- **Segurança (Cron Job):** Rotina Server-side blindada `/cron/check_subscriptions.php` para suspensão automática de clientes faltosos.
- **Webhooks:** Recebedor assíncrono blindado para eventos de cobrança, mantendo as flags do banco de dados vivas.

## ⚙️ Instalação / Deployment
1. Suba todos os diretórios no servidor, certificando-se de apontar o `DocumentRoot` (Apache/Nginx) preferencialmente para o sub-diretório `/public` (ou caso use a raiz do projeto, confie no arquivo `.htaccess` mantido na raiz para redirecionamento silencioso).
2. Verifique o arquivo `u540193243_te_controla_db.sql` e insira no seu SQL remoto.
3. Renomeie o arquivo `.env.example` para `.env` e defina suas senhas, portas DB, e configurações (o parser de ambientes nativo e exclusivo do projeto irá lidar magicamente com essas lógicas para a `$_ENV`), protegendo as credenciais fora do histórico do repositório.

### Banco de Dados (SaaS)
Após importar o banco inicial, é obrigatório executar a migration `migrations/2026_03_19_add_business_flags_to_groups.sql` no seu banco de dados. Essa migration moderniza as tabelas com as colunas de assinantes, status da conta (Trial, Bloqueado) e histórico de pagamentos do Asaas!

### Asaas Settings e Webhooks
Defina suas chaves no `.env`. Utilize o token do Asaas gerado dentro do painel para o `ASAAS_WEBHOOK_TOKEN` e insira as credenciais da API Asaas. Configure o destino do Webhook no painel do Asaas como `https://tecontrola.com.br/api/webhook_asaas.php`.

**Eventos Obrigatórios no Painel do Asaas:**
Certifique-se de habilitar e checar todos esses eventos:
- PAYMENT_CONFIRMED, PAYMENT_RECEIVED, PAYMENT_OVERDUE, PAYMENT_DELETED, PAYMENT_RESTORED
- SUBSCRIPTION_CREATED, SUBSCRIPTION_RENEWED, SUBSCRIPTION_DELETED

No campo "Token de Acesso" ou secret token das configurações do seu webhook online, insira a mesma hash que você injetou no seu `ASAAS_WEBHOOK_TOKEN` no `.env`.

### Cron Job Automatizado
Adicione a seguinte rotina diária no painel do seu CPanel ou Servidor:
`0 0 * * * php /caminho/absoluto/do/projeto/cron/check_subscriptions.php`
*(Nota: a execução via navegador é negada por segurança; o script roda exclusivamente CLI para proteger brechas).*
