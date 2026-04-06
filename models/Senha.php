<?php

class Senha {

    private Usuarios $usuarioModel;
    private PDO $conn;
    private array $data;
    private string $table = "senha_recuperacao";
   
    public function __construct(PDO $db){
        $this->usuarioModel = new Usuarios($db);
        $this->conn = $db;
        $this->data = json_decode(file_get_contents("php://input"), true) ?? [];
    }

    public function gerarTokenSenha(int $idUsuario): string|false {

    $created_at  = date('Y-m-d H:i:s');

    //invalidar tokens antigos
    $query = "UPDATE {$this->table}
    SET usado = 1,
    WHERE usuario_id =  :usuario_id";

    $stmt = $this->conn->prepare($query);

    $execInvaliToken = $stmt->execute([
        'usuario_id' => $idUsuario
    ]);

    if(!$execInvaliToken){
        return false;
    }


    //gerar token
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->conn->prepare(
            "INSERT into {$this->table}
            (usuario_id, token, expira_em) VALUES
            (:usuario_id, :token, :expira_em)");
        
        $execInsert = $stmt->execute([
            'usuario_id' => $idUsuario,
            'token' => $tokenHash,
            'expira_em' => $expiracao
        ]);

        if(!$execInsert){
            return false;
        }

        return $token;

}

public function converterToken($token): string|false {
    $tokenHash = hash('sha256', $token);

    $agora = date('Y-m-d H:i:s');

    $sql = "
    SELECT usuario_id
    from {$this->table}
    WHERE token = :token
    and usado = 0
    and expira_em > :agora
    limit 1";

    $stmt = $this->conn->prepare($sql);
    
    $stmt->execute([
        'token' => $tokenHash,
        'agora' => $agora
    ]);


    return $tokenHash;
}


public function gerarSenhaHash(string $senha, int $idUsuario, string $tokenHash): string|false {
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    //alterar senha para a nvoa
    $stmtSenha = $this->conn->prepare(
        "UPDATE usuarios
        SET senha_hash = :senha,
        updated_at = :updated_at
        WHERE id_usuario = :id"
    );

    $stmtSenha->execute([
        'senha' => $senhaHash,
        'updated_at' => date('Y-m-d H:i:s'),
        'id' => $idUsuario
    ]);

    if(!$stmtSenha){
        return false;
    }
    

    //marcar o token como usado depois de redefinir a senha
    $stmtToken = $this->conn->prepare("
    UPDATE {$this->table}
    SET usado = 1
    WHERE token = :token");

    $stmtToken->execute([
        'token' => $tokenHash
    ]);

    if(!$stmtToken){
        return false;
    }

    return $senhaHash;
}
}