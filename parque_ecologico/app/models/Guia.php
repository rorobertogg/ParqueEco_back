<?php

class Guia {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listarAtivos() {

        $stmt = $this->conn->prepare("
            SELECT id, nome, especialidade
            FROM guias
            WHERE ativo = 1
            ORDER BY nome
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}