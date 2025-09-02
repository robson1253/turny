<?php

// Garante que a sessão seja iniciada se ainda não estiver.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ========================================
 * FUNÇÕES DE SEGURANÇA CSRF
 * ========================================
 */

if (!function_exists('generate_csrf_token')) {
    /**
     * Gera e armazena um token CSRF na sessão se não existir.
     */
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Gera o campo de input HTML com o token CSRF.
     */
    function csrf_field() {
        generate_csrf_token();
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Verifica o token CSRF recebido via POST.
     * Termina o script se o token for inválido.
     */
    function verify_csrf_token() {
        if (
            !isset($_POST['csrf_token']) || 
            !isset($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            http_response_code(403);
            die('Erro de validação de segurança (CSRF Token inválido). A sua sessão pode ter expirado. Por favor, volte e tente novamente.');
        }
        unset($_SESSION['csrf_token']);
    }
}

/**
 * ========================================
 * FUNÇÕES DE MENSAGEM FLASH
 * ========================================
 */

if (!function_exists('flash')) {
    /**
     * Define uma mensagem flash na sessão.
     */
    function flash(string $message, string $type = 'success') {
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
    }
}

if (!function_exists('display_flash_message')) {
    /**
     * Exibe a mensagem flash se ela existir e a remove da sessão.
     */
    function display_flash_message() {
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            $message = htmlspecialchars($flash['message']);
            $type = htmlspecialchars($flash['type']);
            
            echo "<div class='flash-message {$type}'>{$message}</div>";
            
            unset($_SESSION['flash_message']);
        }
    }
}

/**
 * ========================================
 * FUNÇÕES AUXILIARES DE ROTA E RESPOSTA
 * ========================================
 */

if (!function_exists('is_ajax_request')) {
    /**
     * Verifica se a requisição atual é uma chamada AJAX.
     */
    function is_ajax_request() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

if (!function_exists('json_response')) {
    /**
     * Envia uma resposta HTTP padronizada em formato JSON e encerra o script.
     */
    function json_response($data, $statusCode = 200) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
}

if (!function_exists('redirect_back')) {
    /**
     * Redireciona o usuário para a página anterior ou para uma padrão.
     */
    function redirect_back($default = '/') {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $default));
        exit();
    }
}