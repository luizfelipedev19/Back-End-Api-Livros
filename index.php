<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$database = new Database();
$db = $database->getConnection();

$routes = require __DIR__ . '/routes/api.php';

$method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

$uri = str_replace("/api-livros", "", $uri);

$routeFound = false;

foreach ($routes as $route) {
    if ($route["method"] === $method && $route["path"] === $uri) {
        $routeFound = true;

        $controllerName = $route["controller"];
        $action = $route["action"];
        $authRequired = $route["auth"];

        require_once __DIR__ . "/controllers/{$controllerName}.php";

        if ($authRequired) {
            AuthMiddleware::autenticar();
        }

        $controller = new $controllerName($db);
        $controller->$action();

        exit;
    }
}

if (!$routeFound) {
    http_response_code(404);
    echo json_encode(["mensagem" => "Rota não encontrada"]);
}