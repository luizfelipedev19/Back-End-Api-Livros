<?php

class Database
{
    private string $host;
    private string $port;
    private string $db_name;
    private string $username;
    private string $password;
    public ?PDO $conn = null;

    
    public function __construct(){
       $this->host = $_ENV["DB_HOST"];
       $this->port = $_ENV["DB_PORT"];
       $this->db_name = $_ENV["DB_NAME"];
       $this->username = $_ENV["DB_USER"];
       $this->password = $_ENV["DB_PASSWORD"];
    }

    public function getConnection(): ?PDO
    {

        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8",
                $this->username,
                $this->password,
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET time_zone = '-03:00'");

        } catch (PDOException $e) {
            echo json_encode([
                "erro" => "Erro na conexão com o banco de dados LivoAPI",
                "detalhes" => $e->getMessage()
            ]);
        }
        return $this->conn;
    }
}
