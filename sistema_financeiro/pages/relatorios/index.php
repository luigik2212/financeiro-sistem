<?php
/**
 * Relatórios
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once '../../includes/init.php';
require_once '../../includes/auth_check.php';

$current_page = 'relatorios';
$page_title = 'Relatórios';

$user_id = $_SESSION['user_id'];

// Filtros
$filters = [
    'periodo' => sanitize($_GET['periodo'] ?? 'mes_atual'),
    'tipo' => sanitize($_GET['tipo'] ?? ''),
    'categoria' => (int)($_GET['categoria'] ?? 0)
];

// Define período
$data_inicio = $data_fim = '';
switch ($filters['periodo']) {
    case 'mes_atual':
        $data_inicio = date('Y-m-01');
        $data_fim = date('Y-m-t');
        break;
    case 'mes_anterior':
        $data_inicio = date('Y-m-01', strtotime('-1 month'));
        $data_fim = date('Y-m-t', strtotime('-1 month'));
        break;
    case 'ano_atual':
        $data_inicio = date('Y-01-01');
        $data_fim = date('Y-12-31');
        break;
    case 'ultimos_30_dias':
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
        $data_fim = date('Y-m-d');
        break;
}

try {
    // Busca dados do relatório
    $where_conditions = ["c.usuario_id = ?", "c.data_vencimento BETWEEN ? AND ?"];
    $params = [$user_id, $data_inicio, $data_fim];
    
    if (!empty($filters['tipo'])) {
        $where_conditions[] = "c.tipo = ?";
        $params[] = $filters['tipo'];
    }
    
    if (!empty($filters['categoria'])) {
        $where_conditions[] = "c.categoria_id = ?";
        $params[] = $filters['categoria'];
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    // Resumo geral
    $resumo_query = "SELECT 
                        SUM(CASE WHEN c.tipo = 'receita' THEN c.valor ELSE 0 END) as total_receitas,
                        SUM(CASE WHEN c.tipo = 'despesa' THEN c.valor ELSE 0 END) as total_despesas,
                        SUM(CASE WHEN c.tipo = 'receita' AND c.status = 'pago' THEN c.valor ELSE 0 END) as receitas_pagas,
                        SUM(CASE WHEN c.tipo = 'despesa' AND c.status = 'pago' THEN c.valor ELSE 0 END) as despesas_pagas,
                        COUNT(*) as total_contas
                     FROM contas c $where_clause";
    $resumo = $database->selectOne($resumo_query, $params);
    
    // Por categoria
    $categoria_query = "SELECT 
                            cat.nome as categoria,
                            cat.cor,
                            c.tipo,
                            SUM(c.valor) as total,
                            COUNT(*) as quantidade
                        FROM contas c
                        LEFT JOIN categorias cat ON c.categoria_id = cat.id
                        $where_clause
                        GROUP BY c.categoria_id, c.tipo
                        ORDER BY total DESC";
    $por_categoria = $database->select($categoria_query, $params);
    
    // Por mês (últimos 12 meses)
    $mensal_query = "SELECT 
                        DATE_FORMAT(c.data_vencimento, '%Y-%m') as mes,
                        DATE_FORMAT(c.data_vencimento, '%m/%Y') as mes_formatado,
                        SUM(CASE WHEN c.tipo = 'receita' THEN c.valor ELSE 0 END) as receitas,
                        SUM(CASE WHEN c.tipo = 'despesa' THEN c.valor ELSE 0 END) as despesas
                     FROM contas c
                     WHERE c.usuario_id = ? AND c.data_vencimento >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                     GROUP BY DATE_FORMAT(c.data_vencimento, '%Y-%m')
                     ORDER BY mes DESC
                     LIMIT 12";
    $por_mes = $database->select($mensal_query, [$user_id]);
    
    // Busca categorias para filtro
    $categorias = $database->select("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome");
    
} catch (Exception $e) {
    showError('Erro ao gerar relatório: ' . $e->getMessage());
    $resumo = ['total_receitas' => 0, 'total_despesas' => 0, 'receitas_pagas' => 0, 'despesas_pagas' => 0, 'total_contas' => 0];
    $por_categoria = [];
    $por_mes = [];
    $categorias = [];
}

include '../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Relatórios</h1>
                <p class="text-muted">Análise financeira e estatísticas</p>
                    <link rel="stylesheet" href="../../assets/css/style.css">
            </div>
        </div>
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
                    <div class="col-md-4">
                        <select class="form-control form-select" name="periodo">
                            <option value="mes_atual" <?php echo $filters['periodo'] === 'mes_atual' ? 'selected' : ''; ?>>Mês Atual</option>
                            <option value="mes_anterior" <?php echo $filters['periodo'] === 'mes_anterior' ? 'selected' : ''; ?>>Mês Anterior</option>
                            <option value="ano_atual" <?php echo $filters['periodo'] === 'ano_atual' ? 'selected' : ''; ?>>Ano Atual</option>
                            <option value="ultimos_30_dias" <?php echo $filters['periodo'] === 'ultimos_30_dias' ? 'selected' : ''; ?>>Últimos 30 Dias</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <select class="form-control form-select" name="tipo">
                            <option value="">Todos os tipos</option>
                            <option value="receita" <?php echo $filters['tipo'] === 'receita' ? 'selected' : ''; ?>>Receitas</option>
                            <option value="despesa" <?php echo $filters['tipo'] === 'despesa' ? 'selected' : ''; ?>>Despesas</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
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
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Resumo -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-value text-success"><?php echo formatMoney($resumo['total_receitas']); ?></div>
        <div class="stat-label">Total Receitas</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-danger"><?php echo formatMoney($resumo['total_despesas']); ?></div>
        <div class="stat-label">Total Despesas</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value <?php echo ($resumo['total_receitas'] - $resumo['total_despesas']) >= 0 ? 'text-success' : 'text-danger'; ?>">
            <?php echo formatMoney($resumo['total_receitas'] - $resumo['total_despesas']); ?>
        </div>
        <div class="stat-label">Saldo</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-info"><?php echo $resumo['total_contas']; ?></div>
        <div class="stat-label">Total de Contas</div>
    </div>
</div>

<div class="row">
    <!-- Gráfico Mensal -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Evolução Mensal (Últimos 12 Meses)</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" 
                        data-months='<?php echo json_encode(array_reverse(array_column($por_mes, 'mes_formatado'))); ?>'
                        data-revenues='<?php echo json_encode(array_reverse(array_column($por_mes, 'receitas'))); ?>'
                        data-expenses='<?php echo json_encode(array_reverse(array_column($por_mes, 'despesas'))); ?>'></canvas>
            </div>
        </div>
    </div>
    
    <!-- Por Categoria -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Por Categoria</h5>
            </div>
            <div class="card-body">
                <?php if (empty($por_categoria)): ?>
                    <p class="text-muted">Nenhum dado encontrado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <?php foreach ($por_categoria as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="badge" style="background-color: <?php echo $item['cor'] ?? '#6c757d'; ?>">
                                                <?php echo htmlspecialchars($item['categoria'] ?? 'Sem categoria'); ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <div class="<?php echo $item['tipo'] === 'receita' ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatMoney($item['total']); ?>
                                            </div>
                                            <small class="text-muted"><?php echo $item['quantidade']; ?> conta(s)</small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>

