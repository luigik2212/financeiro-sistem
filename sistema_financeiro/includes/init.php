<?php
/**
 * Arquivo de Inicialização
 * Sistema Financeiro
 */

// Evita acesso direto
if (!defined('SISTEMA_FINANCEIRO')) {
    die('Acesso negado');
}

// Carrega configurações
require_once __DIR__ . '/../config/database.php';

// Carrega funções auxiliares
require_once __DIR__ . '/functions.php';

// Carrega classes
require_once __DIR__ . '/../classes/Database.php';

// Conecta ao banco de dados
try {
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}

// Verifica se as tabelas existem (instalação)
function checkInstallation() {
    global $db;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'usuarios'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Se não estiver instalado, redireciona para instalação
if (!checkInstallation() && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    redirect('install.php');
}
?>

