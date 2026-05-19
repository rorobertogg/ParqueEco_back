<?php

class View {
    private static $viewPath = __DIR__ . '/../views/';

    /**
     * Renderiza uma view HTML com layout
     * 
     * @param string $page - Nome da página (app/views/pages/nomepage.html)
     * @param array $data - Variáveis disponíveis na view
     * @param string $layout - Layout a usar (padrão: base.html)
     */
    public static function render($page, $data = [], $layout = 'base') {
        // Extrair variáveis para o escopo local
        extract($data);

        // Capturar o conteúdo da página
        ob_start();
        include self::$viewPath . 'pages/' . $page . '.html';
        $content = ob_get_clean();

        // Incluir o layout
        include self::$viewPath . 'layouts/' . $layout . '.html';
    }

    /**
     * Renderiza um partial (fragmento)
     */
    public static function partial($name, $data = []) {
        extract($data);
        include self::$viewPath . 'partials/' . $name . '.html';
    }

    /**
     * Renderiza um componente reutilizável
     */
    public static function component($name, $data = []) {
        extract($data);
        include self::$viewPath . 'components/' . $name . '.html';
    }
}

?>
