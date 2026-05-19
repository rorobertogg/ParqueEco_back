 <?php

    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../core/Session.php';

    class AuthController {

        private $conn;

        public function __construct() {
            $this->conn = Database::connect();
        }

        /*
        |--------------------------------------------------------------------------
        | RESPOSTA JSON
        |--------------------------------------------------------------------------
        */

        private function json($data, $code = 200) {

            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($data);

            exit;
        }

        private function sanitizeInput(string $value): string {
            return trim(strip_tags($value));
        }

        private function validateLoginName(string $login): bool {
            return preg_match('/^[A-Za-z0-9_.@-]{3,64}$/', $login) === 1;
        }



        /*
        |--------------------------------------------------------------------------
        | CADASTRO
        |--------------------------------------------------------------------------
        */

        public function register() {

            Session::start();

            try {

                $data = json_decode(
                    file_get_contents("php://input"),
                    true
                );

                if (!$data || !is_array($data)) {
                    $this->json([
                        "erro" => "Payload inválido"
                    ], 400);
                }

                $nome = $this->sanitizeInput($data['nome'] ?? '');
                $sobrenome = $this->sanitizeInput($data['sobrenome'] ?? '');
                $login = $this->sanitizeInput($data['usuario'] ?? '');
                $senha = $data['senha'] ?? '';

                if (!$nome || !$sobrenome || !$login || !$senha) {
                    $this->json([
                        "erro" => "Preencha todos os campos"
                    ], 400);
                }

                if (mb_strlen($nome) > 120 || mb_strlen($sobrenome) > 120) {
                    $this->json([
                        "erro" => "Nome ou sobrenome muito longo"
                    ], 400);
                }

                if (! $this->validateLoginName($login)) {
                    $this->json([
                        "erro" => "Usuário inválido"
                    ], 400);
                }

                if (mb_strlen($senha) < 8) {
                    $this->json([
                        "erro" => "A senha deve ter pelo menos 8 caracteres"
                    ], 400);
                }

                $stmt = $this->conn->prepare("
                    SELECT id
                    FROM usuarios
                    WHERE login_usuario = ?
                    ");

                $stmt->execute([$login]);

                if ($stmt->fetch()) {

                    $this->json([
                        "erro" => "Usuário já existe"
                    ], 409);
                }

                $hash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );

                $stmt = $this->conn->prepare("
                    INSERT INTO usuarios (
                        nome_usuario,
                        sobrenome_usuario,
                        login_usuario,
                        senha_usuario,
                        tipo
                    )
                    VALUES (?, ?, ?, ?, 'cliente')
                ");

                $ok = $stmt->execute([
                    $nome,
                    $sobrenome,
                    $login,
                    $hash
                ]);

                if (!$ok) {
                    throw new Exception("Erro ao cadastrar");
                }

                $this->json([
                    "status" => "sucesso",
                    "mensagem" => "Cadastro realizado com sucesso"
                ]);

            } catch (Throwable $e) {

                $this->json([
                    "erro" => $e->getMessage()
                ], 500);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | LOGIN
        |--------------------------------------------------------------------------
        */

        public function login() {

            Session::start();

            try {

                $data = json_decode(
                    file_get_contents("php://input"),
                    true
                );

                if (!$data || !is_array($data)) {

                    $this->json([
                        "erro" => "Payload inválido"
                    ], 400);
                }

                $usuario = $this->sanitizeInput(
                    $data['usuario'] ?? ''
                );

                $senha = $data['senha'] ?? '';

                if (!$usuario || !$senha) {

                    $this->json([
                        "erro" => "Preencha usuário e senha"
                    ], 400);
                }

                if (! $this->validateLoginName($usuario)) {
                    $this->json([
                        "erro" => "Usuário inválido"
                    ], 400);
                }

                $stmt = $this->conn->prepare("
                    SELECT *
                    FROM usuarios
                    WHERE login_usuario = ?
                ");

                $stmt->execute([$usuario]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (
                    !$user ||
                    !password_verify(
                        $senha,
                        $user['senha_usuario']
                    )
                ) {

                    $this->json([
                        "erro" => "Credenciais inválidas"
                    ], 401);
                }

                session_regenerate_id(true);

                $_SESSION['usuario_id'] =
                    $user['id'];

                $_SESSION['usuario_nome'] =
                    $user['nome_usuario'];

                $_SESSION['tipo'] =
                    strtolower(trim($user['tipo']));

                $this->json([
                    "status" => "sucesso",
                    "mensagem" => "Login realizado com sucesso",
                    "tipo" => $_SESSION['tipo']
                ]);

            } catch (Throwable $e) {

                $this->json([
                    "erro" => $e->getMessage()
                ], 500);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | LOGOUT
        |--------------------------------------------------------------------------
        */

        public function logout() {

            Session::start();

            $_SESSION = [];

            if (ini_get("session.use_cookies")) {

                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"] ?? '',
                    $params["secure"],
                    $params["httponly"]
                );
            }

            session_destroy();

            $this->json([
                "status" => "sucesso",
                "mensagem" => "Logout realizado"
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK LOGIN
        |--------------------------------------------------------------------------
        */

        public function check() {

        Session::start();

        if (!isset($_SESSION['usuario_id'])) {
            $this->json([
                "logado" => false
            ], 401);
        }

        $this->json([
            "logado" => true,
            "nome" => $_SESSION['usuario_nome'],
            "tipo" => $_SESSION['tipo']
        ]);
    }


    }