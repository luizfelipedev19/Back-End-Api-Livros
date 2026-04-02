<?php

require_once __DIR__ . '/../models/Livro.php';
require_once __DIR__ . '/../DTO/CreateLivroDTO.php';
require_once __DIR__ . '/../DTO/UpdateLivroDTO.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class LivroController { 

    private Livro $livroModel;

    public function __construct(PDO $db){
        $this->livroModel = new Livro($db);

        //this->data
    }

    public function criarLivro(): void {
        $usuario = AuthMiddleware::autenticar();
        $uuid = $usuario->data->UUID;
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        if(!$uuid){
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "mensagem" => "UUID do usuário não encontrado"
            ]);
            return;
        }

        try {
            $dto = new CreateLivroDTO($data);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "mensagem" => $e->getMessage()
            ]);
            return;
        }

        $idLivro = $this->livroModel->criarLivro(
            $dto->titulo,
            $dto->autor,
            $dto->ano,
            $uuid,
            $dto->genero,
            $dto->status,
            $dto->avaliacao,
            $dto->anotacoes
        );

        if (!$idLivro) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "mensagem" => "Erro ao criar livro"
            ]);
            return;
        }

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "mensagem" => "Livro criado com sucesso",
            "detail" => [
                "id:" => $idLivro,
            ]
        ]);
    }

    public function atualizarLivro(): void {
        $usuario = AuthMiddleware::autenticar();
        $uuid = $usuario->data->UUID ?? null;
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        $idLivro = $data['id_livro'] ?? null;

        if(!$uuid){
            http_response_code(401);
            echo json_encode([
                "success" => false, 
                "mensagem" => "UUID do usuário não encontrado"
            ]);
            return;
        }

        if(!$idLivro){
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "mensagem" => "Id do livro é obrigatório"
            ]);
            return;
        }

        $livroAtual = $this->livroModel->buscarPorId((int) $idLivro, $uuid);

        if(!$livroAtual){
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "mensagem" => "Livro não encontrado"
            ]);
            return;
        }

        
        
        try {
            $dto = new UpdateLivroDTO($data);
        } catch (Exception $e){
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "mensagem" => $e->getMessage()
            ]);
            return;
        }

        $atualizado = $this->livroModel->atualizarLivro(
        (int) $idLivro,
        $dto->titulo ?? $livroAtual['titulo'],
        $dto->autor ?? $livroAtual['autor'],
        $dto->ano ?? $livroAtual['ano'],
        $uuid,
        $dto->genero ?? ($livroAtual['genero'] ?? null),
        $dto->status ?? ($livroAtual['status'] ?? 'quero_ler'),
        $dto->avaliacao ?? ($livroAtual['avaliacao'] ?? null),
        $dto->anotacoes ?? ($livroAtual['anotacoes'] ?? null)
);

        if(!$atualizado){
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "mensagem" => "Erro ao atualizar livro"
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "mensagem" => "Livro atualizado com sucesso",
            "detail" => [
                "livro" => [
                "id"        => (int) $idLivro,
                "titulo"    => $dto->titulo ?? $livroAtual['titulo'],
                "autor"     => $dto->autor ?? $livroAtual['autor'],
                "ano"       => $dto->ano ?? $livroAtual['ano'],
                "genero"    => $dto->genero ?? $livroAtual['genero'],
                "status"    => $dto->status ?? $livroAtual['status'],
                "avaliacao" => $dto->avaliacao ?? $livroAtual['avaliacao'],
                "anotacoes" => $dto->anotacoes ?? $livroAtual['anotacoes']
            ]
            ]
        ]);
    }

    public function deletarLivro(): void {
        $usuario = AuthMiddleware::autenticar();
        $uuid = $usuario->data->UUID ?? null;

        
        //pegar o id do livro pelo body
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        $idLivro = $data['id_livro'] ?? null;

        if(!$idLivro) {
            http_response_code(400);
            echo json_encode(["mensagem" => "Id do livro é obrigatório"]);
            return;
        }

        $deletado = $this->livroModel->deletarLivro((int) $idLivro, $uuid);

        if(!$deletado){
            http_response_code(404);
            echo json_encode(["mensagem" => "Livro não encontrado"]);
            return;
        }

        http_response_code(204);
        echo json_encode([
            "success" => true,
            "mensagem" => "Livro deletado com sucesso",
            "detail:" => [

            ]
        ]);
    }

    public function listarLivros(): void {
        $usuario = AuthMiddleware::autenticar();
        $uuid = $usuario->data->UUID ?? null;

        $titulo = trim($_GET['titulo'] ?? '');
        $autor  = trim($_GET['autor'] ?? '');
        $ano    = isset($_GET['ano']) && $_GET['ano'] !== '' ? (int) $_GET['ano'] : null;
        $genero = trim($_GET['genero'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $avaliacao = isset($_GET['avaliacao']) && $_GET['avaliacao'] !== '' ? (int) $_GET['avaliacao'] : null;
        $anotacoes = trim($_GET['anotacoes'] ?? '');

        $page   = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit  = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $sort   = $_GET['sort'] ?? 'id_livro';
        $order  = strtolower($_GET['order'] ?? 'asc');

        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 10;
        if ($limit > 100) $limit = 100;

        $allowedSort = ['id_livro', 'titulo', 'autor', 'ano', 'genero', 'status', 'avaliacao', 'anotacoes'];

        // Valida os parâmetros de ordenação garantindo valores permitidos e evitando entradas inválidas ou inseguras
        if (!in_array($sort, $allowedSort, true)) $sort = 'id_livro';
        if (!in_array($order, ['asc', 'desc'], true)) $order = 'asc';

        $resultado = $this->livroModel->listarComFiltros(
            $uuid,
            $titulo,
            $autor,
            $ano,
            $genero,
            $status,
            $avaliacao,
            $anotacoes,
            $page,
            $limit,
            $sort,
            $order
        );

        http_response_code(200);
        echo json_encode([
            'success'   => true,
            'detail' => [
              "livros" => [ ['titulo' => $titulo, 'autor' => $autor, 'ano' => $ano,
              'genero' => $genero, 'status' => $status, 'avaliacao' => $avaliacao, 'anotacoes' => $anotacoes],
              ],
            'paginacao' => [
                'page'        => $resultado['page'],
                'limit'       => $resultado['limit'],
                'total'       => $resultado['total'],
                'total_pages' => $resultado['total_pages']
            ],
            'ordenacao' => ['sort' => $sort, 'order' => $order],
            'livros'    => $resultado['items']
            ]
        ]);
    }

    public function listarUmLivro(): void {
        $usuario = AuthMiddleware::autenticar();
        $uuid = $usuario->data->UUID ?? null;
        $data = json_decode(file_get_contents("php://input"), true) ?? [];

        if(!$uuid){
            http_response_code(400);
            echo json_encode([
                "success" => false, 
                "mensagem" => "UUID do usuário não encontrado"
            ]);
            return;
        }

        
    

        $livroEncontrado = $this->livroModel->encontrarLivro($data, $uuid);
        $livroCount = count($livroEncontrado);




        if($livroCount === 0){
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "mensagem" => "Livro não encontrado"
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            "success" => true, 
            "detail" => [
                "livro" => $livroEncontrado
            ]
        ]);

    }
}