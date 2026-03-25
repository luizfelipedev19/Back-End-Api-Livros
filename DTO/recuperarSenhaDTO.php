<?php
require_once __DIR__ . '/../models/Usuarios.php';

class recuperarSenhaDTO {
    public string $email;

    public function __construct($data)
    {
        $this->email = isset($data['email']) ? trim($data['email']) : null;

        $this->validar();
    }

    public function validar(): void {
        if($this->email === ''){
            throw new Exception("Email é obrigatório");
        }

        if(!filter_var($this->email, FILTER_VALIDATE_EMAIL)){
            throw new Exception("Precisa ser um e-mail válido");
        }
    }
}