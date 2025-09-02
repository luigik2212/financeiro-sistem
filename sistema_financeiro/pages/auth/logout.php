<?php
/**
 * Página de Logout
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once '../../includes/init.php';

// Destrói a sessão
session_destroy();

// Remove cookies de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redireciona para login
redirect('login.php');
?>

