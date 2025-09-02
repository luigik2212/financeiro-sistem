<?php
/**
 * Excluir Conta
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once '../../includes/init.php';
require_once '../../includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$conta_id = (int)($_GET['id'] ?? 0);

// Busca conta
try {
    $query = "SELECT * FROM contas WHERE id = ? AND usuario_id = ?";
    $conta = $database->selectOne($query, [$conta_id, $user_id]);
    
    if (!$conta) {
        showError('Conta não encontrada.');
        redirect('index.php');
    }
    
} catch (Exception $e) {
    showError('Erro ao carregar conta: ' . $e->getMessage());
    redirect('index.php');
}

// Exclui conta
try {
    $database->beginTransaction();
    
    // Exclui histórico de pagamentos
    $query = "DELETE FROM historico_pagamentos WHERE conta_id = ?";
    $database->execute($query, [$conta_id]);
    
    // Exclui a conta
    $query = "DELETE FROM contas WHERE id = ? AND usuario_id = ?";
    $database->execute($query, [$conta_id, $user_id]);
    
    $database->commit();
    
    showSuccess('Conta "' . htmlspecialchars($conta['descricao']) . '" excluída com sucesso!');
    
} catch (Exception $e) {
    $database->rollback();
    showError('Erro ao excluir conta: ' . $e->getMessage());
}

redirect('index.php');
?>

