<?php

require_once __DIR__ . '../../DTO/recuperarSenhaDTO.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../vendor/autoload.php'; 



class SenhaController{
    private Usuarios $usuarioModel;
    private PDO $db;

    public function __construct(PDO $db){
        $this->usuarioModel = new Usuarios($db);
        $this->db = $db;
    }

    public function solicitarRecuperacao(): void {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];


    $emailUsuario = trim($data['email'] ?? '');

    if(!$emailUsuario){
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "mensagem" => "E-mail é obrigatório"
        ]);
        return;
    }

    $usuario = $this->usuarioModel->buscarPorEmail($emailUsuario);

    if(!$usuario){
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "mensagem" => "E-mail não encontrado no sistema"
        ]);
        return;
    }

    // para gerar um token único aleatório, é desta forma.
    $token = bin2hex(random_bytes(32));
    $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $this->db->prepare("insert into senha_recuperacao (usuario_id, token, expira_em) Values (:usuario_id, :token, :expira_em)");

    $stmt->bindValue(':usuario_id', $usuario['id_usuario'], PDO::PARAM_INT);
    $stmt->bindValue(':token', $token);
    $stmt->bindValue(':expira_em', $expiracao);
    $stmt->execute();

    $emailEnviado = $this->enviarEmailRecuperacao(
        $emailUsuario,
        $usuario['nome'],
        $token
    );

    if(!$emailEnviado){
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "mensagem" => "Erro ao enviar email"
        ]);
        return;
    }
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "mensagem" => "Por favor, verifique a sua caixa de e-mail e siga às instruções",
        "token" => $token
    ]);
    }

    private function enviarEmailRecuperacao(string $email, string $nome, string $token): bool {

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // configuração do SMTP

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USER'];
            $mail->Password = $_ENV['MAIL_PASS'];
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';


            //configurar remetente e o destinatário

            $mail->setFrom($_ENV['MAIL_USER'], 'BookManager');

            $mail->addAddress($email, $nome);

            //conteudo do email
            $linkRecuperacao = "http://localhost:8082/Front-Biblioteca/redefinir-senha?token=" . $token;


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
                <p>Este link expira em <strong> 1 hora </strong>.</p>
                <p>Se voce não solicitou isso, ignore este email.</p>";

                $mail->send();
                return true;
        } catch(Exception $e){
            return false;
        }

    }


    public function redefinirSenha():void {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        $token = $data['token'] ?? '';
        $senhaNova = trim($data['senha']) ?? '';
        if ($token === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "mensagem" => "Token é obrigatório"]);
        return;
        }

        if ($senhaNova === '') {
         http_response_code(400);
        echo json_encode(["success" => false, "mensagem" => "Senha é obrigatória"]);
        return;
        }
        $agora = date('Y-m-d H:i:s');

        $sql = "SELECT  usuario_id from senha_recuperacao
        WHERE token = :token
        and usado = 0
        and expira_em > :agora
        limit 1";

        $stmt = $this->db->prepare($sql);   
        $stmt->execute(['token' => $token, 'agora' =>$agora]);

        

    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$registro) {
        http_response_code(400);
        echo json_encode([
            "success" => false, 
            "mensagem" => "Token inválido ou expirado"
        ]);
        return;
    }
    //atualizar a senha
    $senhaHash = password_hash($senhaNova, PASSWORD_DEFAULT);
    $stmtSenha = $this->db->prepare("UPDATE usuarios SET senha_hash = :senha where id_usuario = :id");
    $stmtSenha->bindValue(':senha', $senhaHash);
    $stmtSenha->bindValue(':id', $registro['usuario_id'], PDO::PARAM_INT);
    $stmtSenha->execute();

    //marcando o token como usado

    $stmtToken = $this->db->prepare("UPDATE senha_recuperacao set usado = 1 WHERE token = :token");
    $stmtToken->bindValue(':token', $token);
    $stmtToken->execute();


    http_response_code(200);
    echo json_encode([
        "success" => true, 
        "mensagem" => "Senha redefinida com sucesso"
    ]);

    }
}