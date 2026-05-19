<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Session.php';

class GuiaController {

    private $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    private function sanitizeField($value) {
        return trim(strip_tags($value ?? ''));
    }

    private function normalizePhone($value) {
        $phone = preg_replace('/[^0-9]/', '', (string) $value);
        return $phone !== '' ? $phone : null;
    }

    public function index() {
        header('Content-Type: application/json');

        $stmt = $this->conn->prepare("SELECT id, nome, email, telefone, especialidade, ativo FROM guias ORDER BY nome");
        $stmt->execute();

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create() {
        Session::start();

        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        $nome = $this->sanitizeField($data['nome'] ?? '');
        $email = $this->sanitizeField($data['email'] ?? '');
        $telefone = $this->normalizePhone($data['telefone'] ?? '');
        $especialidade = $this->sanitizeField($data['especialidade'] ?? '');
        $ativo = isset($data['ativo']) && ($data['ativo'] === true || $data['ativo'] === '1' || $data['ativo'] === 1) ? 1 : 0;

        if ($nome === '') {
            http_response_code(400);
            echo json_encode(["erro" => "Nome do guia é obrigatório"]);
            return;
        }

        if (strlen($nome) > 120) {
            http_response_code(400);
            echo json_encode(["erro" => "Nome do guia não pode ter mais de 120 caracteres"]);
            return;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["erro" => "E-mail do guia inválido"]);
            return;
        }

        if (strlen($email) > 120) {
            http_response_code(400);
            echo json_encode(["erro" => "E-mail não pode ter mais de 120 caracteres"]);
            return;
        }

        if (strlen($telefone) > 20) {
            http_response_code(400);
            echo json_encode(["erro" => "Telefone não pode ter mais de 20 caracteres"]);
            return;
        }

        if (strlen($especialidade) > 150) {
            http_response_code(400);
            echo json_encode(["erro" => "Especialidade não pode ter mais de 150 caracteres"]);
            return;
        }

        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO guias (nome, email, telefone, especialidade, ativo)
                 VALUES (?, ?, ?, ?, ?)");

            $stmt->execute([
                $nome,
                $email !== '' ? $email : null,
                $telefone !== '' ? $telefone : null,
                $especialidade !== '' ? $especialidade : null,
                $ativo
            ]);

            echo json_encode(["mensagem" => "Guia cadastrado com sucesso"]);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["erro" => $e->getMessage()]);
        }
    }

    public function update($id) {
        Session::start();

        header('Content-Type: application/json');

        $id = (int) $id;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["erro" => "ID de guia inválido"]);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $nome = $this->sanitizeField($data['nome'] ?? '');
        $email = $this->sanitizeField($data['email'] ?? '');
        $telefone = $this->normalizePhone($data['telefone'] ?? '');
        $especialidade = $this->sanitizeField($data['especialidade'] ?? '');
        $ativo = isset($data['ativo']) && ($data['ativo'] === true || $data['ativo'] === '1' || $data['ativo'] === 1) ? 1 : 0;

        if ($nome === '') {
            http_response_code(400);
            echo json_encode(["erro" => "Nome do guia é obrigatório"]);
            return;
        }

        if (strlen($nome) > 120) {
            http_response_code(400);
            echo json_encode(["erro" => "Nome do guia não pode ter mais de 120 caracteres"]);
            return;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["erro" => "E-mail do guia inválido"]);
            return;
        }

        if (strlen($email) > 120) {
            http_response_code(400);
            echo json_encode(["erro" => "E-mail não pode ter mais de 120 caracteres"]);
            return;
        }

        if (strlen($telefone) > 20) {
            http_response_code(400);
            echo json_encode(["erro" => "Telefone não pode ter mais de 20 caracteres"]);
            return;
        }

        if (strlen($especialidade) > 150) {
            http_response_code(400);
            echo json_encode(["erro" => "Especialidade não pode ter mais de 150 caracteres"]);
            return;
        }

        try {
            $stmt = $this->conn->prepare(
                "UPDATE guias
                 SET nome = ?, email = ?, telefone = ?, especialidade = ?, ativo = ?
                 WHERE id = ?");

            $stmt->execute([
                $nome,
                $email !== '' ? $email : null,
                $telefone !== '' ? $telefone : null,
                $especialidade !== '' ? $especialidade : null,
                $ativo,
                $id
            ]);

            if ($stmt->rowCount() === 0) {
                $check = $this->conn->prepare("SELECT id FROM guias WHERE id = ?");
                $check->execute([$id]);
                if ($check->fetch(PDO::FETCH_ASSOC) === false) {
                    http_response_code(404);
                    echo json_encode(["erro" => "Guia não encontrado"]);
                    return;
                }
            }

            echo json_encode(["mensagem" => "Guia atualizado com sucesso"]);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["erro" => $e->getMessage()]);
        }
    }

    public function delete($id) {
        Session::start();

        header('Content-Type: application/json');

        $id = (int) $id;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["erro" => "ID de guia inválido"]);
            return;
        }

        try {
            $stmt = $this->conn->prepare("DELETE FROM guias WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(["erro" => "Guia não encontrado"]);
                return;
            }

            echo json_encode(["mensagem" => "Guia removido com sucesso"]);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["erro" => $e->getMessage()]);
        }
    }
}

?>
