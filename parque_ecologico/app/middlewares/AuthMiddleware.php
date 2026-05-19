<?php

class AuthMiddleware {

    public static function handle() {

        require_once __DIR__ . '/../core/Session.php';
        Session::start();

        if (
            empty($_SESSION['usuario_id']) ||
            empty($_SESSION['tipo']) ||
            strtolower(trim($_SESSION['tipo'])) !== 'admin'
        ) {
            http_response_code(401);

            echo json_encode([
                "erro" => "Não autorizado"
            ]);

            exit;
        }

        if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
            require_once __DIR__ . '/../helpers/csrf.php';

            // fallback compatível com InfinityFree
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

            if (!validarCsrfToken($token)) {
                http_response_code(403);

                echo json_encode([
                    "erro" => "CSRF inválido"
                ]);

                exit;
            }
        }
    }
}
?>