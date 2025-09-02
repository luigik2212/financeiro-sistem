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
/* Resumo */
.stats-grid {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    justify-content: flex-start;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    padding: 1.2rem 2rem;
    min-width: 180px;
    flex: 1 1 180px;
    text-align: center;
    margin-bottom: 0.5rem;
    transition: box-shadow 0.2s;
}
.stat-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.10);
}
.stat-value {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 0.3rem;
}
.stat-label {
    font-size: 1rem;
    color: #888;
}

/* Filtros */
.filters-form .form-group {
    margin-bottom: 1rem;
}
.filters-form label {
    font-weight: 500;
}
.filter-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
.card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

/* Melhorias nos filtros */
.filters-form input[type="text"],
.filters-form select,
.filters-form input[type="date"] {
    border-radius: 8px;
    border: 1px solid #d1d5db;
    background: #f9fafb;
    transition: border 0.2s;
}
.filters-form input[type="text"]:focus,
.filters-form select:focus,
.filters-form input[type="date"]:focus {
    border-color: #007bff;
    background: #fff;
}
.filters-form .form-control,
.filters-form .form-select {
    min-height: 38px;
}
.filters-form .filter-buttons .btn {
    min-width: 110px;
    font-weight: 500;
    border-radius: 8px;
}
.filters-form .filter-buttons .btn-primary {
    background: #2563eb;
    border: none;
}
.filters-form .filter-buttons .btn-primary:hover {
    background: #1d4ed8;
}
.filters-form .filter-buttons .btn-secondary {
    background: #6b7280;
    border: none;
}
.filters-form .filter-buttons .btn-secondary:hover {
    background: #4b5563;
}

/* Tabela */
.table thead th {
    background: #f4f6fa;
    font-weight: 600;
    border-bottom: 2px solid #e3e6ed;
}
.table-hover tbody tr:hover {
    background: #e0f2fe;
    transition: background 0.2s;
}
.badge {
    border-radius: 8px;
    padding: 0.4em 0.8em;
    font-size: 0.95em;
}
.badge-success { background: #e6f4ea; color: #218838; }
.badge-danger { background: #fbeaea; color: #c82333; }
.badge-warning { background: #fff6e0; color: #856404; }
.badge-info { background: #e7f3fa; color: #117a8b; }
.btn-sm {
    padding: 0.25rem 0.7rem;
    font-size: 0.95em;
    border-radius: 8px !important;
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
    color: #007bff;
    border-color: #dee2e6;
    border-radius: 8px !important;
    margin: 0 2px;
}
.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
    color: #fff;
}
.pagination .page-link:hover {
    background-color: #e9ecef;
}

/* Responsividade */
@media (max-width: 768px) {
    .stats-grid {
        flex-direction: column;
        gap: 0.7rem;
    }
    .stat-card {
        min-width: 100%;
        padding: 1rem;
    }
    .filters-form .row > div {
        margin-bottom: 1rem;
    }
    .btn-group {
        flex-direction: column;
    }
    .btn-group .btn {
        margin-bottom: 0.3rem;
        margin-right: 0 !important;
    }
    .btn-group .btn:last-child {
        margin-bottom: 0;
    }
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

