<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /PROJETO4/public/index.php');
    exit;
}

// Timeout de 30 minutos de inatividade
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: /PROJETO4/public/index.php?timeout=1');
    exit;
}

$_SESSION['last_activity'] = time();
?>