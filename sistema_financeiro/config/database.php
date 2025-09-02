<?php
/**
 * Configurações do Banco de Dados
 * Sistema Financeiro
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'u850382502_financeiro');  
define('DB_USER', 'u850382502_admin');      
define('DB_PASS', 'Porto2025.');       
define('DB_CHARSET', 'utf8mb4');

// Configurações do sistema
define('SITE_URL', 'http://localhost');
define('SITE_NAME', 'Sistema Financeiro');
define('TIMEZONE', 'America/Sao_Paulo');

// Configurações de sessão
define('SESSION_NAME', 'sistema_financeiro_session');
define('SESSION_LIFETIME', 3600); // 1 hora

// Configurações de segurança
define('HASH_ALGORITHM', 'sha256');
define('ENCRYPTION_KEY', 'sua_chave_secreta_aqui_mude_em_producao');

// Definir timezone
date_default_timezone_set(TIMEZONE);

// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
?>

