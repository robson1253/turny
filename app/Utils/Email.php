<?php

namespace App\Utils; // <-- NAMESPACE CORRIGIDO

// Importa as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Email // <-- NOME DA CLASSE CORRIGIDO
{
    /**
     * Envia um e-mail.
     * @param string $toEmail - O e-mail do destinatário.
     * @param string $toName - O nome do destinatário.
     * @param string $subject - O assunto do e-mail.
     * @param string $body - O corpo do e-mail (pode ser HTML).
     * @return bool - Retorna true se o e-mail foi enviado, false se não.
     */
    public static function sendEmail(string $toEmail, string $toName, string $subject, string $body): bool
    {
        // Cria uma nova instância do PHPMailer
        $mail = new PHPMailer(true);

        try {
            // --- Configurações do Servidor ---
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Descomente para depuração detalhada
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            
            // PREENCHA COM AS SUAS CREDENCIAIS DO GMAIL
            $mail->Username   = 'turny2025@gmail.com'; 
            $mail->Password   = 'uimkeynbazofzhdx'; // A senha de app que você gerou

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            // --- Remetente e Destinatário ---
            $mail->setFrom('SEU_EMAIL_GMAIL@gmail.com', 'TURNY Plataforma');
            $mail->addAddress($toEmail, $toName);

            // --- Conteúdo do E-mail ---
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body); // Versão em texto puro para clientes de e-mail que não suportam HTML

            $mail->send();
            return true;

        } catch (Exception $e) {
            // Em produção, gravaríamos o erro num log em vez de o mostrar.
            // echo "A mensagem não pôde ser enviada. Erro do Mailer: {$mail->ErrorInfo}";
            return false;
        }
    }
}