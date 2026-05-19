<?php

class Mensagem {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {

        $sql = "
            INSERT INTO mensagens (
                nome,
                email,
                telefone,
                assunto,
                mensagem,
                lida,
                respondida
            ) VALUES (
                :nome,
                :email,
                :telefone,
                :assunto,
                :mensagem,
                0,
                0
            )
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':nome' => $data['nome'] ?? null,
            ':email' => $data['email'] ?? null,
            ':telefone' => $data['telefone'] ?? null,
            ':assunto' => $data['assunto'] ?? null,
            ':mensagem' => $data['mensagem'] ?? null
        ]);
    }

    public function getAll($filters = []) {
        $sql = "SELECT * FROM mensagens";
        $conditions = [];
        $params = [];

        if (isset($filters['lida'])) {
            $conditions[] = 'lida = ?';
            $params[] = $filters['lida'] ? 1 : 0;
        }

        if (isset($filters['respondida'])) {
            $conditions[] = 'respondida = ?';
            $params[] = $filters['respondida'] ? 1 : 0;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY criado_em DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $data) {
        $fields = [];
        $params = [];

        if (isset($data['lida'])) {
            $fields[] = 'lida = ?';
            $params[] = $data['lida'] ? 1 : 0;
        }

        if (isset($data['respondida'])) {
            $fields[] = 'respondida = ?';
            $params[] = $data['respondida'] ? 1 : 0;
        }

        if (!$fields) {
            return false;
        }

        $params[] = (int) $id;
        $sql = 'UPDATE mensagens SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM mensagens WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }
}

?>
