<?php

class Agendamento {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {

        $sql = "
            INSERT INTO agendamento (
                nome_responsavel,
                email,
                data_reserva,
                quiosque_id,
                qtd_visitantes,
                horario_entrada,
                horario_saida,
                aceite_termos,
                status
            )
            VALUES (
                :nome_responsavel,
                :email,
                :data_reserva,
                :quiosque_id,
                :qtd_visitantes,
                :horario_entrada,
                :horario_saida,
                :aceite_termos,
                'pendente'
            )
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([

            ":nome_responsavel" => $data['nome_responsavel'],
            ":email" => $data['email'],
            ":data_reserva" => $data['data_reserva'],
            ":quiosque_id" => $data['quiosque_id'],
            ":qtd_visitantes" => $data['qtd_visitantes'],
            ":horario_entrada" => $data['horario_entrada'],
            ":horario_saida" => $data['horario_saida'],
            ":aceite_termos" => $data['aceite_termos']

        ]);
    }

    public function getAll() {

        $stmt = $this->conn->query("
            SELECT a.*, c.telefone AS telefone
            FROM agendamento a
            LEFT JOIN clientes c ON a.email = c.email
            ORDER BY a.id DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>