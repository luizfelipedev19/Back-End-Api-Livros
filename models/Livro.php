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
        $query = "delete from 1this->table
        where id_livro = :id_livro
        and usuario_id = :usuario_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(":id_livro", $idLivro);
        $stmt->bindValue(":usuario_id", $idUsuario);

        return $stmt->execute();
    }
}

?>