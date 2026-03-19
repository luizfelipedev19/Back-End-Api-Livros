<?php 

class Livro {
    private PDO $conn;
    private string $table = "livros";

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function criarLivro(string $titulo, string $autor, int $ano, int $idUsuario): bool {
        $query = "insert into {$this->table} (titulo, autor, ano, usuario_id) values (:titulo, :autor, :ano, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(":titulo", $titulo);
        $stmt->bindValue(":autor", $autor);
        $stmt->bindValue(":ano", $ano);
        $stmt->bindValue(":usuario_id", $idUsuario);

        return $stmt->execute();
    }

    public function listarPorUsuario(int $idUsuario): array{
        $query = "SELECT id_livro, titulo, autor, ano from {$this->table} where usuario_id = :usuario_id order by id_livro desc";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":usuario_id", $idUsuario);
        $stmt->execute();
    }

    public function buscarPorId(int $idLivro, int $idUsuario): ?array {
        $query = "SELECT id_livro, titulo, autor, ano from {$this->table} where id_livro = :id_livro and usuario_id = :usuario_id limit 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id_livro", $idLivro);
        $stmt->bindValue(":usuario_id", $idUsuario);
        $stmt->execute();

        $livro = $stmt->fetch(PDO::FETCH_ASSOC);

        return $livro ?: null;
    }

    public function atualizarLivro(int $idLivro, string $titulo, string $autor, int $ano, int $idUsuario): bool {
        $query = "update {$this->table} set titulo = :titulo,
        autor = :autor,
        ano = :ano,
        where id_livro = :id_livro
        and usuario_id = :usuario_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":titulo", $titulo);
        $stmt->bindValue(":autor", $autor);
        $stmt->bindValue(":ano", $ano);
        $stmt->bindValue(":id_livro", $idLivro);
        $stmt->bindValue(":usuario_id", $idUsuario);

        return $stmt->execute();
    }

    public function deletarLivro(int $idLivro, int $idUsuario): bool {
        $query = "delete from {$this->table}
        where id_livro = :id_livro
        and usuario_id = :usuario_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(":id_livro", $idLivro);
        $stmt->bindValue(":usuario_id", $idUsuario);

        return $stmt->execute();

    }

    public function listarComFiltros(
        int $idUsuario,
        string $titulo = '',
        string $autor = '',
        ?int $ano = null,
        int $page = 1,
        int $limit = 10,
        string $sort = 'id',
        string $order = 'asc'
    ): array {
        $offset = ($page -1) * $limit;

        $where = "Where id_usuario = :id_usuario ";
        $params = [
            ':id_usuario' => $idUsuario
        ];

        if ($titulo !== ''){
            $where .= " AND titulo like :titulo";
            $params[':titulo'] = '%' . $titulo . '%';
        }

        if ($autor !== ''){
            $where .= "AND autor like :autor";
            $params['autor'] = '%' . $autor . '%';
        }

        if($ano !== null && $ano > 0){
            $where .= "and ano = :ano";
            $params[':ano'] = '%' . $ano . '%';
        }

        $sqlCount = "SELECT COUNT(*) as total from {$this->table}" . $where;
        $stmtCount = $this->conn->prepare($sqlCount);

        foreach ($params as $key => $value){
            if($key === ':id_usuario' || $key === ':ano'){
                $stmtCount->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmtCount->bindValue($key, $value, PDO::PARAM_STR);
            }
        }

        $stmtCount->execute();
        $total = (int) $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        $sql = "Select id, titulo, autor, ano from {$this->table} {$where}
        order by {$sort} {$order} 
        LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value){
            if($key === ':id_usuario' || $key === ':ano'){
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'item' => $items, 
            'total' => $total, 
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int) ceil($total / $limit)
        ];

    }

}

?>