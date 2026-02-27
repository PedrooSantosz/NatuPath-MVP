<?php

define('BASE_URL', 'http://localhost/seu-projeto');
define('BASE_PATH', __DIR__ . '/../');

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_NAME', 'seu_banco');

// Conexão com o banco
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');
