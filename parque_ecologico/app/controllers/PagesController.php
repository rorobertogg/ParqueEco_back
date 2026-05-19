<?php

require_once __DIR__ . '/../core/Session.php';
Session::start();

require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/../models/Agendamento.php';
require_once __DIR__ . '/../models/Guia.php';
require_once __DIR__ . '/../models/VisitaTecnica.php';
require_once __DIR__ . '/../models/Mensagem.php';

class PagesController {

    public function home() {
        View::render('home');
    }

    public function login() {
        View::render('login');
    }

    public function cadastro() {
        View::render('cadastro');
    }

    /*
    |--------------------------------------------------------------------------
    | AGENDAMENTO (qualquer usuário logado)
    |--------------------------------------------------------------------------
    */

    public function agendamento() {

        View::render('agendamento');
    }

    /*
    |--------------------------------------------------------------------------
    | VISITA TÉCNICA (qualquer usuário logado)
    |--------------------------------------------------------------------------
    */

    public function visita() {

       

        $conn = Database::connect();

        $guiaModel = new Guia($conn);

        $guias = $guiaModel->listarAtivos();

        View::render('visita', [
            'guias' => $guias
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN (somente admin)
    |--------------------------------------------------------------------------
    */

    public function admin() {     
        

       

        if (
            empty($_SESSION['usuario_id']) ||
            strtolower(trim($_SESSION['tipo'])) !== 'admin'
        ) {
            header('Location: /parque_ecologico/login');
            exit;
        }

        $conn = Database::connect();

        $agendamentoModel = new Agendamento($conn);
        $visitaModel = new VisitaTecnica($conn);

        $agendamentos = $agendamentoModel->getAll();
        $visitas = $visitaModel->getAll();
        $mensagemModel = new Mensagem($conn);
        $mensagens = $mensagemModel->getAll();

        foreach ($agendamentos as &$a) {
            $a['tipo'] = 'agendamento';
        }

        foreach ($visitas as &$v) {
            $v['tipo'] = 'visita';
        }

        foreach ($mensagens as &$m) {
            $m['tipo'] = 'contato';
            // para manter compatibilidade com ordenação e filtros,
            // colocamos uma data no mesmo campo usado nos outros registros
            $m['data_reserva'] = $m['criado_em'] ?? null;
            $m['nome_responsavel'] = $m['nome'] ?? 'Mensagem';
        }

        $registros = array_merge($agendamentos, $visitas, $mensagens);

        usort($registros, function($a, $b){
            return strtotime(
                $b['data_reserva'] ?? $b['data_visita']
            ) - strtotime(
                $a['data_reserva'] ?? $a['data_visita']
            );
        });

        require_once __DIR__ . '/../helpers/csrf.php';
        $csrfToken = gerarCsrfToken();

        View::render('admin', [
            'registros' => $registros,
            'csrfToken' => $csrfToken
        ]);
    }

    public function sobre() {
        View::render('sobre');
    }

    public function contato() {
        View::render('contato');
    }

    public function quiz() {
        View::render('quiz', [
            'title' => 'Quiz Ecológico'
        ]);
    }

    public function jogo() {
        View::render('jogo', [
            'title' => 'Caça-palavras Ecológico'
        ]);
    }
}