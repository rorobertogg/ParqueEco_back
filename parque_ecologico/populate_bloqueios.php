<?php
/**
 * Script para popular a tabela bloqueios com datas comemorativas do Brasil
 * Execute via terminal: php populate_bloqueios.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/Bloqueio.php';

try {
    $conn = Database::connect();
    $bloqueio = new Bloqueio($conn);
    
    $ano = date('Y');
    echo "Carregando datas comemorativas de $ano...\n";
    
    // Datas fixas
    $datas = [
        '2026-01-01' => 'Ano Novo',
        '2026-04-21' => 'Tiradentes',
        '2026-05-01' => 'Dia do Trabalho',
        '2026-09-07' => 'Independência do Brasil',
        '2026-10-12' => 'Nossa Senhora Aparecida',
        '2026-11-02' => 'Finados',
        '2026-11-15' => 'Proclamação da República',
        '2026-11-20' => 'Consciência Negra',
        '2026-12-25' => 'Natal'
    ];
    
    // Datas móveis para 2026
    $easter = easter_date(2026);
    $sexta = strtotime('-2 days', $easter);
    $corpus = strtotime('+60 days', $easter);
    
    $datas[date('Y-m-d', $sexta)] = 'Sexta-feira Santa';
    $datas[date('Y-m-d', $corpus)] = 'Corpus Christi';
    
    $adicionadas = 0;
    $puladas = 0;
    
    foreach ($datas as $data => $motivo) {
        if ($bloqueio->existeDataBloqueada($data)) {
            echo "⊘ Data $data ($motivo) já existe\n";
            $puladas++;
            continue;
        }
        
        if ($bloqueio->create($data, $motivo)) {
            echo "✓ Data $data ($motivo) adicionada\n";
            $adicionadas++;
        } else {
            echo "✗ Erro ao adicionar $data ($motivo)\n";
        }
    }
    
    echo "\n=== Resultado ===\n";
    echo "Adicionadas: $adicionadas\n";
    echo "Puladas (já existem): $puladas\n";
    echo "Total de datas: " . count($datas) . "\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
