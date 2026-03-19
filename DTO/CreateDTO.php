<?php

class CreateDTO {
    public string $titulo;
    public string $autor;
    public int $ano;


    public function __construct(array $data)
    {
        $this->titulo = trim($data['titulo'] ?? '');
        $this->autor = trim($data['autor'] ?? '');
        $this->ano = (int) ($data['ano'] ?? 0);

        $this->validarLivro();
    }

    public function validarLivro(): void {
        if ($this->titulo === ''){
            throw new Exception("Titulo é obrigatório");
        }

        if($this->autor === ''){
            throw new Exception("Autor é obrigatório");
        }

        if($this->ano < 0){
            throw new Exception("Ano inválido");
        }

        if($this->ano > (int) date('Y')){
            throw new Exception("A data não pode ser no futuro");
        }
    }


}
?>