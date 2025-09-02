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
    'vencimentos_recentes' => sanitize($_GET['vencimentos_recentes'] ?? ''),
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
    
    if (!empty($filters['vencimentos_recentes'])) {
        $days = (int)$filters['vencimentos_recentes'];
        $where_conditions[] = "c.data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
        $params[] = $days;
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

<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
body, .main-content {
    background: #181f25 !important;
    color: #f3f4f6;
}
.card, .table, .filters-form, .stats-grid {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}
.stats-grid {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.stat-card {
    flex: 1 1 180px;
    min-width: 200px;
    border-radius: 16px;
    padding: 1.5rem 2rem;
    background: #232b32;
    color: #fff;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    box-shadow: 0 2px 12px rgba(0,0,0,0.10);
    margin-bottom: 0;
}
.stat-card .stat-label {
    font-size: 1.1rem;
    color: #cbd5e1;
    margin-top: 0.5rem;
}
.stat-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
}
.stat-card.stat-receita { background: #eafcf3; color: #16a34a; }
.stat-card.stat-despesa { background: #fef2f2; color: #dc2626; }
.stat-card.stat-paga { background: #e0f2fe; color: #2563eb; }
.stat-card.stat-total { background: #f1f5f9; color: #2563eb; }
.stat-card.stat-saldo { background: #f0fdf4; color: #0d9488; }
.stat-card.stat-receita, .stat-card.stat-despesa, .stat-card.stat-paga, .stat-card.stat-total, .stat-card.stat-saldo {
    color: inherit;
}

/* Filtros */
.filters-form {
    background: #232b32;
    border-radius: 16px;
    padding: 1.5rem 1.5rem 0.5rem 1.5rem;
    margin-bottom: 1.5rem;
}
.filters-form .form-group {
    margin-bottom: 1.2rem;
}
.filters-form label {
    color: #cbd5e1;
    font-weight: 600;
}
.filters-form input[type="text"],
.filters-form select,
.filters-form input[type="date"] {
    border-radius: 12px;
    border: none;
    background: #232b32;
    color: #f3f4f6;
    min-height: 48px;
    font-size: 1.08rem;
    padding: 0.9rem 1.1rem;
    margin-bottom: 0.5rem;
    box-shadow: 0 1px 2px #0000000a;
}
.filters-form input[type="text"]:focus,
.filters-form select:focus,
.filters-form input[type="date"]:focus {
    background: #181f25;
    outline: 2px solid #2563eb;
}
.filters-form .form-select {
    color: #f3f4f6;
}
.filters-form .filter-buttons .btn {
    min-width: 120px;
    font-weight: 600;
    border-radius: 12px;
    font-size: 1.08rem;
    margin-right: 0.7rem;
    border: none;
    box-shadow: 0 1px 2px #0000000a;
}
.filters-form .btn-primary {
    background: #ef4444;
    color: #fff;
}
.filters-form .btn-primary:hover {
    background: #dc2626;
}
.filters-form .btn-secondary {
    background: #374151;
    color: #fff;
}
.filters-form .btn-secondary:hover {
    background: #1e293b;
}

/* Tabela */
.table-responsive {
    background: #232b32;
    border-radius: 16px;
    padding: 0.5rem 0.5rem 0 0.5rem;
}
.table {
    background: transparent;
    color: #f3f4f6;
    border-radius: 16px;
    margin-bottom: 0;
}
.table thead th {
    background: #232b32;
    color: #cbd5e1;
    font-weight: 700;
    border: none;
    font-size: 1.08rem;
    padding-top: 1.1rem;
    padding-bottom: 1.1rem;
}
.table-striped tbody tr:nth-of-type(odd) {
    background: #232b32;
}
.table-striped tbody tr:nth-of-type(even) {
    background: #181f25;
}
.table-hover tbody tr:hover {
    background: #374151 !important;
    transition: background 0.2s;
}
.badge {
    border-radius: 8px;
    padding: 0.4em 0.8em;
    font-size: 0.97em;
    font-weight: 600;
}
.badge-success { background: #22c55e; color: #fff; }
.badge-danger { background: #ef4444; color: #fff; }
.badge-warning { background: #f59e42; color: #fff; }
.badge-info { background: #2563eb; color: #fff; }
.text-success { color: #22c55e !important; }
.text-danger { color: #ef4444 !important; }
.text-muted { color: #94a3b8 !important; }
.btn-sm {
    padding: 0.35rem 0.9rem;
    font-size: 1.05em;
    border-radius: 10px !important;
    font-weight: 600;
}
.btn-success.btn-sm {
    background: #22c55e;
    border: none;
}
.btn-success.btn-sm:hover {
    background: #16a34a;
}
.btn-primary.btn-sm {
    background: #2563eb;
    border: none;
}
.btn-primary.btn-sm:hover {
    background: #1d4ed8;
}
.btn-danger.btn-sm {
    background: #ef4444;
    border: none;
}
.btn-danger.btn-sm:hover {
    background: #b91c1c;
}
.btn-delete {
    transition: background 0.2s;
}
.btn-delete:hover {
    background: #c82333 !important;
    color: #fff !important;
}
.btn-group .btn {
    margin-right: 0.3rem;
}
.btn-group .btn:last-child {
    margin-right: 0;
}

/* Paginação */
.pagination .page-link {
    color: #2563eb;
    border-color: #232b32;
    border-radius: 10px !important;
    margin: 0 2px;
    background: #232b32;
}
.pagination .page-item.active .page-link {
    background-color: #2563eb;
    border-color: #2563eb;
    color: #fff;
}
.pagination .page-link:hover {
    background-color: #181f25;
}

/* Cabeçalho */
.d-flex.justify-content-between.align-items-center.mb-4 {
    margin-bottom: 2.2rem !important;
}
.d-flex.justify-content-between.align-items-center.mb-4 h1 {
    color: #fff;
    font-size: 2.1rem;
    font-weight: 700;
    margin-bottom: 0.2rem;
}
.d-flex.justify-content-between.align-items-center.mb-4 p {
    color: #cbd5e1;
    font-size: 1.1rem;
    margin-bottom: 0;
}
.d-flex.justify-content-between.align-items-center.mb-4 .btn {
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.08rem;
    margin-left: 0.7rem;
    min-width: 140px;
    min-height: 48px;
    box-shadow: 0 1px 2px #0000000a;
}
.d-flex.justify-content-between.align-items-center.mb-4 .btn-success {
    background: #22c55e;
    color: #fff;
    border: none;
}
.d-flex.justify-content-between.align-items-center.mb-4 .btn-primary {
    background: #2563eb;
    color: #fff;
    border: none;
}
.d-flex.justify-content-between.align-items-center.mb-4 .btn-success:hover {
    background: #16a34a;
}
.d-flex.justify-content-between.align-items-center.mb-4 .btn-primary:hover {
    background: #1d4ed8;
}

/* Responsividade */
@media (max-width: 1200px) {
    .stats-grid { flex-direction: column; gap: 1rem; }
    .stat-card { min-width: 100%; }
}
@media (max-width: 991px) {
    .filters-form { padding: 1rem 0.7rem 0.5rem 0.7rem; }
    .d-flex.justify-content-between.align-items-center.mb-4 .btn {
        min-width: 100px;
        min-height: 40px;
        font-size: 1rem;
    }
}
@media (max-width: 768px) {
    .stats-grid { flex-direction: column; gap: 0.7rem; }
    .stat-card { min-width: 100%; padding: 1rem; }
    .filters-form { padding: 1rem 0.3rem 0.3rem 0.3rem; }
    .table-responsive { padding: 0.2rem; }
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
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
    <div class="stat-card stat-receita">
        <div class="stat-label"><i class="fa fa-arrow-down"></i> Receitas</div>
        <div class="stat-value"><?php echo formatMoney($totals['total_receitas']); ?></div>
    </div>
    <div class="stat-card stat-despesa">
        <div class="stat-label"><i class="fa fa-arrow-up"></i> Despesas</div>
        <div class="stat-value"><?php echo formatMoney($totals['total_despesas']); ?></div>
    </div>
    <div class="stat-card stat-paga">
        <div class="stat-label"><i class="fa fa-check-circle"></i> Receitas Pagas</div>
        <div class="stat-value"><?php echo formatMoney($totals['receitas_pagas']); ?></div>
    </div>
    <div class="stat-card stat-paga">
        <div class="stat-label"><i class="fa fa-check-circle"></i> Despesas Pagas</div>
        <div class="stat-value"><?php echo formatMoney($totals['despesas_pagas']); ?></div>
    </div>
    <div class="stat-card stat-saldo">
        <div class="stat-label"><i class="fa fa-wallet"></i> Saldo Total</div>
        <div class="stat-value <?php echo ($totals['total_receitas'] - $totals['total_despesas']) >= 0 ? 'text-success' : 'text-danger'; ?>">
            <?php echo formatMoney($totals['total_receitas'] - $totals['total_despesas']); ?>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Filtros</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="filters-form">
                    <div class="row">
                        <div class="col-md-4 col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Buscar</label>
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="Buscar descrição..."
                                       value="<?php echo htmlspecialchars($filters['search']); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4 col-lg-2">
                            <div class="form-group">
                                <label class="form-label">Tipo</label>
                                <select class="form-control form-select" name="tipo">
                                    <option value="">Todos os tipos</option>
                                    <option value="receita" <?php echo $filters['tipo'] === 'receita' ? 'selected' : ''; ?>>Receita</option>
                                    <option value="despesa" <?php echo $filters['tipo'] === 'despesa' ? 'selected' : ''; ?>>Despesa</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4 col-lg-2">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select class="form-control form-select" name="status">
                                    <option value="">Todos os status</option>
                                    <option value="pendente" <?php echo $filters['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="pago" <?php echo $filters['status'] === 'pago' ? 'selected' : ''; ?>>Pago</option>
                                    <option value="vencido" <?php echo $filters['status'] === 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-2">
                            <div class="form-group">
                                <label class="form-label">Categoria</label>
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
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Vencimentos Recentes</label>
                                <select class="form-control form-select" name="vencimentos_recentes">
                                    <option value="">Todos os vencimentos</option>
                                    <option value="7" <?php echo $filters['vencimentos_recentes'] === '7' ? 'selected' : ''; ?>>Próximos 7 dias</option>
                                    <option value="15" <?php echo $filters['vencimentos_recentes'] === '15' ? 'selected' : ''; ?>>Próximos 15 dias</option>
                                    <option value="30" <?php echo $filters['vencimentos_recentes'] === '30' ? 'selected' : ''; ?>>Próximos 30 dias</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Data Início</label>
                                <input type="date" 
                                       class="form-control" 
                                       name="data_inicio" 
                                       value="<?php echo htmlspecialchars($filters['data_inicio']); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label">Data Fim</label>
                                <input type="date" 
                                       class="form-control" 
                                       name="data_fim" 
                                       value="<?php echo htmlspecialchars($filters['data_fim']); ?>">
                            </div>
                        </div>
                        
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label class="form-label">&nbsp;</label>
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Limpar
                                    </a>
                                </div>
                            </div>
                        </div>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista de Contas (<?php echo $total_records; ?>)</h5>
                <a href="create.php" class="btn btn-success btn-sm">
                    <i class="fa fa-plus"></i> Nova Conta
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($contas)): ?>
                    <p class="text-muted text-center">Nenhuma conta encontrada.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
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
                                                <i class="fa <?php echo $conta['tipo'] === 'receita' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
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
                                            <div class="btn-group" role="group">
                                            <?php if ($status === 'pendente'): ?>
                                                <a href="pay.php?id=<?php echo $conta['id']; ?>" 
                                                   class="btn btn-success btn-sm" 
                                                   title="Marcar como pago">
                                                    <i class="fa fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="edit.php?id=<?php echo $conta['id']; ?>" 
                                               class="btn btn-primary btn-sm" 
                                               title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            
                                            <a href="delete.php?id=<?php echo $conta['id']; ?>" 
                                               class="btn btn-danger btn-sm btn-delete" 
                                               title="Excluir"
                                               data-message="Tem certeza que deseja excluir a conta '<?php echo htmlspecialchars($conta['descricao']); ?>'?">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                            </div>
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
                                            <i class="fa fa-chevron-left"></i> Anterior
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
                                            Próxima <i class="fa fa-chevron-right"></i>
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

<?php include '../../templates/footer.php'; ?>

