<?php

class RegisterUserDTO {
    public string $nome;
    public string $email;
    public string $senha;
    public string $senha_hash;

public function __construct(array $data){

    $this->nome = trim($data['nome'] ?? '');
    $this->email = trim($data['email'] ?? '');
    $this->senha = trim($data['senha'] ?? '');

    $this->validar();

    $this->senha_hash = password_hash($this->senha, PASSWORD_DEFAULT);

}
     private function validar(): void
    {
        if ($this->nome === '') {
            throw new Exception('Nome é obrigatório');
        }

        if ($this->email === '') {
            throw new Exception('Email é obrigatório');
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }

        if ($this->senha === '') {
            throw new Exception('Senha é obrigatória');
        }

        if (strlen($this->senha) <= 8) {
            throw new Exception('A senha deve ter no mínimo 8 caracteres');
        }

        if (!preg_match('/[A-Z]/', $this->senha)) {
            throw new Exception('A senha deve conter pelo menos uma letra maiúscula');
        }

        if (!preg_match('/[a-z]/', $this->senha)) {
            throw new Exception('A senha deve conter pelo menos uma letra minúscula');
        }

        if (!preg_match('/[0-9]/', $this->senha)) {
            throw new Exception('A senha deve conter pelo menos um número');
        }

        if (!preg_match('/[\W_]/', $this->senha)) {
            throw new Exception('A senha deve conter pelo menos um caractere especial');
        }
    }
}
