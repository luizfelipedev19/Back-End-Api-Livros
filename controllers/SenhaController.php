<?php

require_once __DIR__ . '../../DTO/recuperarSenhaDTO.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../utils/enviarEmail.php'; 

class SenhaController {
    private Usuarios $usuarioModel;
    private PDO $db;
    private array $data;
    private enviarEmail $enviarEmail;

    public function __construct(PDO $db){
        $this->usuarioModel = new Usuarios($db);
        $this->db = $db;
        $this->data = json_decode(file_get_contents("php://input"), true) ?? [];
        $this->enviarEmail = new enviarEmail();
    }

    public function solicitarRecuperacao(): void {
        $this->data;

        $emailUsuario = trim($this->data['email'] ?? '');

        if(!$emailUsuario){
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "mensagem" => "E-mail é obrigatório"
            ]);
            return;
        }

        $usuario = $this->usuarioModel->buscarPorEmail($emailUsuario);

        if($usuario){
            //  Invalida tokens antigos
            $this->db->prepare("
                UPDATE senha_recuperacao
                SET usado = 1
                WHERE usuario_id = :id
            ")->execute([
                'id' => $usuario['id_usuario']
            ]);

            //  Gerar um token seguro
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $this->db->prepare("
                INSERT INTO senha_recuperacao 
                (usuario_id, token, expira_em) 
                VALUES (:usuario_id, :token, :expira_em)
            ");

            $stmt->execute([
                'usuario_id' => $usuario['id_usuario'],
                'token' => $tokenHash,
                'expira_em' => $expiracao
            ]);

            //  Envia email com token 
            $this->enviarEmailRecuperacao(
                $emailUsuario,
                $usuario['nome'],
                $token
            );
        }

       
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "mensagem" => "Se o e-mail estiver cadastrado, você receberá instruções para redefinir sua senha."
        ]);
    }

    private function enviarEmailRecuperacao(string $email, string $nome, string $token): bool {
        
    $enviarEmailUsuario = new enviarEmail();

    return $enviarEmailUsuario->$this->enviarEmail($email, $nome, $token);
    }

    public function redefinirSenha(): void {
        $this->data;

        $token = $this->data['token'] ?? '';
        $senhaNova = trim($this->data['senha'] ?? '');

        if ($token === '') {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "mensagem" => "Token é obrigatório"
            ]);
            return;
        }

        if (mb_strlen($senhaNova) < 8) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "mensagem" => "A senha deve ter pelo menos 8 caracteres"
            ]);
            return;
        }       

        //Converte token recebido para hash
        $tokenHash = hash('sha256', $token);

        $agora = date('Y-m-d H:i:s');

        $sql = "
            SELECT usuario_id 
            FROM senha_recuperacao
            WHERE token = :token
            AND usado = 0
            AND expira_em > :agora
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);   
        $stmt->execute([
            'token' => $tokenHash,
            'agora' => $agora
        ]);

        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$registro) {
            http_response_code(400);
            echo json_encode([
                "success" => false, 
                "mensagem" => "Token inválido ou expirado"
            ]);
            return;
        }

        //  Atualiza senha
        $senhaHash = password_hash($senhaNova, PASSWORD_DEFAULT);

        $stmtSenha = $this->db->prepare("
            UPDATE usuarios 
            SET senha_hash = :senha,
                updated_at = :updated_at
            WHERE id_usuario = :id
        ");

        $stmtSenha->execute([
            'senha' => $senhaHash,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $registro['usuario_id']
        ]);

        //  Marca token como usado
        $stmtToken = $this->db->prepare("
            UPDATE senha_recuperacao 
            SET usado = 1 
            WHERE token = :token
        ");

        $stmtToken->execute([
            'token' => $tokenHash
        ]);

        http_response_code(200);
        echo json_encode([
            "success" => true, 
            "mensagem" => "Senha redefinida com sucesso"
        ]);
    }
}