<?php

class Router {
    private $routes = [];

    public function get($path, $action, $middleware = null) {
    $this->addRoute('GET', $path, $action, $middleware);
    }

    public function post($path, $action, $middleware = null) {
    $this->addRoute('POST', $path, $action, $middleware);
    }

    public function put($path, $action, $middleware = null) {
    $this->addRoute('PUT', $path, $action, $middleware);
    }

    public function delete($path, $action, $middleware = null) {
    $this->addRoute('DELETE', $path, $action, $middleware);
    }

    private function addRoute($method, $path, $action, $middleware = null) {
    $this->routes[] = [
        'method' => $method,
        'path' => $path,
        'action' => $action,
        'middleware' => $middleware
    ];
}

    public static function route() {
        $router = new self();

        $router->get('/', 'PagesController@home');
        $router->get('/agendamento', 'PagesController@agendamento');
        $router->get('/admin', 'PagesController@admin');
        $router->get('/sobre', 'PagesController@sobre');
        $router->get('/contato', 'PagesController@contato');
        $router->get('/quiz', 'PagesController@quiz');
        $router->get('/jogo', 'PagesController@jogo');
        $router->get('/login', 'PagesController@login');
        $router->get('/login.html', 'PagesController@login');
        $router->get('/visita', 'PagesController@visita'); 
        

        //rotas públicas
        $router->post('/api/auth/login', 'AuthController@login');
        $router->post('/api/auth/logout', 'AuthController@logout');
        $router->get('/api/auth/check', 'AuthController@check');
        

        
        

        //form público para agendamento
        $router->post('/api/agendamentos/enviar', 'AgendamentoController@store');
        $router->post('/api/visita/enviar', 'VisitaTecnicaController@store');
        //form público de contato
        $router->post('/api/contato/enviar', 'ContatoController@store');

        //rotas de admin
        $router->get('/api/agendamentos/listar', 'AgendamentoController@index', 'AuthMiddleware');
        $router->post('/api/agendamentos/aprovar/{id}', 'AgendamentoController@aprovar', 'AuthMiddleware');
        $router->post('/api/agendamentos/rejeitar/{id}', 'AgendamentoController@rejeitar', 'AuthMiddleware');
        $router->post('/api/agendamentos/excluir/{id}', 'AgendamentoController@delete', 'AuthMiddleware');
        
        // rotas para guias (admin)
        $router->post('/api/guias/criar', 'GuiaController@create', 'AuthMiddleware');
        $router->put('/api/guias/atualizar/{id}', 'GuiaController@update', 'AuthMiddleware');
        $router->delete('/api/guias/excluir/{id}', 'GuiaController@delete', 'AuthMiddleware');
        $router->get('/api/guias/listar', 'GuiaController@index', 'AuthMiddleware');

        // rotas para bloqueios (admin)
        $router->get('/api/bloqueios/listar', 'BloqueioController@listar', 'AuthMiddleware');
        $router->post('/api/bloqueios/criar', 'BloqueioController@criar', 'AuthMiddleware');
        $router->delete('/api/bloqueios/excluir/{id}', 'BloqueioController@deletar', 'AuthMiddleware');
        $router->get('/api/bloqueios/datas-comemorativas', 'BloqueioController@gerarDatasComemorativas', 'AuthMiddleware');
        $router->post('/api/bloqueios/bloquear-todos', 'BloqueioController@bloquearTodos', 'AuthMiddleware');

        // rotas admin para mensagens de contato
        $router->get('/api/contato/listar', 'ContatoController@index', 'AuthMiddleware');
        $router->post('/api/contato/status/{id}', 'ContatoController@updateStatus', 'AuthMiddleware');
        $router->delete('/api/contato/excluir/{id}', 'ContatoController@delete', 'AuthMiddleware');

        $router->dispatch();
    }

    public function dispatch() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = $this->normalizeRequestUri($uri);
        $method = $_SERVER['REQUEST_METHOD'] === 'HEAD' ? 'GET' : $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '(\d+)', $route['path']);
            $pattern = '#^' . rtrim($pattern, '/') . '$#';

            if ($route['path'] === '/') {
                $pattern = '#^/$#';
            }

            if (preg_match($pattern, $uri, $matches)) {

                array_shift($matches); // remove match completo
                if ($route['middleware']) {
                require_once __DIR__ . "/../middlewares/{$route['middleware']}.php";
                $route['middleware']::handle();
                }




                try {
                    return $this->callAction($route['action'], $matches);
                } catch (Throwable $e) {
                    return $this->handleException($e, $uri);
                }
            }
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["erro" => "Rota não encontrada", "uri" => $uri]);
    }
    
    

    private function callAction($action, $params) {
        list($controllerName, $method) = explode('@', $action);

        require_once __DIR__ . "/../controllers/$controllerName.php";

        $controller = new $controllerName();

        return call_user_func_array([$controller, $method], $params);
    }

   private function handleException(Throwable $e, $uri) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'erro' => 'Erro interno',
        'mensagem' => 'Ocorreu um problema ao processar a requisição'
    ]);
}

    private function normalizeRequestUri($uri) {
        $uri = str_replace('/parque_ecologico/public', '', $uri);
        $uri = str_replace('/parque_ecologico', '', $uri);

        return $this->normalizeRoutePath($uri);
    }

    private function normalizeRoutePath($path) {
        $path = '/' . trim($path, '/');
        return rtrim($path, '/') ?: '/';
    }
}

?>
