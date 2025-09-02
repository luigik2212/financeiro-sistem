<?php
/**
 * Marcar Conta como Paga
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
    
    if ($conta['status'] === 'pago') {
        showError('Esta conta já está marcada como paga.');
        redirect('index.php');
    }
    
} catch (Exception $e) {
    showError('Erro ao carregar conta: ' . $e->getMessage());
    redirect('index.php');
}

// Marca como paga
try {
    $database->beginTransaction();
    
    // Atualiza status da conta
    $query = "UPDATE contas SET status = 'pago', data_pagamento = CURDATE() WHERE id = ? AND usuario_id = ?";
    $database->execute($query, [$conta_id, $user_id]);
    
    // Registra no histórico
    $query = "INSERT INTO historico_pagamentos (conta_id, valor_pago, usuario_id, observacoes) VALUES (?, ?, ?, ?)";
    $database->execute($query, [$conta_id, $conta['valor'], $user_id, 'Pagamento registrado pelo sistema']);
    
    $database->commit();
    
    showSuccess('Conta "' . htmlspecialchars($conta['descricao']) . '" marcada como paga!');
    
} catch (Exception $e) {
    $database->rollback();
    showError('Erro ao marcar conta como paga: ' . $e->getMessage());
}

redirect('index.php');
?>

