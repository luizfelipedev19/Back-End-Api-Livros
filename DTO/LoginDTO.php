<?php

class LoginDTO {
    public string $email;
    public string $senha;

    public function __construct(array $data)
    {
        $this->email = trim($data['email'] ?? '');
        $this->senha = trim($data['senha'] ?? '');

        $this->validar();
    }

    private function validar(): void {
        if($this->email === ''){
            throw new Exception("Email é obrigatório");
        }

        if(!filter_var($this->email, FILTER_VALIDATE_EMAIL)){
            throw new Exception("Email inválido");
        }

        if($this->senha === ''){
            throw new Exception("Senha é obrigatório");
        }
    }
}