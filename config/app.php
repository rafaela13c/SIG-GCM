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
// SIG-GCM — Configurações Gerais da Aplicação
// =============================================

define('APP_NAME', 'SIG-GCM');
define('APP_SUBTITLE', 'Sistema Integrado de Gestão da Guarda Civil Municipal');
define('APP_CITY', 'Eldorado dos Ventos - SP');
define('APP_VERSION', '1.2.0');

// Metadados de Autoria (Fingerprint Digital — Camada 2 Anti-Plágio)
define('APP_AUTHOR',         'Rafaela Cristina Alcântara da Silva');
define('APP_AUTHOR_GITHUB',  'https://github.com/rafaela13c');
define('APP_AUTHOR_LINKEDIN','https://www.linkedin.com/in/rafaela-cas1308/');
define('APP_LICENSE',        'CC BY-NC-SA 4.0');
define('APP_FINGERPRINT',    'SIG-GCM-RCAS-2025-ORIGINAL-PHP8-MVC-TG-TC');

// URL base da aplicação (sem barra no final)
// Suporta detecção automática para Raiz (/) ou Subpastas (/sig-gcm) em OpenLiteSpeed, Apache, Nginx e Docker.
// Também permite override via variável de ambiente BASE_URL ou APP_URL.
if (!defined('BASE_URL')) {
    if (getenv('BASE_URL') !== false && getenv('BASE_URL') !== '') {
        define('BASE_URL', rtrim(getenv('BASE_URL'), '/'));
    } elseif (getenv('APP_URL') !== false && getenv('APP_URL') !== '') {
        $parsedPath = parse_url(getenv('APP_URL'), PHP_URL_PATH);
        define('BASE_URL', rtrim($parsedPath ?? '', '/'));
    } elseif (php_sapi_name() === 'cli') {
        define('BASE_URL', '');
    } else {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir  = str_replace('\\', '/', dirname($scriptName));
        if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
            define('BASE_URL', '');
        } else {
            define('BASE_URL', rtrim($scriptDir, '/'));
        }
    }
}

// Diretório raiz
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

// Sessão
define('SESSION_LIFETIME', 28800);    // 8 horas em segundos
define('SESSION_IDLE_TIMEOUT', 1800); // 30 min de inatividade

// Segurança
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos em segundos
define('PASSWORD_MIN_LENGTH', 8);
define('BCRYPT_COST', 12);

// Paginação
define('ITEMS_PER_PAGE', 20);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Reporting de erros (desativar em produção)
// error_reporting(0);
// ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('display_errors', 1);
