# API Livros

API REST desenvolvida em PHP para cadastro de usuarios, autenticacao com JWT, gerenciamento de livros pessoais e recuperacao de senha por e-mail.

## Resumo tecnico

- Linguagem: PHP 8.2
- Framework: nao utiliza framework PHP tradicional
- Arquitetura: PHP puro com roteamento manual, controllers, models, DTOs e middleware proprio
- Servidor web: Apache
- Banco de dados: MySQL 8
- Documentacao da API: OpenAPI/Swagger (`swagger.yaml`, `swagger.json`, `swagger.html`)
- Containerizacao: Docker e Docker Compose

## Bibliotecas PHP usadas

As dependencias do projeto sao gerenciadas pelo Composer:

- `firebase/php-jwt`: geracao e validacao de tokens JWT
- `vlucas/phpdotenv`: carregamento de variaveis de ambiente do `.env`
- `phpmailer/phpmailer`: envio de e-mails de recuperacao de senha
- `zircote/swagger-php`: geracao da documentacao OpenAPI a partir de atributos PHP
- `symfony/console`: suporte de console usado pelo ecossistema do Swagger

## Infraestrutura necessaria

- PHP 8.2 com extensoes `pdo`, `pdo_mysql` e `mysqli`
- Apache com `mod_rewrite` habilitado
- Composer
- MySQL 8.0
- Opcional: phpMyAdmin para inspecao do banco

## Estrutura do projeto

- `index.php`: ponto de entrada da aplicacao
- `routes/api.php`: definicao das rotas
- `controllers/`: regras de negocio da API
- `models/`: acesso ao banco de dados
- `DTO/`: validacao e organizacao dos dados de entrada
- `middleware/`: autenticacao JWT
- `utils/`: utilitarios como JWT e envio de e-mail
- `config/`: conexao com banco e schemas OpenAPI

## Como a aplicacao funciona

O arquivo `index.php` carrega o autoload do Composer, le o `.env`, cria a conexao com o banco e compara a URL requisitada com a lista de rotas definida em `routes/api.php`.

Cada rota aponta para um controller e uma action. Quando a rota exige autenticacao, o middleware `middleware/AuthMiddleware.php` valida:

- o header `Authorization: Bearer <token>`
- o header `X-User-UUID`
- o conteudo do JWT

## Funcionalidades implementadas

### Autenticacao

- `POST /register`: cadastro de usuario
- `POST /login`: login e retorno do token JWT

### Usuario

- `GET /usuario`: dados do usuario autenticado
- `PUT /usuario/editar`: edicao de nome e e-mail
- `PATCH /usuario/foto`: atualizacao da foto de perfil via URL
- `DELETE /usuario/deletar`: exclusao do usuario autenticado

### Livros

- `POST /livros`: criacao de livro
- `GET /livros`: listagem paginada com filtros
- `PUT /livro/editar`: atualizacao de livro
- `DELETE /livro/deletar`: remocao de livro

Campos identificados nos livros:

- `titulo`
- `autor`
- `ano`
- `genero`
- `status`
- `avaliacao`
- `anotacoes`

### Recuperacao de senha

- `POST /recuperar-senha`: gera token e envia e-mail
- `POST /redefinir-senha`: redefine a senha

## Variaveis de ambiente esperadas

O projeto depende destas variaveis no arquivo `.env`:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `JWT_SECRET`
- `JWT_ALG`
- `JWT_EXP`
- `JWT_ISS`
- `MAIL_USER`
- `MAIL_PASS`

## Como rodar com Docker

1. Suba os containers:

```bash
docker-compose up --build
```

2. Servicos disponiveis:

- API: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081`
- MySQL: porta `3308`

## Como rodar sem Docker

1. Instale as dependencias:

```bash
composer install
```

2. Configure o arquivo `.env`
3. Garanta que o MySQL esteja acessivel com as credenciais configuradas
4. Execute a aplicacao em um ambiente PHP com Apache ou servidor compativel com a estrutura atual

## Documentacao OpenAPI

Arquivos encontrados no projeto:

- `swagger.yaml`
- `swagger.json`
- `swagger.html`

Script Composer para regenerar a documentacao:

```bash
composer run swagger
```

## Entidades de banco identificadas no codigo

Pelas queries dos models, a aplicacao usa pelo menos estas tabelas:

- `usuarios`
- `livros`
- `senha_recuperacao`
