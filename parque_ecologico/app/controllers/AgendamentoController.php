<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/Agendamento.php';
require_once __DIR__ . '/../models/Bloqueio.php';

class AgendamentoController {

    private $agendamentoModel;
    private $bloqueioModel;
    private $conn;

    public function __construct() {
        try {
            $this->conn = Database::connect();
            $this->agendamentoModel = new Agendamento($this->conn);
            $this->bloqueioModel = new Bloqueio($this->conn);
        } catch (Throwable $e) {
            die(json_encode([
                "erro" => $e->getMessage()
            ]));
        }
    }

    private function limpar($valor) {
        return strip_tags(trim($valor));
    }

    private function isValidDate($value) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function isValidTime($value) {
        $time = DateTime::createFromFormat('H:i', $value);
        return $time && $time->format('H:i') === $value;
    }

    private function getBloqueio($data) {
        return $this->bloqueioModel->findByDate($data);
    }

    private function dataBloqueada($data) {
        return $this->getBloqueio($data) !== false;
    }

    private function horarioConflitante($data) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM agendamento
            WHERE data_reserva = ?
            AND quiosque_id = ?
            AND status != 'rejeitado'
            AND (
                horario_entrada < ?
                AND horario_saida > ?
            )
        ");

        $stmt->execute([
            $data['data_reserva'],
            $data['quiosque_id'],
            $data['horario_saida'],
            $data['horario_entrada']
        ]);

        return $stmt->fetchColumn() > 0;
    }

    private function validar($data) {

        foreach ($data as $k => $v) {
            $data[$k] = $this->limpar($v);
        }


        if (empty($data['nome_responsavel']) || strlen($data['nome_responsavel']) < 3)
            return "Responsável inválido";

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
            return "Email inválido";

        if (empty($data['telefone']) || strlen($data['telefone']) < 8)
            return "Telefone inválido";

        if (empty($data['aceite_termos']) || !in_array($data['aceite_termos'], ['1', 1, true, 'true'], true))
            return "É obrigatório aceitar os termos";

        if (
            empty($data['quiosque_id']) ||
            !ctype_digit((string) $data['quiosque_id']) ||
            $data['quiosque_id'] < 1 ||
            $data['quiosque_id'] > 20
        )
            return "Quiosque inválido";

        if (
            !isset($data['qtd_visitantes']) ||
            !is_numeric($data['qtd_visitantes']) ||
            $data['qtd_visitantes'] < 1
        )
            return "Quantidade inválida";

        if ($data['qtd_visitantes'] > 8)
            return "Cada quiosque permite no máximo 8 visitantes";

        if (empty($data['data_reserva']) || !$this->isValidDate($data['data_reserva']))
            return "Data inválida";

        $dataReserva = strtotime($data['data_reserva']);
        $amanha = strtotime('+4 day');
        $limite = strtotime('+3 months');

        // NÃO pode ser hoje
        if ($dataReserva < $amanha)
            return "Reservas devem ser feitas com no mínimo 4 dia de antecedência";

        // máximo 3 meses
        if ($dataReserva > $limite)
            return "Reservas podem ser feitas com no máximo 3 meses de antecedência";

        $bloqueio = $this->getBloqueio($data['data_reserva']);
        if ($bloqueio) {
            $mensagem = trim($bloqueio['motivo'])
                ? "Data indisponível para agendamento: " . $bloqueio['motivo']
                : "Data indisponível para agendamento";
            return $mensagem;
        }

        if (date('N', $dataReserva) >= 6)
            return "Somente dias úteis";

        if (empty($data['horario_entrada']) || !$this->isValidTime($data['horario_entrada']))
            return "Horário de entrada inválido";

        if (empty($data['horario_saida']) || !$this->isValidTime($data['horario_saida']))
            return "Horário de saída inválido";

        $entrada = strtotime($data['horario_entrada']);
        $saida = strtotime($data['horario_saida']);

        $min = strtotime("08:00");
        $max = strtotime("16:00");

        if ($entrada < $min || $entrada > $max)
            return "Entrada inválida";

        if ($saida < $min || $saida > $max)
            return "Saída inválida";

        if ($saida <= $entrada)
            return "Saída deve ser após entrada";

        $duracaoMin = ($saida - $entrada) / 60;

        // mínimo 30 min
        if ($duracaoMin < 30)
            return "O tempo mínimo de reserva é 30 minutos";

        // máximo 4h
        if ($duracaoMin > 240)
            return "Máximo permitido: 4 horas";

        if ($this->horarioConflitante($data))
            return "Horário indisponível";

        return null;
    }

    public function index() {
        header('Content-Type: application/json');
        echo json_encode($this->agendamentoModel->getAll());
    }

    public function store() {
        

        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!$data || !is_array($data)) {
            http_response_code(400);
            echo json_encode(["erro" => "Payload inválido"]);
            return;
        }

        $data = array_map([$this, 'limpar'], $data);
        $data['aceite_termos'] = isset($data['aceite_termos']) && in_array($data['aceite_termos'], ['1', 1, true, 'true'], true) ? 1 : 0;

        if ($erro = $this->validar($data)) {

            http_response_code(422);

            echo json_encode([
                "erro" => $erro
            ]);

            return;
        }

        try {

            // Inserir/atualizar cliente no banco (email como PK)
            $stmt = $this->conn->prepare(
                "INSERT INTO clientes (email, nome_responsavel, telefone)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                   nome_responsavel = VALUES(nome_responsavel),
                   telefone = VALUES(telefone)
                "
            );

            $stmt->execute([
                $data['email'],
                $data['nome_responsavel'],
                $data['telefone'] ?? null
            ]);

            if ($this->agendamentoModel->create($data)) {

                echo json_encode([
                    "mensagem" => "Pedido de reserva realizada com sucesso. Notificaremos por email quando for aprovada ou rejeitada."
                ]);

            } else {
                throw new Exception("Erro ao salvar");
            }

        } catch (Throwable $e) {

            http_response_code(500);

            echo json_encode([
                "erro" => $e->getMessage()
            ]);
        }
    }

    public function aprovar($id) {
        $this->atualizarStatus($id, 'aprovado');
    }

    public function rejeitar($id) {
        $this->atualizarStatus($id, 'rejeitado');
    }

    private function atualizarStatus($id, $status) {

        $id = (int) $id;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["erro" => "ID inválido"]);
            return;
        }

        $stmt = $this->conn->prepare(
            "UPDATE agendamento
            SET status = ?
            WHERE id = ?"
        );

        $stmt->execute([$status, $id]);

        echo json_encode([
            "mensagem" => ucfirst($status) . " com sucesso"
        ]);
    }

    public function delete($id) {

        $id = (int) $id;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["erro" => "ID inválido"]);
            return;
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM agendamento
            WHERE id = ?"
        );

        $stmt->execute([$id]);

        echo json_encode([
            "mensagem" => "Excluído com sucesso"
        ]);
    }
}
?>