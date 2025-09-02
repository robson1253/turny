<?php

namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Email
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
        $mail = new PHPMailer(true);

        try {
            // --- Configurações do Servidor ---
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Descomente esta linha para depuração detalhada do envio

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            // --- INÍCIO DA CORREÇÃO ---
            // As credenciais agora são lidas de forma segura a partir das variáveis de ambiente ($_ENV).
            // Garanta que estas variáveis estão definidas no seu arquivo .env
            $mail->Username   = $_ENV['EMAIL_USERNAME'];
            $mail->Password   = $_ENV['EMAIL_PASSWORD']; 
            // --- FIM DA CORREÇÃO ---

            // --- Remetente e Destinatário ---
            $mail->setFrom($_ENV['EMAIL_USERNAME'], 'TURNY Plataforma');
            $mail->addAddress($toEmail, $toName);

            // --- Conteúdo do E-mail ---
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body); // Versão em texto puro para clientes de e-mail que não suportam HTML

            $mail->send();
            return true;

        } catch (Exception $e) {
            // Em ambiente de produção, é crucial registar o erro em vez de o exibir.
            // O nosso manipulador de erros global no index.php já faz isso.
            // Lançar a exceção permite que o erro seja capturado e logado.
            throw new Exception("Erro ao enviar e-mail: {$mail->ErrorInfo}");
            return false;
        }
    }
}
