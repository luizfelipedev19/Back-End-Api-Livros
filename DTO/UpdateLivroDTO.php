<?php

class UpdateLivroDTO {
    public ?string $titulo;
    public ?string $autor;
    public ?int $ano;

    public function __construct(array $data)
    {
        $this->titulo = isset($data['titulo']) ? trim($data['titulo']) : null;
        $this->autor = isset($data['autor']) ? trim($data['autor']): null;
        $this->ano = isset($data['ano']) ? (int) $data['ano'] : null;

        $this->validar();
    }

    private function validar(): void {
        if ($this->titulo !== null && $this->titulo === '') {
            throw new Exception("Titulo inválido");
        }

        if($this->autor !== null && $this->autor === ''){
            throw new Exception("Autor inválido");
        }

        if($this->ano !== null){
            if($this->ano <=0){
                throw new Exception("Ano inválido");
            }

            if($this->ano > (int) date('Y')){
                throw new Exception("Ano não pode ser no futuro");
            }
        }
        if ($this->titulo === null && $this->autor === null && this->ano === null){
            throw new Exception("Nenhum campo para atualizar");
        }
    }


}
?>