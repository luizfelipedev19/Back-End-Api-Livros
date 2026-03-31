<?php

use PHPMailer\PHPMailer\SMTP;

class Livro {
    private PDO $conn;
    private string $table = "livros";

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function criarLivro(
    string $titulo,
    string $autor,
    int $ano,
    string $uuid,
    ?string $genero = null,
    string $status = 'quero_ler',
    ?int $avaliacao = null,
    ?string $anotacoes = null
): int|false {

    $query = "INSERT INTO {$this->table}
        (titulo, autor, ano, usuario_id, genero, status, avaliacao, anotacoes)
        VALUES (
            :titulo,
            :autor,
            :ano,
            (SELECT id_usuario FROM usuarios WHERE UUID = :uuid),
            :genero,
            :status,
            :avaliacao,
            :anotacoes
        )";

    $stmt = $this->conn->prepare($query);

    $stmt->bindValue(":titulo", $titulo);
    $stmt->bindValue(":autor", $autor);
    $stmt->bindValue(":ano", $ano, PDO::PARAM_INT);
    $stmt->bindValue(":uuid", $uuid, PDO::PARAM_STR);
    $stmt->bindValue(":genero", $genero);
    $stmt->bindValue(":status", $status);

    if ($avaliacao === null) {
        $stmt->bindValue(":avaliacao", null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(":avaliacao", $avaliacao, PDO::PARAM_INT);
    }

    $stmt->bindValue(":anotacoes", $anotacoes);

    $executado = $stmt->execute();

    if (!$executado) {
        return false;
    }

    return (int) $this->conn->lastInsertId();
}

    public function listarPorUsuario(int $idUsuario): array {
        $query = "SELECT id_livro, titulo, autor, ano
                  FROM {$this->table}
                  WHERE usuario_id = :usuario_id
                  ORDER BY id_livro DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":usuario_id", $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $idLivro, string $uuid): ?array {
    $query = "SELECT id_livro, titulo, autor, ano, genero, status, avaliacao, anotacoes
              FROM {$this->table}
              WHERE id_livro = :id_livro
              AND usuario_id = (
                  SELECT id_usuario
                  FROM usuarios
                  WHERE UUID = :uuid
              )
              LIMIT 1";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindValue(":id_livro", $idLivro, PDO::PARAM_INT);
    $stmt->bindValue(":uuid", $uuid, PDO::PARAM_STR);
    $stmt->execute();

    $livro = $stmt->fetch(PDO::FETCH_ASSOC);
    return $livro ?: null;
}

    public function atualizarLivro(
        int $idLivro,
        string $titulo,
        string $autor,
        int $ano,
        string $uuid,
        ?string $genero = null,
        string $status = 'quero_ler',
        ?int $avaliacao = null,
        ?string $anotacoes = null
    ): bool {
        $query = "UPDATE {$this->table} SET
            titulo = :titulo,
            autor = :autor,
            ano = :ano,
            genero = :genero,
            status = :status,
            avaliacao = :avaliacao,
            anotacoes = :anotacoes
        WHERE id_livro = :idLivro
        AND usuario_id = (SELECT id_usuario FROM usuarios WHERE UUID = :uuid)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(":titulo", $titulo);
        $stmt->bindValue(":autor", $autor);
        $stmt->bindValue(":ano", $ano, PDO::PARAM_INT);
        $stmt->bindValue(":genero", $genero);
        $stmt->bindValue(":status", $status);

        // CORREÇÃO PRINCIPAL
        if ($avaliacao === null) {
            $stmt->bindValue(":avaliacao", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":avaliacao", $avaliacao, PDO::PARAM_INT);
        }

        $stmt->bindValue(":anotacoes", $anotacoes);
        $stmt->bindValue(":idLivro", $idLivro, PDO::PARAM_INT);
        $stmt->bindValue(":uuid", $uuid, PDO::PARAM_STR);

        $stmt->execute();
        return $stmt->rowCount() > 0;


    }

    public function deletarLivro(int $idLivro, string $uuid): bool {
        $query = "DELETE FROM {$this->table}
                  WHERE id_livro = :id_livro
                  AND usuario_id = (select id_usuario from usuarios where UUID = :uuid)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(":id_livro", $idLivro, PDO::PARAM_INT);
        $stmt->bindValue(":uuid", $uuid, PDO::PARAM_STR);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function listarComFiltros(
        string $uuid,
        string $titulo = '',
        string $autor = '',
        ?int $ano = null,
        string $genero = '',
        ?string $status = '',
        ?int $avaliacao = null,
        ?string $anotacoes = null,
        int $page = 1,
        int $limit = 10,
        string $sort = 'id_livro',
        string $order = 'asc'
    ): array {

        $offset = ($page - 1) * $limit;

        $where = "WHERE usuario_id = (select id_usuario from usuarios where UUID = :uuid)";
        $params = [':uuid' => $uuid];

        if ($titulo !== '') {
            $where .= " AND titulo LIKE :titulo";
            $params[':titulo'] = '%' . $titulo . '%';
        }

        if ($autor !== '') {
            $where .= " AND autor LIKE :autor";
            $params[':autor'] = '%' . $autor . '%';
        }

        if ($ano !== null && $ano > 0) {
            $where .= " AND ano = :ano";
            $params[':ano'] = $ano;
        }

        if ($genero !== '') {
            $where .= " AND genero LIKE :genero";
            $params[':genero'] = '%' . $genero . '%';
        }
        if ($status !== '') {
            $where .= " AND status = :status";
            $params[':status'] = $status;
        }
        if ($avaliacao !== null && $avaliacao > 0) {
            $where .= " AND avaliacao = :avaliacao";
            $params[':avaliacao'] = $avaliacao;
        }
        if ($anotacoes !== null && $anotacoes !== '') {
            $where .= " AND anotacoes LIKE :anotacoes";
            $params[':anotacoes'] = '%' . $anotacoes . '%';
        }

        $sqlCount = "SELECT COUNT(*) AS total FROM {$this->table} {$where}";
        $stmtCount = $this->conn->prepare($sqlCount);

        foreach ($params as $key => $value) {
            $stmtCount->bindValue(
                $key,
                $value,
                ($key === ':uuid' || $key === ':ano') ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }

        $stmtCount->execute();
        $total = (int) $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        $sql = "SELECT id_livro, titulo, autor, ano, genero, status, avaliacao, anotacoes
                FROM {$this->table}
                {$where}
                ORDER BY {$sort} {$order}
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(
                $key,
                $value,
                ($key === ':id_usuario' || $key === ':ano') ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int) ceil($total / $limit)
        ];
    }

    public function encontrarLivro(int $idLivro, string $uuid): ?array {
        $query = "Select id_livro, titulo, autor, ano, genero, status, avaliacao, anotacoes
        FROM {$this->table}
        WHERE id_livro = :idLivro
        and usuario_id = (Select id_usuario from usuarios where UUID = :uuid)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":idLivro", $idLivro, PDO::PARAM_INT);
        $stmt->bindValue(":uuid", $uuid, PDO::PARAM_STR);
        $stmt->execute();

        $livro = $stmt->fetch(PDO::FETCH_ASSOC);
        return $livro ?: null;
    }
}