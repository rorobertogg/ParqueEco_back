<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Bloqueio.php';

class BloqueioController {

    private $conn;
    private $model;

    public function __construct() {
        $this->conn = Database::connect();
        $this->model = new Bloqueio($this->conn);
    }

    // Datas comemorativas fixas do Brasil
    private function sanitizeText($value) {
        return trim(strip_tags($value));
    }

    private function isValidDate($value) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function getDatasComemorativas() {
        $ano = date('Y');
        
        return [
            [
                'data' => "$ano-01-01",
                'nome' => 'Ano Novo',
                'tipo' => 'feriado'
            ],
            [
                'data' => "$ano-04-21",
                'nome' => 'Tiradentes',
                'tipo' => 'feriado'
            ],
            [
                'data' => "$ano-05-01",
                'nome' => 'Dia do Trabalho',
                'tipo' => 'feriado'
            ],
            [
                'data' => "$ano-09-07",
                'nome' => 'Independência do Brasil',
                'tipo' => 'feriado'
            ],
            [
                'data' => "$ano-10-12",
                'nome' => 'Nossa Senhora Aparecida',
                'tipo' => 'feriado'
            ],
            [
                'data' => "$ano-11-02",
                'nome' => 'Finados',
                'tipo' => 'feriado'
            ],
            [
                'data' => "$ano-11-15",
                'nome' => 'Proclamação da República',
                'tipo' => 'feriado'
            ],
            [
                'data' => "$ano-11-20",
                'nome' => 'Consciência Negra',
                'tipo' => 'feriado'
            ],
            [
                'data' => "$ano-12-25",
                'nome' => 'Natal',
                'tipo' => 'feriado'
            ]
        ];
    }

    // Calcula a Sexta-feira Santa (easter)
    private function getSextaFeiraSanta($ano) {
        $easter = easter_date($ano);
        $timestamp = strtotime('-2 days', $easter);
        return date('Y-m-d', $timestamp);
    }

    // Calcula Corpus Christi (60 dias após Páscoa)
    private function getCorpusChristi($ano) {
        $easter = easter_date($ano);
        $timestamp = strtotime('+60 days', $easter);
        return date('Y-m-d', $timestamp);
    }

    public function listar() {
        header('Content-Type: application/json');
        echo json_encode($this->model->getAll());
    }

    public function criar() {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents("php://input"), true);

        $data['data_bloqueada'] = $this->sanitizeText($data['data_bloqueada'] ?? '');
        $data['motivo'] = $this->sanitizeText($data['motivo'] ?? '');

        if (empty($data['data_bloqueada']) || empty($data['motivo'])) {
            http_response_code(422);
            echo json_encode(['erro' => 'Data e motivo são obrigatórios']);
            return;
        }

        if (!$this->isValidDate($data['data_bloqueada'])) {
            http_response_code(422);
            echo json_encode(['erro' => 'Formato de data inválido']);
            return;
        }

        if (mb_strlen($data['motivo']) > 255) {
            http_response_code(422);
            echo json_encode(['erro' => 'Motivo muito longo']);
            return;
        }

        if ($this->model->existeDataBloqueada($data['data_bloqueada'])) {
            http_response_code(422);
            echo json_encode(['erro' => 'Esta data já está bloqueada']);
            return;
        }

        try {
            if ($this->model->create($data['data_bloqueada'], $data['motivo'])) {
                echo json_encode(['mensagem' => 'Data bloqueada com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao bloquear data']);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
        }
    }

    public function deletar($id) {
        header('Content-Type: application/json; charset=utf-8');

        $id = (int) $id;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID inválido']);
            return;
        }

        try {
            if ($this->model->delete($id)) {
                echo json_encode(['mensagem' => 'Bloqueio removido com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao remover bloqueio']);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
        }
    }

    public function gerarDatasComemorativas() {
        header('Content-Type: application/json');

        try {
            $ano = intval($_GET['ano'] ?? date('Y'));
            
            // Obter datas fixas
            $datas = $this->getDatasComemorativas();
            
            // Adicionar datas móveis
            $datas[] = [
                'data' => $this->getSextaFeiraSanta($ano),
                'nome' => 'Sexta-feira Santa',
                'tipo' => 'feriado'
            ];
            
            $datas[] = [
                'data' => $this->getCorpusChristi($ano),
                'nome' => 'Corpus Christi',
                'tipo' => 'feriado'
            ];

            // Atualizar ano nas datas fixas
            foreach ($datas as &$data) {
                $data['data'] = substr_replace($data['data'], $ano, 0, 4);
            }

            // Remover duplicatas
            $datas = array_unique($datas, SORT_REGULAR);

            // Ordenar por data
            usort($datas, function($a, $b) {
                return strcmp($a['data'], $b['data']);
            });

            // Contar quantas já estão no banco
            $comoBloqueado = 0;
            $jaBloqueado = [];

            foreach ($datas as &$data) {
                if ($this->model->existeDataBloqueada($data['data'])) {
                    $data['ja_bloqueado'] = true;
                    $comoBloqueado++;
                    $jaBloqueado[] = $data;
                } else {
                    $data['ja_bloqueado'] = false;
                }
            }

            echo json_encode([
                'datas' => $datas,
                'total' => count($datas),
                'ja_bloqueados' => $comoBloqueado,
                'lista_bloqueados' => $jaBloqueado
            ]);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
        }
    }

    public function bloquearTodos() {
        header('Content-Type: application/json');

        try {
            $ano = intval($_POST['ano'] ?? date('Y'));
            
            // Obter datas fixas
            $datas = $this->getDatasComemorativas();
            
            // Adicionar datas móveis
            $datas[] = [
                'data' => $this->getSextaFeiraSanta($ano),
                'nome' => 'Sexta-feira Santa',
                'tipo' => 'feriado'
            ];
            
            $datas[] = [
                'data' => $this->getCorpusChristi($ano),
                'nome' => 'Corpus Christi',
                'tipo' => 'feriado'
            ];

            // Atualizar ano
            foreach ($datas as &$data) {
                $data['data'] = substr_replace($data['data'], $ano, 0, 4);
            }

            $adicionadas = 0;
            foreach ($datas as $data) {
                if (!$this->model->existeDataBloqueada($data['data'])) {
                    $this->model->create($data['data'], $data['nome']);
                    $adicionadas++;
                }
            }

            echo json_encode([
                'mensagem' => "Adicionadas $adicionadas datas comemorativas ao bloqueio"
            ]);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
        }
    }
}
?>
