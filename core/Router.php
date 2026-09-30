<?php
/**
 * =========================================
 * SIG-GCM — Sistema Integrado de Gestão
 * Guarda Civil Municipal
 * =========================================
 * SISTEMA DE TESTE — Projeto acadêmico (TG/TC)
 * Desenvolvido 100% por:
 * Autor   : Rafaela Cristina Alcântara da Silva
 * GitHub  : https://github.com/rafaela13c
 * LinkedIn: https://www.linkedin.com/in/rafaela-cas1308/
 * =========================================
 * Este sistema NÃO é oficial e NÃO será
 * utilizado pela GCM. Uso exclusivo para
 * fins de estudo acadêmico.
 * =========================================
 */

// =============================================
// SIG-GCM — Router
// =============================================

class Router {
    private array $routes = [];
    private static ?string $currentUrl = null;

    /**
     * Adiciona uma rota genérica na tabela de roteamento.
     */
    public function add(string $method, string $pattern, string $controller, string $action): void {
        $pattern = trim($pattern, '/');
        $this->routes[] = compact('method', 'pattern', 'controller', 'action');
    }

    /**
     * Atalho para rotas GET
     */
    public function get(string $pattern, string $controller, string $action): void {
        $this->add('GET', $pattern, $controller, $action);
    }

    /**
     * Atalho para rotas POST
     */
    public function post(string $pattern, string $controller, string $action): void {
        $this->add('POST', $pattern, $controller, $action);
    }

    /**
     * Atalho para rotas de qualquer método (GET/POST)
     */
    public function any(string $pattern, string $controller, string $action): void {
        $this->add('ANY', $pattern, $controller, $action);
    }

    /**
     * Obtém e normaliza a URL da requisição atual de forma agnóstica ao servidor web.
     * Totalmente compatível com OpenLiteSpeed, Apache, Nginx e servidor embutido do PHP.
     */
    public static function getUrl(): string {
        if (self::$currentUrl !== null) {
            return self::$currentUrl;
        }

        $raw = '';
        if (!empty($_GET['url'])) {
            $raw = trim($_GET['url'], '/');
        } else {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $path = parse_url($uri, PHP_URL_PATH);
            if ($path === null || $path === false) {
                $path = '';
            }
            $raw = trim(str_replace('\\', '/', $path), '/');
        }

        // Se ainda estiver vazio, verifica PATH_INFO
        if ($raw === '' && !empty($_SERVER['PATH_INFO'])) {
            $raw = trim($_SERVER['PATH_INFO'], '/');
        }

        // Identifica prefixos de subpasta para remover (ex: 'sig-gcm')
        $prefixes = [];

        // 1. A partir do scriptName (ex: /sig-gcm/index.php -> 'sig-gcm')
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir  = trim(str_replace('\\', '/', dirname($scriptName)), '/');
        if (!empty($scriptDir) && $scriptDir !== '.') {
            $prefixes[] = $scriptDir;
        }

        // 2. A partir de BASE_URL se definido (ex: '/sig-gcm' -> 'sig-gcm')
        if (defined('BASE_URL') && BASE_URL !== '') {
            $basePrefix = trim(BASE_URL, '/');
            if (!empty($basePrefix)) {
                $prefixes[] = $basePrefix;
            }
        }

        // Remove prefixos duplicados
        $prefixes = array_unique($prefixes);

        // Remove o prefixo da subpasta do início de $raw se presente
        foreach ($prefixes as $p) {
            if ($raw === $p) {
                $raw = '';
                break;
            }
            if (strpos($raw, $p . '/') === 0) {
                $raw = substr($raw, strlen($p) + 1);
            }
        }

        // Remove index.php se ainda restar
        if (strpos($raw, 'index.php/') === 0) {
            $raw = substr($raw, strlen('index.php/'));
        } elseif ($raw === 'index.php') {
            $raw = '';
        }

        self::$currentUrl = trim($raw, '/');
        return self::$currentUrl;
    }

    /**
     * Processa a requisição atual e despacha para o Controller/Método correspondente.
     */
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $url = self::getUrl();

        // Requisições HTTP HEAD devem corresponder às rotas GET
        $matchMethod = ($method === 'HEAD') ? 'GET' : $method;

        foreach ($this->routes as $route) {
            // Verifica o método HTTP (aceita correspondência exata ou 'ANY')
            if ($route['method'] !== $matchMethod && $route['method'] !== 'ANY') {
                continue;
            }

            // Converter padrão para regex: {id} → (\d+), {slug} → ([a-z0-9\-]+)
            $pattern = preg_replace('/\{id\}/', '(\d+)', $route['pattern']);
            $pattern = preg_replace('/\{[a-z_]+\}/', '([^/]+)', $pattern);
            $pattern = '#^' . $pattern . '$#i';

            if (preg_match($pattern, $url, $matches)) {
                array_shift($matches); // Remover o match completo da expressão

                $controllerFile = ROOT_PATH . '/controllers/' . $route['controller'] . '.php';

                if (!file_exists($controllerFile)) {
                    $this->notFound();
                    return;
                }

                require_once $controllerFile;
                
                if (!class_exists($route['controller'])) {
                    $this->notFound();
                    return;
                }

                $ctrl = new $route['controller']();

                if (!method_exists($ctrl, $route['action'])) {
                    $this->notFound();
                    return;
                }

                // Executa a ação do Controller passando os parâmetros da URL
                $ctrl->{$route['action']}(...$matches);
                return;
            }
        }

        // Se nenhuma rota for encontrada, dispara a página 404
        $this->notFound();
    }

    /**
     * Exibe a página de erro 404
     */
    private function notFound(): void {
        http_response_code(404);
        if (file_exists(ROOT_PATH . '/views/errors/404.php')) {
            include ROOT_PATH . '/views/errors/404.php';
        } else {
            echo '<h1>404 — Página não encontrada</h1>';
        }
    }
}
