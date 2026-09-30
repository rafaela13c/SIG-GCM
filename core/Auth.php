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
 */// =============================================
// SIG-GCM — Classe de Autenticação
// =============================================

class Auth {
    private static Database $db;

    public static function init(): void {
        self::$db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => false, // true em produção com HTTPS
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
    }

    // =========================================
    // AUTENTICAÇÃO
    // =========================================

    public static function attempt(string $username, string $password): array {
        // Verificar se o banco de dados está acessível
        if (!Database::isConnected()) {
            return ['success' => false, 'message' => 'Erro: Não foi possível conectar ao banco de dados. Configure as credenciais em config/database.php.'];
        }

        // Verificar bloqueio por tentativas
        $lockKey = 'login_attempts_' . md5($username);
        if (isset($_SESSION[$lockKey])) {
            $data = $_SESSION[$lockKey];
            if ($data['count'] >= MAX_LOGIN_ATTEMPTS) {
                $elapsed = time() - $data['last_attempt'];
                if ($elapsed < LOGIN_LOCKOUT_TIME) {
                    $remaining = ceil((LOGIN_LOCKOUT_TIME - $elapsed) / 60);
                    return ['success' => false, 'message' => "Conta bloqueada. Aguarde {$remaining} minuto(s)."];
                } else {
                    unset($_SESSION[$lockKey]);
                }
            }
        }

        // Buscar usuário
        $user = self::$db->fetchOne(
            "SELECT * FROM usuarios WHERE username = ? AND ativo = 1",
            [$username]
        );

        // Registrar tentativa de login na tabela
        self::logLoginAttempt($username, $user ? $user['id'] : null, false);

        if (!$user || !password_verify($password, $user['senha'])) {
            // Incrementar contador de tentativas
            if (!isset($_SESSION[$lockKey])) {
                $_SESSION[$lockKey] = ['count' => 0, 'last_attempt' => time()];
            }
            $_SESSION[$lockKey]['count']++;
            $_SESSION[$lockKey]['last_attempt'] = time();

            $remaining = MAX_LOGIN_ATTEMPTS - $_SESSION[$lockKey]['count'];
            $msg = $remaining > 0
                ? "Usuário ou senha incorretos. {$remaining} tentativa(s) restante(s)."
                : "Conta bloqueada por " . (LOGIN_LOCKOUT_TIME / 60) . " minutos.";

            return ['success' => false, 'message' => $msg];
        }

        // Login bem-sucedido — limpar contador
        unset($_SESSION[$lockKey]);

        // Registrar login com sucesso
        self::logLoginAttempt($username, $user['id'], true);

        // Iniciar sessão
        session_regenerate_id(true);
        $_SESSION['user_id']        = $user['id'];
        $_SESSION['user_nome']       = $user['nome'];
        $_SESSION['user_username']   = $user['username'];
        $_SESSION['user_perfil']     = $user['perfil'];
        $_SESSION['user_foto']       = $user['foto'] ?? null;
        $_SESSION['senha_temporaria']= (bool)($user['primeiro_acesso'] ?? 0);
        $_SESSION['login_time']      = time();
        $_SESSION['last_activity']   = time();

        return [
            'success'          => true,
            'senha_temporaria' => (bool)$user['senha_temporaria']
        ];
    }

