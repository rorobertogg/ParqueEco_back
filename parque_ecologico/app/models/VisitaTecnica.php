<?php

class VisitaTecnica {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {

    $sql = "
        INSERT INTO visita_tecnica (
            nome_instituicao,
            nome_diretor,
            nome_responsavel,
            email,
            data_visita,
            horario_entrada,
            horario_saida,
            qtd_visitantes,
            faixa_etaria,
            objetivo,
            guia_id
        )
        VALUES (
            :nome_instituicao,
            :nome_diretor,
            :nome_responsavel,
            :email,
            :data_visita,
            :horario_entrada,
            :horario_saida,
            :qtd_visitantes,
            :faixa_etaria,
            :objetivo,
            :guia_id
        )
    ";

    $stmt = $this->conn->prepare($sql);

    return $stmt->execute([
        ':nome_instituicao' => $data['nome_instituicao'],
        ':nome_diretor' => $data['nome_diretor'],
        ':nome_responsavel' => $data['nome_responsavel'],
        ':email' => $data['email'],
        ':data_visita' => $data['data_visita'],
        ':horario_entrada' => $data['horario_entrada'],
        ':horario_saida' => $data['horario_saida'],
        ':qtd_visitantes' => $data['qtd_visitantes'],
        ':faixa_etaria' => $data['faixa_etaria'],
        ':objetivo' => $data['objetivo'],
        ':guia_id' => $data['guia_id']
    ]);
}

    public function getAll() {

    $stmt = $this->conn->prepare("
        SELECT 
            vt.*,
            g.nome AS guia_nome,
            c.telefone AS telefone
        FROM visita_tecnica vt
        LEFT JOIN guias g ON vt.guia_id = g.id
        LEFT JOIN clientes c ON vt.email = c.email
        ORDER BY vt.data_visita DESC
    ");

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}