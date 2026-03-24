<?php
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Livro.php';
require_once __DIR__ . '/../utils/jwt.php';
require_once __DIR__ . '/../DTO/RegisterUserDTO.php';
require_once __DIR__ . '/../DTO/LoginDTO.php';

class AuthController
{

    private Usuarios $usuarioModel;
    private JwtHandler $jwtHandler;

    public function __construct(PDO $db)
    {
        $this->usuarioModel = new Usuarios($db);
        $this->jwtHandler = new JwtHandler();
    }

    public function register(): void
    {
        try { 
        $data = json_decode(file_get_contents("php://input"), true);

        $dto = new RegisterUserDTO($data);

        $usuarioExistente = $this->usuarioModel->buscarPorEmail($dto->email);

        if ($usuarioExistente) {
            http_response_code(409);
            echo json_encode(["Sucess" => "false",
                "mensagem" => "Email já cadastrado"]);
            return;
        }

        $uuid = $this->gerarUUIDUsuario();

        $criado = $this->usuarioModel->criar(
            $dto->nome,
            $dto->email,
            $dto->senha_hash,
            $uuid);

        if ($criado) {
            http_response_code(201);
            echo json_encode(["sucess" => true,
            "mensagem" => "Usuário criado com sucesso",
            "uuid" => $uuid
            ]);
            return;
        }

        http_response_code(500);
        echo json_encode(["sucess" => "false",
            "mensagem" => "Erro ao cadastrar usuário"]);

    } catch(Exception $e){
        http_response_code(400);
        echo json_encode(["sucess" => false,
        "mensagem" => $e->getMessage()]);
    }
} 

    private function gerarUUIDUsuario(int $tamanho = 30): string {
        return substr(bin2hex(random_bytes(20)), 0, $tamanho);
    }



    public function login(): void
    {

        try{
        $data = json_decode(file_get_contents("php://input"), true);

        $dto = new LoginDTO($data);

        $usuario = $this->usuarioModel->buscarPorEmail($dto->email);

        if (!$usuario || !password_verify($dto->senha, $usuario["senha_hash"])) {
            http_response_code(401);
            echo json_encode(["sucess" => false,
             "mensagem" => "Email ou senha inválidos"]);
            return;
        }

        $accessToken = $this->jwtHandler->gerarToken($usuario);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "mensagem" => "Login realizado com sucesso",
            "access_token" => $accessToken,
            "UUID" => $usuario["UUID"],
            "nome" => $usuario["nome"],
            "email" => $usuario["email"],
            "foto_perfil" => $usuario["foto_perfil"] ?? null
        ]);

    } catch(Exception $e){
        http_response_code(400);
        echo json_encode([
            "sucess" => false,
            "mensagem" => $e->getMessage()
        ]);
    }
}

    public function perfil()
    {
        $usuario = AuthMiddleware::autenticar();

        if(($usuario->type ?? null) !== "access"){
            http_response_code(401);
            echo json_encode([
                "success" => false, 
                "mensagem" => "Token inválido para acesso"
            ]);
            return;
        }

        $idUsuario = $usuario->data->id_usuario;

        echo json_encode([
            "mensagem" => "Perfil acessado com sucesso",
            "id_usuario" => $idUsuario,
            "usuario" => $usuario->data
        ]);
    }
}
