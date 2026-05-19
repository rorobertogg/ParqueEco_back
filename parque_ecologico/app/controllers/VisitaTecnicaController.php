<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/VisitaTecnica.php';
require_once __DIR__ . '/../models/Bloqueio.php';

class VisitaTecnicaController {

    private $conn;
    private $model;
    private $bloqueioModel;

    public function __construct() {
        $this->conn = Database::connect();
        $this->model = new VisitaTecnica($this->conn);
        $this->bloqueioModel = new Bloqueio($this->conn);
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
            FROM visita_tecnica
            WHERE data_visita = ?
            AND guia_id = ?
            AND (
                horario_entrada < ?
                AND horario_saida > ?
            )
        ");

        $stmt->execute([
            $data['data_visita'],
            $data['guia_id'],
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

        if (empty($data['guia_id']) || !ctype_digit((string) $data['guia_id']) || $data['guia_id'] < 1)
            return "Guia inválido";

        if (empty($data['aceite_termos']) || !in_array($data['aceite_termos'], ['1', 1, true, 'true'], true))
            return "É obrigatório aceitar os termos";

        if (empty($data['data_visita']) || !$this->isValidDate($data['data_visita']))
            return "Data inválida";

        $dataVisita = strtotime($data['data_visita']);
        $amanha = strtotime('+7 day');
        $limite = strtotime('+3 months');

        if ($dataVisita < $amanha)
            return "Visitas devem ser agendadas com no mínimo 7 dia de antecedência";

        if ($dataVisita > $limite)
            return "Visitas podem ser feitas com no máximo 3 meses de antecedência";

        $bloqueio = $this->getBloqueio($data['data_visita']);
        if ($bloqueio) {
            $mensagem = trim($bloqueio['motivo'])
                ? "Data indisponível: " . $bloqueio['motivo']
                : "Data indisponível";
            return $mensagem;
        }

        if (date('N', $dataVisita) >= 6)
            return "Somente dias úteis";

        if (empty($data['horario_entrada']) || !$this->isValidTime($data['horario_entrada']))
            return "Horário de entrada inválido";

        if (empty($data['horario_saida']) || !$this->isValidTime($data['horario_saida']))
            return "Horário de saída inválido";

        $entrada = strtotime($data['horario_entrada']);
        $saida = strtotime($data['horario_saida']);

        $duracaoMin = ($saida - $entrada) / 60;

        if ($duracaoMin < 30)
            return "Tempo mínimo: 30 minutos";

        if ($duracaoMin > 240)
            return "Máximo permitido: 4 horas";

        if ($this->horarioConflitante($data))
            return "Guia já reservado nesse horário";
        
        if (
            empty($data['qtd_visitantes']) ||
            !ctype_digit((string) $data['qtd_visitantes']) ||
            $data['qtd_visitantes'] < 1
        ) {
            return "Quantidade inválida";
        }

        if ($data['qtd_visitantes'] > 4) {
            return "Cada visita técnica permite no máximo 4 visitantes";
        }

        if (!empty($data['faixa_etaria']) && mb_strlen($data['faixa_etaria']) > 60)
            return "Faixa etária inválida";

        if (!empty($data['objetivo']) && mb_strlen($data['objetivo']) > 300)
            return "Objetivo inválido";

        return null;
    }

    public function store() {

        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!$data || !is_array($data)) {
            http_response_code(400);

            echo json_encode([
                "erro" => "Payload inválido"
            ]);

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
                "INSERT INTO clientes (email, nome_responsavel, nome_instituicao, nome_diretor, telefone)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                   nome_responsavel = VALUES(nome_responsavel),
                   nome_instituicao = VALUES(nome_instituicao),
                   nome_diretor = VALUES(nome_diretor),
                   telefone = VALUES(telefone)
                "
            );

            $stmt->execute([
                $data['email'],
                $data['nome_responsavel'],
                $data['nome_instituicao'] ?? null,
                $data['nome_diretor'] ?? null,
                $data['telefone'] ?? null
            ]);

            $this->model->create($data);

            echo json_encode([
                "mensagem" => "Pedido de visita técnica realizada com sucesso. Notificaremos por email quando for aprovada ou rejeitada."
            ]);

        } catch (Throwable $e) {

            http_response_code(500);

            echo json_encode([
                "erro" => $e->getMessage()
            ]);
        }
    }

    public function index() {

        header('Content-Type: application/json');

        echo json_encode(
            $this->model->getAll()
        );
    }
}
?>