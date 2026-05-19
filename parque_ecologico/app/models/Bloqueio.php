<?php

class Bloqueio {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data_bloqueada, $motivo) {

        $sql = "INSERT INTO bloqueios (data_bloqueada, motivo) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        return $stmt->execute([$data_bloqueada, $motivo]);
    }

    public function getAll() {
        $stmt = $this->conn->query("
            SELECT * FROM bloqueios 
            ORDER BY data_bloqueada ASC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM bloqueios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getBloqueiosPorAno($ano) {
        $stmt = $this->conn->prepare("
            SELECT * FROM bloqueios 
            WHERE YEAR(data_bloqueada) = ?
            ORDER BY data_bloqueada ASC
        ");
        $stmt->execute([$ano]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existeDataBloqueada($data) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM bloqueios 
            WHERE data_bloqueada = ?
        ");
        $stmt->execute([$data]);
        
        return $stmt->fetchColumn() > 0;
    }

    public function findByDate($data) {
        $stmt = $this->conn->prepare("
            SELECT * FROM bloqueios
            WHERE data_bloqueada = ?
            LIMIT 1
        ");
        $stmt->execute([$data]);

        $bloqueio = $stmt->fetch(PDO::FETCH_ASSOC);
        return $bloqueio ?: false;
    }
}
?>
