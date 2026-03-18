<?php
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Livro.php';
require_once __DIR__ . '/../utils/jwt.php';

class AuthController
{

    private Usuarios $usuarioModel;
    private Livro $livroModel;
    private JwtHandler $jwtHandler;

    public function __construct(PDO $db)
    {
        $this->usuarioModel = new Usuarios($db);
        $this->livroModel = new Livro($db);
        $this->jwtHandler = new JwtHandler();
    }

    public function register(): void
    {

        $data = json_decode(file_get_contents("php://input"), true);

        $nome = trim($data["nome"] ?? "");
        $email = trim($data["email"] ?? "");
        $senha = trim($data["senha"] ?? "");

        if (!$nome || !$email || !$senha) {
            http_response_code(400);
            echo json_encode(["mensagem" => "Nome, email e senha são obrigatórios"]);
            return;
        }


        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([ "Sucess" => "false",
                "Mensagem" => "e-mail invalido"]);
            return;
        }
        if(strlen($senha) < 8){
            http_response_code(400);
            echo json_encode(["Sucess" => "false",
                "Mensagem" => "A senha deve ter pelo menos 8 caracteres"]);
            return;

        } elseif (!preg_match('/[A-Z]/', $senha)) {
            http_response_code(400);
            echo json_encode(["Sucess" => "false",
                "Mensagem" => "A senha deve conter pelo menos uma letra maiúscula"]);
            return;
        } elseif (!preg_match('/[a-z]/', $senha)) {
            http_response_code(400);
            echo json_encode(["Sucess" => "false",
                "Mensagem" => "A senha deve conter pelo menos uma letra minúscula"]);
            return;
        } elseif (!preg_match('/[0-9]/', $senha)) {
            http_response_code(400);
            echo json_encode(["Sucess" => "false",
                "Mensagem" => "A senha deve conter pelo menos um numero"]);
        } elseif (!preg_match('/[@#$%&*!+?=\-\[\]\{\}\|]/', $senha)) {
            http_response_code(400);
            echo json_encode(["Sucess" => "false",
                "Mensagem" => "A senha deve conter pelo menos um caractere especial"]);
        }

        $usuarioExistente = $this->usuarioModel->buscarPorEmail($email);

        if ($usuarioExistente) {
            http_response_code(409);
            echo json_encode(["Sucess" => "false",
                "mensagem" => "Email já cadastrado"]);
            return;
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $criado = $this->usuarioModel->criar($nome, $email, $senhaHash);

        if ($criado) {
            http_response_code(201);
            echo json_encode(["sucess" => true,
            "mensagem" => "Usuário criado com sucesso"]);
            return;
        }

        http_response_code(500);
        echo json_encode(["sucess" => "false",
            "mensagem" => "Erro ao cadastrar usuário"]);
    }

    public function login(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $email = trim($data["email"] ?? "");
        $senha = trim($data["senha"] ?? "");

        if (!$email || !$senha) {
            http_response_code(401);
            echo json_encode(["mensagem" => "Credenciais inválidas"]);
            return;
        }

        $usuario = $this->usuarioModel->buscarPorEmail($email);

        if (!$usuario || !password_verify($senha, $usuario["senha_hash"])) {
            http_response_code(401);
            echo json_encode(["mensagem" => "Senha não confere"]);
            return;
        }

        $token = $this->jwtHandler->gerarToken($usuario);

        http_response_code(200);
        echo json_encode([
            "mensagem" => "Login realizado com sucesso",
            "token" => $token
        ]);
    }
    public function perfil()
    {
        $usuario = AuthMiddleware::autenticar();

        $idUsuario = $usuario->data->id_usuario;

        echo json_encode([
            "mensagem" => "Perfil acessado com sucesso",
            "id_usuario" => $idUsuario,
            "usuario" => $usuario->data
        ]);
    }

    public function criarLivro(): void
    {
        $usuario = AuthMiddleware::autenticar();
        $idUsuario = $usuario->data->id_usuario;

        $data = json_decode(file_get_contents("php://input"), true);
        $titulo = trim($data["titulo"] ?? "");

        $autor = trim($data["autor"] ?? "");
        $ano = (int) ($data["ano"] ?? 0);

        if (!$titulo || !$autor || !$ano) {
            http_response_code(400);
            echo json_encode(["mensagem" => "Titulo, autor e ano são obrigatórios"]);
            return;
        }

        $livro = $this->livroModel->criarLivro($titulo, $autor, $ano, $idUsuario);

        echo json_encode([
            "menagem" => "Dados recebidos com sucesso",
            "id_usuario_logado" => $idUsuario,
            "titulo" => $titulo,
            "autor" => $autor,
            "ano" => $ano
        ]);
    }
}
