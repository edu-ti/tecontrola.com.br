<?php
// Carrega as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Caminho absoluto para a raiz do projeto (public_html)
$project_root = __DIR__ . '/../';

// Carrega os ficheiros do PHPMailer
require $project_root . 'vendor/phpmailer/src/Exception.php';
require $project_root . 'vendor/phpmailer/src/PHPMailer.php';
require $project_root . 'vendor/phpmailer/src/SMTP.php';

// --- CONFIGURAÇÕES DE E-MAIL (PREENCHA AQUI) ---
define('SMTP_HOST', 'smtp.hostinger.com');         // Servidor SMTP (ex: smtp.hostinger.com)
define('SMTP_USERNAME', 'nao-responda@tecontrola.com.br'); // O seu e-mail completo
define('SMTP_PASSWORD', 'g3st@03Du4rd0');     // A senha do seu e-mail
define('SMTP_PORT', 465);                         // Porta (465 para SSL ou 587 para TLS)
define('SMTP_SECURE', PHPMailer::ENCRYPTION_SMTPS);  // Use `PHPMailer::ENCRYPTION_SMTPS` para porta 465 (SSL) ou `PHPMailer::ENCRYPTION_STARTTLS` para 587 (TLS)
define('EMAIL_FROM', 'nao-responda@tecontrola.com.br'); // O seu e-mail
define('EMAIL_FROM_NAME', 'Te Controla - Finanças'); // Nome que aparecerá como remetente
// --- FIM DAS CONFIGURAÇÕES DE E-MAIL ---


// --- CONFIGURAÇÃO DO SCRIPT ---
date_default_timezone_set('America/Sao_Paulo');

// Carrega a configuração da base de dados
try {
    require_once $project_root . 'db_config.php';
} catch (PDOException $e) {
    log_error("Falha na conexão com a base de dados: " . $e->getMessage());
    exit;
}

// --- FUNÇÕES AUXILIARES ---
function log_error($message) {
    $log_file = __DIR__ . '/email_reminders.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

function send_email($to_email, $to_name, $subject, $body) {
    $mail = new PHPMailer(true); // Ativa exceções

    try {
        // Configurações do Servidor
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Descomente para depuração detalhada
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Remetente e Destinatário
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo(EMAIL_FROM, EMAIL_FROM_NAME);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Versão em texto simples

        $mail->send();
        return true;
    } catch (Exception $e) {
        log_error("Falha ao enviar e-mail para $to_email. Erro: {$mail->ErrorInfo}");
        return false;
    }
}

// --- LÓGICA PRINCIPAL ---
echo "Iniciando script de lembretes via PHPMailer...\n";

try {
    global $pdo;
    
    // 1. Encontra todos os grupos e os seus utilizadores (e-mails)
    $stmt_groups = $pdo->query("SELECT g.id as group_id, u.email, u.name 
                               FROM groups g 
                               JOIN users u ON u.group_id = g.id");
    $groups = $stmt_groups->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

    if (!$groups) {
        echo "Nenhum grupo encontrado.\n";
        exit;
    }

    $today = date('Y-m-d');
    $check_day_plus_1 = (int)date('d', strtotime('+1 day')); // Amanhã
    $check_day_plus_0 = (int)date('d', strtotime('today'));  // Hoje

    // 2. Itera sobre cada grupo
    foreach ($groups as $group_id => $users) {
        $reminders = [];
        
        echo "Verificando grupo $group_id...\n";

        // 3. Verifica Despesas Fixas do grupo
        $stmt_fixed = $pdo->prepare("
            SELECT description, due_day 
            FROM fixed_expenses 
            WHERE group_id = :group_id AND (due_day = :day0 OR due_day = :day1)
        ");
        $stmt_fixed->execute(['group_id' => $group_id, 'day0' => $check_day_plus_0, 'day1' => $check_day_plus_1]);
        $fixed_expenses = $stmt_fixed->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fixed_expenses as $expense) {
            $due_day = (int)$expense['due_day'];
            $message = ($due_day === $check_day_plus_0) ? "(Vence Hoje)" : "(Vence Amanhã)";
            $reminders[] = "<li>Despesa Fixa: <strong>{$expense['description']}</strong> {$message}</li>";
        }

        // 4. Verifica Vencimentos de Cartões do grupo
        $stmt_cards = $pdo->prepare("
            SELECT name, due_day 
            FROM cards 
            WHERE group_id = :group_id AND (due_day = :day0 OR due_day = :day1)
        ");
        $stmt_cards->execute(['group_id' => $group_id, 'day0' => $check_day_plus_0, 'day1' => $check_day_plus_1]);
        $cards = $stmt_cards->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cards as $card) {
            $due_day = (int)$card['due_day'];
            $message = ($due_day === $check_day_plus_0) ? "(Vence Hoje)" : "(Vence Amanhã)";
            $reminders[] = "<li>Fatura do Cartão: <strong>{$card['name']}</strong> {$message}</li>";
        }

        // 5. Se houver lembretes, envia o e-mail
        if (!empty($reminders)) {
            $email_body = "
                <html>
                <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                    <h2>Olá!</h2>
                    <p>Estes são os seus lembretes de vencimento para hoje e amanhã:</p>
                    <ul style='padding-left: 20px;'>
            ";
            
            $email_body .= implode("\n", $reminders);
            
            $email_body .= "
                    </ul>
                    <p>Aceda ao <a href='https://tecontrola.com.br'>Te Controla Finanças</a> para ver os detalhes.</p>
                    <p style='font-size: 0.9em; color: #888;'>Este é um e-mail automático, por favor não responda.</p>
                </body>
                </html>
            ";

            $subject = "Lembrete de Vencimentos - Te Controla";

            // Envia o e-mail para todos os utilizadores do grupo
            foreach ($users as $user) {
                // Personaliza o "Olá" para cada utilizador
                $personalized_body = str_replace("<h2>Olá!</h2>", "<h2>Olá, {$user['name']}!</h2>", $email_body);
                
                send_email($user['email'], $user['name'], $subject, $personalized_body);
                echo "-> E-mail enviado para {$user['email']}\n";
            }
        } else {
            echo "-> Nenhum lembrete para este grupo.\n";
        }
    }

    echo "Script de lembretes concluído.\n";

} catch (PDOException $e) {
    log_error("Erro fatal na execução do script: " . $e->getMessage());
    echo "Erro fatal: " . $e->getMessage() . "\n";
}
?>

