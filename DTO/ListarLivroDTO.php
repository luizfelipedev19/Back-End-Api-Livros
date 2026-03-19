<?php

class ListarLivroDTO {
    public ?string $titulo;
    public ?string $autor;
    public ?int $ano;
    public int $page;
    public int $limit;
    public string $sort;
    public string $order;

    public function __construct(array $query)
    {
        $this->titulo = isset($query['titulo']) ? trim($query['titulo']) : null;
        $this->autor = isset($query['autor']) ? trim($query['autor']) : null;
        $this->ano = isset($query['ano']) ? trim($query['ano']): null;

        $this->page = isset($query['page']) ? (int) $query['page'] : 1;
        $this->limit = isset($query['limit']) ? (int) $query['limit']: 10;

        $this->sort = $query['sort'] ?? 'id';
        $this->order= strtolower($query['order'] ?? 'asc');


        $this->validar();
    }

    private function validar(): void {

        if($this->page < 1){
            $this->page = 1;
        }

        if($this->limit < 1){
            $this->limit = 10;
        }
        if($this->limit > 100){
            $this->limit = 100;
        }

        $allowedSort = ['id', 'titulo', 'autor', 'ano'];
        if(!in_array($this->sort, $allowedSort, true)){
            $this->sort = 'id';
        }

        if(!in_array($this->order, ['asc', 'desc'], true)){
            $this->order = 'asc';
        }
    }
}
?>