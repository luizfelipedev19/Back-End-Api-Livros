<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHandler
{
    private string $secret;
    private string $alg;
    private int $exp;
    private string $iss;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'];
        $this->alg = $_ENV['JWT_ALG'];
        $this->exp = (int) $_ENV['JWT_EXP'];
        $this->iss = $_ENV['JWT_ISS'];
    }

    public function gerarToken(array $usuarios): string
    {
        $payload = [
            "iss" => $this->iss,
            "iat" => time(),
            "exp" => time() + $this->exp,
            "data" => [
                "id_usuario" => $usuarios["id_usuario"],
                "nome" => $usuarios["nome"],
                "email" => $usuarios["email"]
            ]
        ];

        return JWT::encode($payload, $this->secret, $this->alg);
    }

    public function validarToken(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, $this->alg));
    }
}
