<?php
/**
 * Listagem de Contas
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once '../../includes/init.php';
require_once '../../includes/auth_check.php';

$current_page = 'contas';
$page_title = 'Contas';

$user_id = $_SESSION['user_id'];

// Filtros
$filters = [
    'search' => sanitize($_GET['search'] ?? ''),
    'tipo' => sanitize($_GET['tipo'] ?? ''),
    'status' => sanitize($_GET['status'] ?? ''),
    'categoria' => (int)($_GET['categoria'] ?? 0),
    'data_inicio' => sanitize($_GET['data_inicio'] ?? ''),
    'data_fim' => sanitize($_GET['data_fim'] ?? ''),
    'page' => max(1, (int)($_GET['page'] ?? 1))
];

$per_page = 20;
$offset = ($filters['page'] - 1) * $per_page;

// Busca contas
try {
    $where_conditions = ["c.usuario_id = ?"];
    $params = [$user_id];
    
    if (!empty($filters['search'])) {
        $where_conditions[] = "(c.descricao LIKE ? OR c.observacoes LIKE ?)";
        $params[] = "%{$filters['search']}%";
        $params[] = "%{$filters['search']}%";
    }
    
    if (!empty($filters['tipo'])) {
        $where_conditions[] = "c.tipo = ?";
        $params[] = $filters['tipo'];
    }
    
    if (!empty($filters['status'])) {
        if ($filters['status'] === 'vencido') {
            $where_conditions[] = "c.status = 'pendente' AND c.data_vencimento < CURDATE()";
        } else {
            $where_conditions[] = "c.status = ?";
            $params[] = $filters['status'];
        }
    }
    
    if (!empty($filters['categoria'])) {
        $where_conditions[] = "c.categoria_id = ?";
        $params[] = $filters['categoria'];
    }
    
    if (!empty($filters['data_inicio'])) {
        $where_conditions[] = "c.data_vencimento >= ?";
        $params[] = dateToMysql($filters['data_inicio']);
    }
    
    if (!empty($filters['data_fim'])) {
        $where_conditions[] = "c.data_vencimento <= ?";
        $params[] = dateToMysql($filters['data_fim']);
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    // Conta total de registros
    $count_query = "SELECT COUNT(*) as total FROM contas c $where_clause";
    $total_records = $database->selectOne($count_query, $params)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Busca contas com paginação
    $query = "SELECT c.*, cat.nome as categoria_nome, cat.cor as categoria_cor
              FROM contas c
              LEFT JOIN categorias cat ON c.categoria_id = cat.id
              $where_clause
              ORDER BY c.data_vencimento DESC, c.id DESC
              LIMIT $per_page OFFSET $offset";
    
    $contas = $database->select($query, $params);
    
    // Busca categorias para filtro
    $categorias = $database->select("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome");
    
    // Calcula totais
    $totals_query = "SELECT 
                        SUM(CASE WHEN c.tipo = 'receita' THEN c.valor ELSE 0 END) as total_receitas,
                        SUM(CASE WHEN c.tipo = 'despesa' THEN c.valor ELSE 0 END) as total_despesas,
                        SUM(CASE WHEN c.tipo = 'receita' AND c.status = 'pago' THEN c.valor ELSE 0 END) as receitas_pagas,
                        SUM(CASE WHEN c.tipo = 'despesa' AND c.status = 'pago' THEN c.valor ELSE 0 END) as despesas_pagas
                     FROM contas c $where_clause";
    $totals = $database->selectOne($totals_query, $params);
    
} catch (Exception $e) {
    showError('Erro ao carregar contas: ' . $e->getMessage());
    $contas = [];
    $categorias = [];
    $total_records = 0;
    $total_pages = 0;
    $totals = ['total_receitas' => 0, 'total_despesas' => 0, 'receitas_pagas' => 0, 'despesas_pagas' => 0];
}

include '../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
                <link rel="stylesheet" href="../../assets/css/style.css">
            <div>
                <h1>Contas</h1>
                <p class="text-muted">Gerenciar receitas e despesas</p>
            </div>
            <div>
                <a href="create.php" class="btn btn-success">Nova Conta</a>
                <a href="create.php?parcelado=1" class="btn btn-primary">Conta Parcelada</a>
            </div>
        </div>
    </div>
</div>

<!-- Resumo -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-value text-success"><?php echo formatMoney($totals['total_receitas']); ?></div>
        <div class="stat-label">Total Receitas</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-danger"><?php echo formatMoney($totals['total_despesas']); ?></div>
        <div class="stat-label">Total Despesas</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-success"><?php echo formatMoney($totals['receitas_pagas']); ?></div>
        <div class="stat-label">Receitas Pagas</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-danger"><?php echo formatMoney($totals['despesas_pagas']); ?></div>
        <div class="stat-label">Despesas Pagas</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value <?php echo ($totals['total_receitas'] - $totals['total_despesas']) >= 0 ? 'text-success' : 'text-danger'; ?>">
            <?php echo formatMoney($totals['total_receitas'] - $totals['total_despesas']); ?>
        </div>
        <div class="stat-label">Saldo Total</div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6>Filtros</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-3">
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               placeholder="Buscar descrição..."
                               value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <select class="form-control form-select" name="tipo">
                            <option value="">Todos os tipos</option>
                            <option value="receita" <?php echo $filters['tipo'] === 'receita' ? 'selected' : ''; ?>>Receita</option>
                            <option value="despesa" <?php echo $filters['tipo'] === 'despesa' ? 'selected' : ''; ?>>Despesa</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <select class="form-control form-select" name="status">
                            <option value="">Todos os status</option>
                            <option value="pendente" <?php echo $filters['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="pago" <?php echo $filters['status'] === 'pago' ? 'selected' : ''; ?>>Pago</option>
                            <option value="vencido" <?php echo $filters['status'] === 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <select class="form-control form-select" name="categoria">
                            <option value="">Todas categorias</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" 
                                        <?php echo $filters['categoria'] == $categoria['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-6">
                                <input type="text" 
                                       class="form-control date-input" 
                                       name="data_inicio" 
                                       placeholder="Data início"
                                       value="<?php echo htmlspecialchars($filters['data_inicio']); ?>">
                            </div>
                            <div class="col-6">
                                <input type="text" 
                                       class="form-control date-input" 
                                       name="data_fim" 
                                       placeholder="Data fim"
                                       value="<?php echo htmlspecialchars($filters['data_fim']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="index.php" class="btn btn-secondary">Limpar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Contas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Lista de Contas (<?php echo $total_records; ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($contas)): ?>
                    <p class="text-muted text-center">Nenhuma conta encontrada.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                    <th>Parcelas</th>
                                    <th width="150">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contas as $conta): ?>
                                    <?php
                                    $status = getAccountStatus($conta['data_vencimento'], $conta['data_pagamento']);
                                    $badge_class = $status === 'pago' ? 'badge-success' : ($status === 'vencido' ? 'badge-danger' : 'badge-warning');
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($conta['descricao']); ?></strong>
                                            <?php if (!empty($conta['observacoes'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($conta['observacoes']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($conta['categoria_nome']): ?>
                                                <span class="badge" style="background-color: <?php echo $conta['categoria_cor']; ?>">
                                                    <?php echo htmlspecialchars($conta['categoria_nome']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Sem categoria</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $conta['tipo'] === 'receita' ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo ucfirst($conta['tipo']); ?>
                                            </span>
                                        </td>
                                        <td class="<?php echo $conta['tipo'] === 'receita' ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatMoney($conta['valor']); ?>
                                        </td>
                                        <td><?php echo formatDate($conta['data_vencimento']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($conta['parcelado']): ?>
                                                <span class="badge badge-info">
                                                    <?php echo $conta['parcela_atual']; ?>/<?php echo $conta['total_parcelas']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($status === 'pendente'): ?>
                                                <a href="pay.php?id=<?php echo $conta['id']; ?>" 
                                                   class="btn btn-success btn-sm" 
                                                   title="Marcar como pago">
                                                    Pagar
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="edit.php?id=<?php echo $conta['id']; ?>" 
                                               class="btn btn-primary btn-sm" 
                                               title="Editar">
                                                Editar
                                            </a>
                                            
                                            <a href="delete.php?id=<?php echo $conta['id']; ?>" 
                                               class="btn btn-danger btn-sm btn-delete" 
                                               title="Excluir"
                                               data-message="Tem certeza que deseja excluir a conta '<?php echo htmlspecialchars($conta['descricao']); ?>'?">
                                                Excluir
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($filters['page'] > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $filters['page'] - 1])); ?>">
                                            Anterior
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $filters['page'] - 2); $i <= min($total_pages, $filters['page'] + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $filters['page'] ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($filters['page'] < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $filters['page'] + 1])); ?>">
                                            Próxima
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.pagination .page-link {
    color: var(--primary-color);
    border-color: var(--border-color);
}

.pagination .page-item.active .page-link {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.pagination .page-link:hover {
    background-color: var(--light-color);
}
</style>

<?php include '../../templates/footer.php'; ?>

