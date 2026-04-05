<?php

require_once __DIR__ . '/../models/Livro.php';
require_once __DIR__ . '/../DTO/CreateLivroDTO.php';
require_once __DIR__ . '/../DTO/UpdateLivroDTO.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../base/BaseController.php';

class LivroController extends BaseController{ 

    private Livro $livroModel;

     public function __construct(PDO $db){
        parent::__construct();
        $this->livroModel = new Livro($db);
    }

    public function criarLivro(): void {
    $this->requireAuth();

        try {
            $dto = new CreateLivroDTO($this->data);
        } catch (Exception $e) {
           $this->error($e->getMessage(), 400);
        }

        $idLivro = $this->livroModel->criarLivro(
            $dto->titulo,
            $dto->autor,
            $dto->ano,
            $this->uuid,
            $dto->genero,
            $dto->status,
            $dto->avaliacao,
            $dto->anotacoes
        );

        if (!$idLivro) {
            $this->error("Erro ao criar livro", 500);
        }

        $this->success([
            "mensagem" => "Livro criado com sucesso",
            "id" => $idLivro
        ], 201);

    }

    public function atualizarLivro(): void {
        $this->requireAuth();
        $idLivro = $this->data['id_livro'] ?? null;

        if(!$idLivro){
            $this->error("Id do livro é obrigatório", 400);
            return;
        }

        $livroAtual = $this->livroModel->buscarPorId((int) $idLivro, $this->uuid);

        if(!$livroAtual){
            $this->error("Livro não encontrado", 404);
            return;
        }
        try {
            $dto = new UpdateLivroDTO($this->data);
        } catch (Exception $e){
            $this->error($e->getMessage());
            return;
        }

        $atualizado = $this->livroModel->atualizarLivro(
        (int) $idLivro,
        $dto->titulo ?? $livroAtual['titulo'],
        $dto->autor ?? $livroAtual['autor'],
        $dto->ano ?? ((int) ($livroAtual['ano'] ?? 0)),
        $this->uuid,
        $dto->genero ?? ($livroAtual['genero'] ?? null),
        $dto->status ?? ($livroAtual['status'] ?? 'quero_ler'),
        $dto->avaliacao ?? ($livroAtual['avaliacao'] ?? null),
        $dto->anotacoes ?? ($livroAtual['anotacoes'] ?? null)
);

        if(!$atualizado){
            $this->error("Erro ao atualizar livro", 500);
            return;
        }

        $this->success([
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
        $this->requireAuth();
        //pegar o id do livro pelo body
        $this->data;
        $idLivro = $this->data['id_livro'] ?? null;

        if(!$idLivro) {
            $this->error("Id do livro é obrigatório", 400);
        }

        $deletado = $this->livroModel->deletarLivro((int) $idLivro, $this->uuid);

        if(!$deletado){
            $this->error("Livro não encontrado", 404);
        }

        http_response_code(204);
    }
/*
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
*/
    public function listarLivros(): void {
        $this->requireAuth();

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 10;
        if ($limit > 100) $limit = 100;

        $livroEncontrado = $this->livroModel->encontrarLivro($this->data, $this->uuid);
        $livroCount = count($livroEncontrado);


        if($livroCount === 0){
            $this->error("Nenhum livro encontrado", 404);
            return;
        }

        $this->success([
            "detail" => [
                "livros" => [
                    ['titulo' => $livroEncontrado['titulo'] ?? '', 'autor' => $livroEncontrado['autor'] ?? '', 'ano' => $livroEncontrado['ano'] ?? null,
                    'genero' => $livroEncontrado['genero'] ?? '', 'status' => $livroEncontrado['status'] ?? '', 'avaliacao' => $livroEncontrado['avaliacao'] ?? null, 'anotacoes' => $livroEncontrado['anotacoes'] ?? ''],
                ],
                'paginacao' => [
                    'page'        => $page,
                    'limit'       => $limit,
                    'total'       => $livroCount,
                    'total_pages' => ceil($livroCount / $limit)
                ],
            ]
        ]);
    }
}