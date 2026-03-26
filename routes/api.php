<?php
return [
    [
        "method" => "POST",
        "path" => "/register",
        "controller" => "AuthController",
        "action" => "register",
        "auth" => false
    ],
    [
        "method" => "POST",
        "path" => "/login",
        "controller" => "AuthController",
        "action" => "login",
        "auth" => false
    ],
    [
        "method" => "GET",
        "path" => "/perfil",
        "controller" => "AuthController",
        "action" => "perfil",
        "auth" => true
    ],
    [
        "method" => "POST",
        "path" => "/livros",
        "controller" => "LivroController",
        "action" => "criarLivro",
        "auth" => true,
    ],
    [
        "method" => "GET",
        "path" => "/livros",
        "controller" => "LivroController",
        "action" => "listarLivros",
        "auth" => true
    ],
    [
    "method" => "PUT",
    "path" => "/livro",
    "controller" => "LivroController",
    "action" => "atualizarLivro",
    "auth" => true
    ],
    [
    "method" => "DELETE",
    "path" => "/livro",
    "controller" => "LivroController",
    "action" => "deletarLivro",
    "auth" => true
    ],
    [
    "method" => "PATCH",
    "path" => "/usuario/foto",
    "controller" => "UsuarioController",
    "action" => "atualizarFoto",
    "auth" => true
    ],
    [
    "method" => "POST",
    "path" => "/senha",
    "controller" => "SenhaController",
    "action" => "solicitarRecuperacao",
    "auth" => false
    ]
];