    public static function logout(): void {
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    public static function check(): bool {
        if (!isset($_SESSION['user_id'])) return false;

        // Timeout de inatividade
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_IDLE_TIMEOUT) {
                session_destroy();
                return false;
            }
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function requireAuth(): void {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        // Forçar troca de senha temporária (Desativado no Modo de Teste)
        /*
        if ($_SESSION['senha_temporaria']) {
            $currentUri = $_SERVER['REQUEST_URI'];
            if (!str_contains($currentUri, '/alterar-senha')) {
                header('Location: ' . BASE_URL . '/alterar-senha');
                exit;
            }
        }
        */
    }

    public static function requirePermission(string $permissao): void {
        self::requireAuth();
        if (!self::can($permissao)) {
            http_response_code(403);
            include ROOT_PATH . '/views/errors/403.php';
            exit;
        }
    }

    // =========================================
    // SISTEMA DE PERMISSÕES
    // =========================================

    // Mapa de permissões padrão por perfil
    private static array $defaultPermissions = [
        'operador' => [
            'dashboard', 'taloes.criar', 'taloes.ver_proprios',
            'frota.checklists', 'frota.abastecimentos',
            'escala.ver_propria', 'troca.solicitar', 'banco_horas.ver_proprio',
            'ronda_escolar.registrar', 'maria_penha.visitar',
            'rh.ver_proprio', 'materiais.ver_acautelados',
            'treinamentos.ver_proprios', 'comunicados.ver',
        ],
        'inspetor' => [
            'dashboard', 'taloes.criar', 'taloes.ver_todos', 'taloes.editar_parcial',
            'frota.checklists', 'frota.abastecimentos',
            'escala.criar', 'escala.editar', 'escala.ver_todos',
            'troca.aprovar', 'banco_horas.lancar',
            'ronda_escolar.registrar', 'ronda_escolar.ver_todos',
            'maria_penha.visitar', 'maria_penha.cadastrar_vitima',
            'rh.ver_todos', 'materiais.movimentar',
            'treinamentos.ver_proprios', 'comunicados.ver',
            'relatorios.basicos', 'alertas.ver',
        ],
        'subcomandante' => [
            'dashboard', 'taloes.criar', 'taloes.ver_todos', 'taloes.editar_todos',
            'frota.checklists', 'frota.abastecimentos', 'frota.cadastrar_viaturas',
            'escala.criar', 'escala.editar', 'escala.ver_todos',
            'troca.aprovar', 'banco_horas.lancar',
            'ronda_escolar.registrar', 'ronda_escolar.ver_todos',
            'maria_penha.visitar', 'maria_penha.cadastrar_vitima',
            'rh.ver_todos', 'rh.editar',
            'materiais.movimentar', 'materiais.cadastrar',
            'treinamentos.cadastrar_cursos',
            'comunicados.criar', 'relatorios.basicos', 'relatorios.gerenciais',
            'usuarios.criar', 'alertas.ver',
        ],
        'comandante' => [
            'dashboard', 'taloes.criar', 'taloes.ver_todos', 'taloes.editar_todos',
            'frota.checklists', 'frota.abastecimentos', 'frota.cadastrar_viaturas',
            'escala.criar', 'escala.editar', 'escala.ver_todos',
            'troca.aprovar', 'banco_horas.lancar',
            'ronda_escolar.registrar', 'ronda_escolar.ver_todos',
            'maria_penha.visitar', 'maria_penha.cadastrar_vitima',
            'rh.ver_todos', 'rh.editar',
            'materiais.movimentar', 'materiais.cadastrar',
            'treinamentos.cadastrar_cursos',
            'comunicados.criar', 'relatorios.basicos', 'relatorios.gerenciais',
            'usuarios.criar', 'usuarios.alterar_permissoes',
            'auditoria.ver', 'corregedoria.ver',
            'alertas.criar', 'alertas.ver',
        ],
        'corregedor' => [
            'dashboard', 'taloes.ver_todos', 'frota.ver',
            'escala.ver_todos', 'ronda_escolar.ver_todos',
            'maria_penha.ver',
            'rh.ver_todos', 'materiais.ver_acautelados',
            'treinamentos.ver_proprios',
            'relatorios.basicos', 'relatorios.gerenciais',
            'corregedoria.criar', 'corregedoria.editar', 'corregedoria.ver',
            'auditoria.ver', 'comunicados.ver',
        ],
        'admin' => ['*'], // Acesso total
    ];

    /** Retorna as permissões padrão de um perfil específico (para uso em Views). */
    public static function defaultsForPerfil(string $perfil): array {
        return self::$defaultPermissions[$perfil] ?? [];
    }

    public static function can(string $permissao): bool {
        if (!isset($_SESSION['user_id'])) return false;

        $perfil = $_SESSION['user_perfil'];

        // Admin tem tudo
        if ($perfil === 'admin') return true;

        // Verificar permissão granular individual (sobrescreve o padrão)
        try {
            $db   = Database::getInstance();
            $perm = $db->fetchOne(
                "SELECT permitido FROM permissoes_usuario WHERE usuario_id = ? AND recurso = ?",
                [$_SESSION['user_id'], $permissao]
            );
            if ($perm !== null) return (bool)$perm['permitido'];
        } catch (RuntimeException $e) {
            // Se o banco cair, ignoramos as permissões granulares e usamos as padrões da sessão
        }

        // Verificar permissão do perfil padrão
        $defaults = self::$defaultPermissions[$perfil] ?? [];
        return in_array($permissao, $defaults) || in_array('*', $defaults);
    }

    public static function user(): ?array {
        if (!isset($_SESSION['user_id'])) return null;
        return [
            'id'               => $_SESSION['user_id'],
            'nome'             => $_SESSION['user_nome'],
            'username'         => $_SESSION['user_username'],
            'perfil'           => $_SESSION['user_perfil'],
            'foto'             => $_SESSION['user_foto'],
            'senha_temporaria' => $_SESSION['senha_temporaria'],
        ];
    }

    public static function id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function perfil(): ?string {
        return $_SESSION['user_perfil'] ?? null;
    }

    // =========================================
    // GERAÇÃO DE SENHAS
    // =========================================

    public static function gerarSenhaTemporaria(): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $senha = '';
        for ($i = 0; $i < 10; $i++) {
            $senha .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $senha;
    }

    public static function hashSenha(string $senha): string {
        return password_hash($senha, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    }

    public static function alterarSenha(int $userId, string $novaSenha): bool {
        $db   = Database::getInstance();
        $hash = self::hashSenha($novaSenha);
        $rows = $db->execute(
            "UPDATE usuarios SET senha = ?, primeiro_acesso = 0, updated_at = NOW() WHERE id = ?",
            [$hash, $userId]
        );
        if ($rows > 0) {
            $_SESSION['senha_temporaria'] = false;
        }
        return $rows > 0;
    }

    // =========================================
    // CSRF
    // =========================================

    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }

    // =========================================
    // LOG DE LOGIN
    // =========================================

    private static function logLoginAttempt(string $username, ?int $userId, bool $sucesso): void {
        try {
            $db = Database::getInstance();
            // Geolocalização básica via IP (futuramente pode integrar API)
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $db->execute(
                "INSERT INTO login_log (usuario_id, username_tentado, sucesso, ip, user_agent, tentado_em)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$userId, $username, $sucesso ? 1 : 0, $ip, $ua]
            );
        } catch (Exception $e) {
            // Silencioso — não impede o fluxo de login
        }
    }
}
