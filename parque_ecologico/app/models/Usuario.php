<?php

class Usuario {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function criar($data) {

        $sql = "
            INSERT INTO usuarios (
                nome,
                email,
                senha
            )
            VALUES (
                :nome,
                :email,
                :senha
            )
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':nome' => $data['nome'],
            ':email' => $data['email'],
            ':senha' => password_hash(
                $data['senha'],
                PASSWORD_DEFAULT
            )
        ]);
    }

    public function buscarPorEmail($email) {

        $stmt = $this->conn->prepare("
            SELECT *
            FROM usuarios
            WHERE email = ?
        ");

        $stmt->execute([$email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}