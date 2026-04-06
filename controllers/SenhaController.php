<?php

require_once __DIR__ . '../../DTO/recuperarSenhaDTO.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../utils/enviarEmail.php'; 
require_once __DIR__ . '/../models/Senha.php';
require_once __DIR__ . '/../base/BaseController.php';

class SenhaController extends BaseController{
    private Usuarios $usuarioModel;
    private Senha $senhaModel;
    private PDO $db;
    private enviarEmail $enviarEmail;

    public function __construct(PDO $db){
        parent::__construct();
        $this->usuarioModel = new Usuarios($db);
        $this->senhaModel = new Senha($db);
        $this->db = $db;
        $this->enviarEmail = new enviarEmail();
    }

    public function solicitarRecuperacao(): void {


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

        if($usuario) { 
        $token = $this->senhaModel->gerarTokenSenha($usuario['id_usuario']);

        if(!$token){
          $this->error("Erro ao gerar token", 500);
          exit;
        }
        
            //  Envia email com token 
            $enviado = $this->enviarEmailRecuperacao(
                $emailUsuario,
                $usuario['nome'],
                $token
            );
        
}
       
        if(!$enviado){
            $this->error("Erro ao enviar e-mail", 500);
            exit;
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

        

        http_response_code(200);
        echo json_encode([
            "success" => true, 
            "mensagem" => "Senha redefinida com sucesso"
        ]);
    }
}