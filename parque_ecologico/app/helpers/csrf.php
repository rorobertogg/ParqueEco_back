<?php

require_once __DIR__ . '/../core/Session.php';

function gerarCsrfToken() {
    Session::start();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validarCsrfToken($token) {
    Session::start();

    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

?>