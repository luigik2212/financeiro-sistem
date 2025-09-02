<?php
/**
 * Middleware de Verificação de Autenticação
 * Sistema Financeiro
 */

// Evita acesso direto
if (!defined('SISTEMA_FINANCEIRO')) {
    die('Acesso negado');
}

// Verifica se usuário está logado
if (!isLoggedIn()) {
    // Salva a URL atual para redirecionamento após login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redireciona para login
    redirect('pages/auth/login.php');
}

// Verifica se a sessão não expirou
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
    // Sessão expirada
    session_destroy();
    showError('Sua sessão expirou. Faça login novamente.');
    redirect('pages/auth/login.php');
}

// Atualiza o tempo da sessão
$_SESSION['login_time'] = time();

// Função para verificar se usuário tem permissão de admin
function requireAdmin() {
    if (!isAdmin()) {
        showError('Acesso negado. Você não tem permissão para acessar esta página.');
        redirect('index.php');
    }
}
?>

