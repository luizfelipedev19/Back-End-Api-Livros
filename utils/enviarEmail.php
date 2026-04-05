<?php

class enviarEmail {



    public function enviarEmail(string $email, string $nome, string $token): bool {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USER'];
            $mail->Password = $_ENV['MAIL_PASS'];
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($_ENV['MAIL_USER'], 'BookManager');
            $mail->addAddress($email, $nome);

            $linkRecuperacao = "http://192.168.0.38:8082/Front-Biblioteca/redefinir-senha?token=" . $token;

            $mail->isHTML(true);
            $mail->Subject = 'Recuperação de senha - BookManager';
            $mail->Body = "
                <h1>Olá, {$nome}</h1>
                <p>Recebemos uma solicitação para redefinir a sua senha.</p>
                <p>Clique no botão abaixo para criar uma nova senha:</p>
                <a href='{$linkRecuperacao}' style='
                    background: #3b82f6;
                    color: white;
                    padding: 12px 24px;
                    border-radius: 8px;
                    text-decoration: none;
                    display: inline-block;
                    margin: 20px 0;
                '>Redefinir senha</a>
                <p>Este link expira em <strong>1 hora</strong>.</p>
                <p>Se você não solicitou isso, ignore este email.</p>
            ";


            $mail->send();
            return true;

        } catch(Exception $e){
            error_log("Erro PHPMailer: " . $e->getMessage());
            return false;
        }
    }
}