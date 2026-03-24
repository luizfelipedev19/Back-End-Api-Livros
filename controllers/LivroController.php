<?php

require_once __DIR__ . '/../models/Livro.php';
require_once __DIR__ . '/../DTO/CreateLivroDTO.php';
require_once __DIR__ . '/../DTO/UpdateLivroDTO.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class LivroController { 

    private Livro $livroModel;

    public function __construct(PDO $db){
        $this->livroModel = new Livro($db);
    }

    public function criarLivro(): void {
        $usuario = AuthMiddleware::autenticar();
        $idUsuario = $usuario->data->id_usuario;

        $data = json_decode(file_get_contents("php://input"), true) ?? [];

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

        $criado = $this->livroModel->criarLivro(
            $dto->titulo,
            $dto->autor,
            $dto->ano,
            $idUsuario,
            $dto->genero,
            $dto->status,
            $dto->avaliacao,
            $dto->anotacoes
        );

        if (!$criado) {
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
            "livro" => [
                "titulo"    => $dto->titulo,
                "autor"     => $dto->autor,
                "ano"       => $dto->ano,
                "genero"    => $dto->genero,
                "status"    => $dto->status,
                "avaliacao" => $dto->avaliacao,
                "anotacoes" => $dto->anotacoes
            ]
        ]);
    }

    public function atualizarLivro(): void {
        $usuario = AuthMiddleware::autenticar();
        $idUsuario = $usuario->data->id_usuario;
        $idLivro = $_GET['id'] ?? null;

        if(!$idLivro){
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "mensagem" => "Id do livro é obrigatório"
            ]);
            return;
        }

        $livroAtual = $this->livroModel->buscarPorId((int) $idLivro, $idUsuario);

        if(!$livroAtual){
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "mensagem" => "Livro não encontrado"
            ]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        
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
            $dto->titulo,
            $dto->autor,
            $dto->ano,
            (int) $idUsuario,
            $dto->genero,
            $dto->status,
            $dto->avaliacao,
            $dto->anotacoes
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
        ]);
    }

    public function deletarLivro(): void {
        $usuario = AuthMiddleware::autenticar();
        $idUsuario = $usuario->data->id_usuario;
        
        $idLivro = $_GET['id'] ?? null;

        if(!$idLivro) {
            http_response_code(400);
            echo json_encode(["mensagem" => "Id do livro é obrigatório"]);
            return;
        }

        $deletado = $this->livroModel->deletarLivro((int) $idLivro, $idUsuario);

        if(!$deletado){
            http_response_code(404);
            echo json_encode(["mensagem" => "Livro não encontrado"]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "mensagem" => "Livro deletado com sucesso"
        ]);
    }

    public function listarLivros(): void {
        $usuario = AuthMiddleware::autenticar();
        $idUsuario = $usuario->data->id_usuario;

        $titulo = trim($_GET['titulo'] ?? '');
        $autor  = trim($_GET['autor'] ?? '');
        $ano    = isset($_GET['ano']) && $_GET['ano'] !== '' ? (int) $_GET['ano'] : null;
        $page   = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit  = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $sort   = $_GET['sort'] ?? 'id_livro';
        $order  = strtolower($_GET['order'] ?? 'asc');

        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 10;
        if ($limit > 100) $limit = 100;

        $allowedSort = ['id_livro', 'titulo', 'autor', 'ano'];
        if (!in_array($sort, $allowedSort, true)) $sort = 'id_livro';
        if (!in_array($order, ['asc', 'desc'], true)) $order = 'asc';

        $resultado = $this->livroModel->listarComFiltros(
            $idUsuario,
            $titulo,
            $autor,
            $ano,
            $page,
            $limit,
            $sort,
            $order
        );

        http_response_code(200);
        echo json_encode([
            'success'   => true,
            'filtros'   => ['titulo' => $titulo, 'autor' => $autor, 'ano' => $ano],
            'paginacao' => [
                'page'        => $resultado['page'],
                'limit'       => $resultado['limit'],
                'total'       => $resultado['total'],
                'total_pages' => $resultado['total_pages']
            ],
            'ordenacao' => ['sort' => $sort, 'order' => $order],
            'livros'    => $resultado['items']
        ]);
    }
}