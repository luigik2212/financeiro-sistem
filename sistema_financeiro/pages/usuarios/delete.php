<?php
/**
 * Excluir Usuário
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once '../../includes/init.php';
require_once '../../includes/auth_check.php';

// Verifica se é admin
requireAdmin();

$user_id = (int)($_GET['id'] ?? 0);

// Não permite excluir o próprio usuário
if ($user_id == $_SESSION['user_id']) {
    showError('Você não pode excluir seu próprio usuário.');
    redirect('index.php');
}

// Busca usuário
try {
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $usuario = $database->selectOne($query, [$user_id]);
    
    if (!$usuario) {
        showError('Usuário não encontrado.');
        redirect('index.php');
    }
    
} catch (Exception $e) {
    showError('Erro ao carregar usuário: ' . $e->getMessage());
    redirect('index.php');
}

// Verifica se usuário tem contas associadas
try {
    $query = "SELECT COUNT(*) as total FROM contas WHERE usuario_id = ?";
    $contas_count = $database->selectOne($query, [$user_id])['total'];
    
    if ($contas_count > 0) {
        showError('Não é possível excluir este usuário pois ele possui ' . $contas_count . ' conta(s) associada(s). Primeiro exclua ou transfira as contas.');
        redirect('index.php');
    }
    
} catch (Exception $e) {
    showError('Erro ao verificar contas do usuário: ' . $e->getMessage());
    redirect('index.php');
}

// Exclui usuário
try {
    $database->beginTransaction();
    
    // Exclui histórico de pagamentos do usuário
    $query = "DELETE FROM historico_pagamentos WHERE usuario_id = ?";
    $database->execute($query, [$user_id]);
    
    // Exclui o usuário
    $query = "DELETE FROM usuarios WHERE id = ?";
    $database->execute($query, [$user_id]);
    
    $database->commit();
    
    showSuccess('Usuário "' . htmlspecialchars($usuario['nome']) . '" excluído com sucesso!');
    
} catch (Exception $e) {
    $database->rollback();
    showError('Erro ao excluir usuário: ' . $e->getMessage());
}

redirect('index.php');
?>

