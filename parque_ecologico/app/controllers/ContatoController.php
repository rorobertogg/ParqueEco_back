<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/Mensagem.php';

class ContatoController {

    private $conn;
    private $model;

    public function __construct() {
        $this->conn = Database::connect();
        $this->model = new Mensagem($this->conn);
    }

    private function limpar($v) {
        return trim(strip_tags($v ?? ''));
    }

    public function store() {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !is_array($data)) {
            http_response_code(400);
            echo json_encode(["erro" => "Payload inválido"]);
            return;
        }

        // validações simples
        $nome = $this->limpar($data['nome'] ?? '');
        $email = $this->limpar($data['email'] ?? '');
        $assunto = $this->limpar($data['assunto'] ?? '');
        $mensagem = $this->limpar($data['mensagem'] ?? '');

        if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
            http_response_code(422);
            echo json_encode(["erro" => "Preencha todos os campos obrigatórios."]);
            return;
        }

        if (mb_strlen($nome) > 120) {
            http_response_code(422);
            echo json_encode(["erro" => "Nome muito longo."]);
            return;
        }

        if (mb_strlen($assunto) > 150) {
            http_response_code(422);
            echo json_encode(["erro" => "Assunto muito longo."]);
            return;
        }

        if (mb_strlen($mensagem) > 2000) {
            http_response_code(422);
            echo json_encode(["erro" => "Mensagem muito longa."]);
            return;
        }

        // validar email básico
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(["erro" => "E-mail inválido."]);
            return;
        }

        // validar telefone opcional: deve ter 10 ou 11 dígitos quando informado
        $telefoneRaw = preg_replace('/[^0-9]/', '', $data['telefone'] ?? '');
        if ($telefoneRaw !== '' && !preg_match('/^\d{10,11}$/', $telefoneRaw)) {
            http_response_code(422);
            echo json_encode(["erro" => "Telefone inválido. Use 10 ou 11 dígitos."]);
            return;
        }

        try {
            $this->model->create([
                'nome' => $nome,
                'email' => $email,
                'telefone' => $telefoneRaw,
                'assunto' => $assunto,
                'mensagem' => $mensagem
            ]);

            echo json_encode(["mensagem" => "Obrigado! Entraremos em contato em breve."]);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["erro" => $e->getMessage()]);
        }
    }

    // Admin: listar mensagens
    public function index() {
        header('Content-Type: application/json');

        $filters = [];
        if (isset($_GET['lida'])) {
            $filters['lida'] = $_GET['lida'] === '1' || $_GET['lida'] === 'true';
        }
        if (isset($_GET['respondida'])) {
            $filters['respondida'] = $_GET['respondida'] === '1' || $_GET['respondida'] === 'true';
        }

        echo json_encode($this->model->getAll($filters));
    }

    public function updateStatus($id) {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !is_array($data)) {
            http_response_code(400);
            echo json_encode(["erro" => "Payload inválido"]);
            return;
        }

        try {
            if ($this->model->updateStatus($id, [
                'lida' => isset($data['lida']) ? (bool) $data['lida'] : null,
                'respondida' => isset($data['respondida']) ? (bool) $data['respondida'] : null,
            ])) {
                echo json_encode(["mensagem" => "Status atualizado"]);
                return;
            }

            http_response_code(422);
            echo json_encode(["erro" => "Nenhum status fornecido"]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["erro" => $e->getMessage()]);
        }
    }

    public function delete($id) {
        header('Content-Type: application/json');

        try {
            $this->model->delete($id);
            echo json_encode(["mensagem" => "Mensagem excluída com sucesso"]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["erro" => $e->getMessage()]);
        }
    }
}

?>
