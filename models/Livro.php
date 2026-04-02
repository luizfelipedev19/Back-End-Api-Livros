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
    ?string $anotacoes = null,
): int|false {

     $created_at = date('Y-m-d H:i:s');

    $query = "INSERT INTO {$this->table}
        (titulo, autor, ano, usuario_id, genero, status, avaliacao, anotacoes, created_at)
        VALUES (
            :titulo,
            :autor,
            :ano,
            (SELECT id_usuario FROM usuarios WHERE UUID = :uuid),
            :genero,
            :status,
            :avaliacao,
            :anotacoes,
            :created_at
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
    $stmt->bindValue(":created_at",  $created_at);

    $executado = $stmt->execute();

    if (!$executado) {
        return false;
    }

    return (int) $this->conn->lastInsertId();
}

    public function listarPorUsuario(string $uuid): array {
        $query = "SELECT id_livro, titulo, autor, ano
                  FROM {$this->table}
                  WHERE usuario_id = (SELECT id_usuario from usuarios where UUID = :uuid)
                  ORDER BY id_livro DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":uuid", $uuid, PDO::PARAM_STR);
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
        $updated_at = date('Y-m-d H:i:s');

        $query = "UPDATE {$this->table} SET
            titulo = :titulo,
            autor = :autor,
            ano = :ano,
            genero = :genero,
            status = :status,
            avaliacao = :avaliacao,
            anotacoes = :anotacoes,
            updated_at = :updated_at
        WHERE id_livro = :idLivro
        AND usuario_id = (SELECT id_usuario FROM usuarios WHERE UUID = :uuid)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(":titulo", $titulo);
        $stmt->bindValue(":autor", $autor);
        $stmt->bindValue(":ano", $ano, PDO::PARAM_INT);
        $stmt->bindValue(":genero", $genero);
        $stmt->bindValue(":status", $status);

    
        if ($avaliacao === null) {
            $stmt->bindValue(":avaliacao", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":avaliacao", $avaliacao, PDO::PARAM_INT);
        }

        $stmt->bindValue(":anotacoes", $anotacoes);
        $stmt->bindValue(":idLivro", $idLivro, PDO::PARAM_INT);
        $stmt->bindValue(":uuid", $uuid, PDO::PARAM_STR);
        $stmt->bindValue(":updated_at", $updated_at);

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
                ($key === ':uuid' || $key === ':ano') ? PDO::PARAM_STR : PDO::PARAM_INT
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

    public function encontrarLivro($data, string $uuid): ?array {
        $where = "WHERE usuario_id = (Select id_usuario from usuarios where UUID = :uuid)";
        $params = [':uuid' => $uuid];

        // Normalize incoming $data: accept JSON string, object or array
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            }
        }

        if (is_object($data)) {
            $data = (array)$data;
        }

        // If decoded/received array is associative (single filter object), wrap it into a list
        if (is_array($data)) {
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);
            if ($isAssoc) {
                $data = [$data];
            }
        }

        if (is_array($data) && !empty($data)) {
            $allowed = ['id_livro', 'titulo', 'autor', 'ano', 'genero', 'status', 'avaliacao', 'anotacoes'];
            $conds = [];
            $i = 0;

            
            //tarefa para semana que vem, adicionar a paginação dentro desse endpoint.
            foreach ($data as $item) {
                $entry = is_object($item) ? (array)$item : (array)$item;
               
                foreach ($entry as $key => $value) {
                    $key = trim((string)$key);
                    if ($value === null || $value === '') {
                        continue;
                    }
                    if (!in_array($key, $allowed, true)) {
                        continue;
                    }

                    $param = ":p{$i}";
                    if ($key === 'ano' || $key === 'avaliacao') {
                        // numeric fields use exact match
                        $conds[] = "{$key} = {$param}";
                        $params[$param] = (int) $value;
                    } else {
                        // use LIKE for string fields
                        $conds[] = "{$key} LIKE {$param}";
                        $params[$param] = '%' . $value . '%';

                        //Entre as duas porcentagens, ele faz uma busca completa. Se mandar livro = ceu, ele vai encontrar "O céu é azul", "Céu estrelado", "Céu e inferno", etc. Se mandar livro = ceu%, ele vai encontrar "Céu é azul", "Céu estrelado", mas não "O céu é azul". Se mandar livro = %ceu, ele vai encontrar "O céu é azul", mas não "Céu estrelado". Se mandar livro = ceu, ele vai encontrar apenas "Céu".
                    }
                    $i++;
                }
            }

            if (!empty($conds)) {
                $where .= ' AND ' . implode(' AND ', $conds);
            }
        }

        $query = "SELECT id_livro, titulo, autor, ano, genero, status, avaliacao, anotacoes
                  FROM {$this->table}
                  {$where}
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $val) {
            if ($val === null) {
                $stmt->bindValue($key, null, PDO::PARAM_NULL);
                continue;
            }

            if (is_int($val)) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
                continue;
            }

            // If parameter name indicates numeric but value came as string, try cast when numeric
            if ((($key === ':ano' || $key === ':avaliacao') || strpos($key, ':p') === 0) && is_numeric($val)) {
                $stmt->bindValue($key, (int) $val, PDO::PARAM_INT);
                continue;
            }

            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $livro = $stmt->execute();
        $livro = $stmt->fetch(PDO::FETCH_ASSOC);
        $livro = $livro ?: [];
        return $livro;
    }
// ...existing code...
}