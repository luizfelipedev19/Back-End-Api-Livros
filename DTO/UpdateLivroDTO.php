<?php

class UpdateLivroDTO {
    public ?string $titulo;
    public ?string $autor;
    public ?int $ano;
    public ?string $genero;
    public ?string $status; 
    public ?int $avaliacao;
    public ?string $anotacoes;


    public function __construct(array $data)
    {
        $this->titulo = isset($data['titulo']) ? trim($data['titulo']) : null;

        $this->autor = isset($data['autor']) ? trim($data['autor']): null;

        $this->ano = isset($data['ano']) ? (int) $data['ano'] : null;

        $this->genero = isset($data['genero']) ? trim($data['genero']) : null;

        $this->status = isset($data['status']) ? trim($data['status'])
        : null;

        $this->avaliacao = isset($data['avaliacao']) ? (int) $data['avaliacao'] : null;

        $this->anotacoes = isset($data['anotacoes']) ? trim($data['anotacoes']) : null;

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
        
        if($this->genero !== null && $this->genero === ''){
            throw new Exception("Gênero inválido");
        }

        if($this->status !== null){
            $statusValido = ['lendo', 'lido', 'quero_ler'];
            if (!in_array($this->status, $statusValido)){
                throw new Exception("Status inválido. Os status válidos são: " . implode(", ", $statusValido));
            }
        }
        if($this->avaliacao !== null && ($this->avaliacao < 1 || $this->avaliacao > 5)){
            throw new Exception("Avaliação deve ser entre 1 e 5");
        }

        if($this->anotacoes !== null && $this->anotacoes === ''){
            throw new Exception("Anotações inválidas");
        }

        if ($this->titulo === null && $this->autor === null && $this->ano === null && $this->genero === null && $this->status === null && $this->avaliacao === null && $this->anotacoes === null){
            throw new Exception("Nenhum campo para atualizar");
        }
    
    }


}
?>