<?php
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/verificarEmail.php';
require_once __DIR__ . '/../base/BaseController.php';


class UsuarioController extends BaseController
{
    private Usuarios $usuarioModel;
    private PDO $conn;

    public function __construct(PDO $db)
    {
        parent::__construct();
        $this->usuarioModel = new Usuarios($db);
        $this->conn = $db;
    }
    //método que atualiza a foto de perfil do usuário
    public function atualizarFoto(): void {
    $this->requireAuth();

    $idUsuario = $this->user->data->id_usuario; // 🔥 corrigido

    $urlFoto = $this->data["url_foto"] ?? null;

    if(!$urlFoto){
        $this->error("A URL da foto não pode ser vazia", 400);
        return;
    }

    if(!filter_var($urlFoto, FILTER_VALIDATE_URL)){
        $this->error("A URL da foto é inválida", 400);
        return;
    }

    $atualizado = $this->usuarioModel->atualizarFoto($idUsuario, $urlFoto);

    if(!$atualizado){
        $this->error("Erro ao atualizar a foto de perfil", 500);
        return;
    }

    $this->success([
        "mensagem" => "Foto de perfil atualizada com sucesso",
        "foto_perfil" => $urlFoto
    ]);
}

    //função para editar os dados do usuário logado, como nome e email
    function editarUsuarioLogado(): void {
        $this->requireAuth();

        //pegando o id do usuário que vem no usuário autenticado
        $idUsuario = $this->user->data->id_usuario;
        
        //pegando o UUID do usuário autenticado
        $uuid = $this->user->data->UUID;

        //pegando o que vem do body
        $this->data;

        //instanciando a classe verificarEmail
        $validar = new verificarEmail($this->conn);


        //verificando se o e-mail existe e se já está em uso por outro usuário
        if(isset($this->data['email'])){
            if($validar->verificarEmailEmUso($this->data['email'], $this->uuid)){
                $this->error("O email ja esta em uso por outro usuario", 409);
                return;
            }
        }

        $atualizado = $this->usuarioModel->editarUsuario($idUsuario, $uuid, $this->data);

        if(!$atualizado){
            $this->error("Erro ao atualizar usuário", 500);
            return;
        }

       $this->success([
        "mensagem" => "Usuario atualizado com sucesso",
        "usuario" => [
            "id_usuario" => $idUsuario
        ]
       ]);

    }

    function deletarUsuario(): void {

    $this->requireAuth();
    // Recupera o usuário autenticado através do token

    // pegando o ID do usuário logado
    $idUsuario = $this->user->data->id_usuario;

    //excluindo o usuário do banco de dados
    $deletado = $this->usuarioModel->deletarUsuario($idUsuario);

    // Verifica se houve falha na exclusão
    if (!$deletado) {
        $this->error("Erro ao deletar usuário", 500);
        return;
    }

    // Retorna sucesso na operação
   $this->success([
    "mensagem" => "Usuário deletado com sucesso"
   ]);
}

    function listarUsuario(): void {

    $this->requireAuth();
    // Recupera o usuário autenticado através do token

    // pegando o UUID do usuário logado 
    $uuid = $this->user->data->UUID;

    // Busca os dados do usuário no banco
    $dadosUsuario = $this->usuarioModel->listarUsuario($uuid);

    // Verifica se o usuário foi encontrado
    if (!$dadosUsuario) {
        $this->error("Usuário não encontrado", 404);
        return;
    }

    // Retorna os dados do usuário
    http_response_code(200);
    $this->success([
       "mensagem" => "Usuário encontrado",
       "usuario" => $dadosUsuario
    ]);
}

}