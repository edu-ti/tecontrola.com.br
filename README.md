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
3. Importante: Não se esqueça de correr o script da respectiva pasta `/migrations/002_saas_subscription.sql` para modernizar seu banco com as colunas de assinantes!
4. Renomeie o arquivo `.env.example` para `.env` e defina suas senhas, portas DB, e configurações (o parser de ambientes nativo e exclusivo do projeto irá lidar magicamente com essas lógicas para a `$_ENV`), protegendo as credenciais fora do histórico do repositório.

### Asaas Settings
Defina as credenciais do Asaas no próprio arquivo `.env` sem aspas ou complicações (conforme exemplo em `.env.example`).
Configure o destino do Webhook no seu ambiente de Painel Asaas como `https://seusite.com.br/api/webhook_asaas.php` e assegure-se de injetar a chave Webhook Token.

### Cron Job Automatizado
Adicione a seguinte rotina diária no painel da sua hospedagem:
`0 0 * * * php /caminho/do/projeto/cron/check_subscriptions.php`
*(Nota: a execução via navegador é negada por segurança; o script roda exclusivamente CLI para proteger brechas).*
