<?php
class Usuarios
{
    private PDO $conn;
    private string $table = "usuarios";

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $query = "select id_usuario, nome, email, senha_hash from {$this->table} where email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":email", $email);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    public function criar(string $nome, string $email, string $senhaHash): bool
    {
        $query = "insert into {$this->table} (nome, email, senha_hash) values (:nome, :email, :senha_hash)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(":nome", $nome);
        $stmt->bindValue(":email", $email);
        $stmt->bindValue(":senha_hash", $senhaHash);

        return $stmt->execute();
    }
}
