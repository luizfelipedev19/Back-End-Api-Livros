<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php';


class BaseController {

protected array $data;
protected ?string $uuid;
protected $user;


public function __construct(bool $authRequired  = true)
{
    header("Content-Type: application/json; charset=UTF-8");
    
    $this->data = json_decode(file_get_contents("php://input"), true) ?? [];

    if($authRequired){
    $usuario = AuthMiddleware::autenticar();
    $this->user = $usuario;
    $this->uuid = $usuario->data->UUID ?? null;
    } else {
        $this->user = null;
        $this->uuid = null;
    }
}

protected function requireAuth(): void {
    if(!$this->user || !$this->uuid){
       $this->error("Acesso não autorizado", 401);
       exit;
     }
}

protected function success(array $data = [], int $code = 200): void {
    http_response_code($code);
    echo json_encode([
        "success" => true,
        "detail" => $data
    ]);
    
}


protected function error(string $mensagem, int $code = 400): void {
    http_response_code($code);
    echo json_encode([
        "success" => false,
        "mensagem" => $mensagem
    ]);
}
}