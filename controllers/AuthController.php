<?php
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Livro.php';
require_once __DIR__ . '/../utils/jwt.php';
require_once __DIR__ . '/../DTO/RegisterUserDTO.php';
require_once __DIR__ . '/../DTO/LoginDTO.php';
require_once __DIR__ . '/../base/BaseController.php';


class AuthController extends BaseController
{

    private Usuarios $usuarioModel;
    private JwtHandler $jwtHandler;

    public function __construct(PDO $db)
    {
        parent::__construct(false);
        $this->usuarioModel = new Usuarios($db);
        $this->jwtHandler = new JwtHandler();
    }

    public function register(): void
    {
        try { 
        $dto = new RegisterUserDTO($this->data);

        $usuarioExistente = $this->usuarioModel->buscarPorEmail($dto->email);

        if ($usuarioExistente) {
            $this->error("Email já cadastrado", 400);
            return;
        }

        $uuid = $this->gerarUUIDUsuario();

        $criado = $this->usuarioModel->criar(
            $dto->nome,
            $dto->email,
            $dto->senha_hash,
            $uuid);

        if(!$criado){
            $this->error("Erro ao cadastrar usuário", 500);
        }

            $this->success([
                "mensagem" => "Usuário registrado com sucesso",
            ], 201);
    
        

    } catch(Exception $e){
        $this->error("Erro ao cadastrar usuário", 400);
        echo json_encode(["success" => false,
        "mensagem" => $e->getMessage()]);
        
    }
} 

    public function login(): void
    {

        try{
        $dto = new LoginDTO($this->data);

        $usuario = $this->usuarioModel->buscarPorEmail($dto->email);

        if (!$usuario || !password_verify($dto->senha, $usuario["senha_hash"])) {
            $this->error("Email ou senha inválidos", 401);
            return; 
        }

        $accessToken = $this->jwtHandler->gerarToken($usuario);

        $this->success([
            "mensagem" => "Login realizado com sucesso",
            "access_token" => $accessToken,
            "UUID" => $usuario["UUID"],
            "nome" => $usuario["nome"],
            "email" => $usuario["email"],
            "foto_perfil" => $usuario["foto_perfil"] ?? null
        ], 200);

    } catch(Exception $e){
        $this->error($e->getMessage(), 400);
    }
}

    public function perfil()
    {
       $this->requireAuth();

       $usuario = AuthMiddleware::autenticar();

        if(($usuario->type ?? null) !== "access"){
            $this->error("Token inválido para acesso", 401);
            return;
        
        }
        $this->success([
            "success" => true,
            "mensagem" => "Perfil acessado com sucesso",
            "id_usuario" => $usuario->data->id_usuario,
            "usuario" => $usuario->data
        ]);
    }

    private function gerarUUIDUsuario(int $tamanho = 30): string {
        return substr(bin2hex(random_bytes(20)), 0, $tamanho);
    }
}
