<?php
/**
 * Dashboard Principal
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once 'includes/init.php';
require_once 'includes/auth_check.php';

$current_page = 'dashboard';
$page_title = 'Dashboard';

// Busca estatísticas do mês atual
$current_month = date('Y-m');
$user_id = $_SESSION['user_id'];

try {
    // Total de receitas do mês
    $query = "SELECT COALESCE(SUM(valor), 0) as total FROM contas 
              WHERE usuario_id = ? AND tipo = 'receita' 
              AND DATE_FORMAT(data_vencimento, '%Y-%m') = ?";
    $receitas_mes = $database->selectOne($query, [$user_id, $current_month])['total'];
    
    // Total de despesas do mês
    $query = "SELECT COALESCE(SUM(valor), 0) as total FROM contas 
              WHERE usuario_id = ? AND tipo = 'despesa' 
              AND DATE_FORMAT(data_vencimento, '%Y-%m') = ?";
    $despesas_mes = $database->selectOne($query, [$user_id, $current_month])['total'];
    
    // Saldo do mês
    $saldo_mes = $receitas_mes - $despesas_mes;
    
    // Contas pendentes
    $query = "SELECT COUNT(*) as total FROM contas 
              WHERE usuario_id = ? AND status = 'pendente'";
    $contas_pendentes = $database->selectOne($query, [$user_id])['total'];
    
    // Contas vencidas
    $query = "SELECT COUNT(*) as total FROM contas 
              WHERE usuario_id = ? AND status = 'pendente' 
              AND data_vencimento < CURDATE()";
    $contas_vencidas = $database->selectOne($query, [$user_id])['total'];
    
    // Próximas contas a vencer (próximos 7 dias)
    $query = "SELECT * FROM contas c
              LEFT JOIN categorias cat ON c.categoria_id = cat.id
              WHERE c.usuario_id = ? AND c.status = 'pendente' 
              AND c.data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
              ORDER BY c.data_vencimento ASC
              LIMIT 5";
    $proximas_contas = $database->select($query, [$user_id]);
    
    // Últimas transações
    $query = "SELECT c.*, cat.nome as categoria_nome, cat.cor as categoria_cor
              FROM contas c
              LEFT JOIN categorias cat ON c.categoria_id = cat.id
              WHERE c.usuario_id = ?
              ORDER BY c.data_criacao DESC
              LIMIT 5";
    $ultimas_transacoes = $database->select($query, [$user_id]);
    
} catch (Exception $e) {
    $receitas_mes = $despesas_mes = $saldo_mes = 0;
    $contas_pendentes = $contas_vencidas = 0;
    $proximas_contas = $ultimas_transacoes = [];
}

include 'templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1>Dashboard</h1>
        <p class="text-muted">Visão geral das suas finanças</p>
    </div>
</div>

<!-- Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value text-success"><?php echo formatMoney($receitas_mes); ?></div>
        <div class="stat-label">Receitas do Mês</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-danger"><?php echo formatMoney($despesas_mes); ?></div>
        <div class="stat-label">Despesas do Mês</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value <?php echo $saldo_mes >= 0 ? 'text-success' : 'text-danger'; ?>">
            <?php echo formatMoney($saldo_mes); ?>
        </div>
        <div class="stat-label">Saldo do Mês</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-warning"><?php echo $contas_pendentes; ?></div>
        <div class="stat-label">Contas Pendentes</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-danger"><?php echo $contas_vencidas; ?></div>
        <div class="stat-label">Contas Vencidas</div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de Receitas vs Despesas -->
    <div class="col-6">
        <div class="card">
            <div class="card-header">
                <h5>Receitas vs Despesas (Mês Atual)</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueExpenseChart" 
                        data-revenue="<?php echo $receitas_mes; ?>"
                        data-expense="<?php echo $despesas_mes; ?>"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Próximas Contas -->
    <div class="col-6">
        <div class="card">
            <div class="card-header">
                <h5>Próximas Contas (7 dias)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($proximas_contas)): ?>
                    <p class="text-muted">Nenhuma conta próxima do vencimento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <?php foreach ($proximas_contas as $conta): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($conta['descricao']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($conta['nome'] ?? 'Sem categoria'); ?></small>
                                        </td>
                                        <td class="text-right">
                                            <div class="<?php echo $conta['tipo'] === 'receita' ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatMoney($conta['valor']); ?>
                                            </div>
                                            <small class="text-muted"><?php echo formatDate($conta['data_vencimento']); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="pages/contas/index.php" class="btn btn-primary btn-sm">Ver Todas as Contas</a>
            </div>
        </div>
    </div>
</div>

<!-- Últimas Transações -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Últimas Transações</h5>
            </div>
            <div class="card-body">
                <?php if (empty($ultimas_transacoes)): ?>
                    <p class="text-muted">Nenhuma transação encontrada.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimas_transacoes as $transacao): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transacao['descricao']); ?></td>
                                        <td>
                                            <span class="badge" style="background-color: <?php echo $transacao['categoria_cor'] ?? '#6c757d'; ?>">
                                                <?php echo htmlspecialchars($transacao['categoria_nome'] ?? 'Sem categoria'); ?>
                                            </span>
                                        </td>
                                        <td class="<?php echo $transacao['tipo'] === 'receita' ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatMoney($transacao['valor']); ?>
                                        </td>
                                        <td><?php echo formatDate($transacao['data_vencimento']); ?></td>
                                        <td>
                                            <?php
                                            $status = getAccountStatus($transacao['data_vencimento'], $transacao['data_pagamento']);
                                            $badge_class = $status === 'pago' ? 'badge-success' : ($status === 'vencido' ? 'badge-danger' : 'badge-warning');
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="pages/contas/index.php" class="btn btn-primary btn-sm">Ver Todas as Transações</a>
                <a href="pages/contas/create.php" class="btn btn-success btn-sm">Nova Transação</a>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

