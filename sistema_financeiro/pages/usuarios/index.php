<?php
/**
 * Listagem de Usuários
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once '../../includes/init.php';
require_once '../../includes/auth_check.php';

// Verifica se é admin
requireAdmin();

$current_page = 'usuarios';
$page_title = 'Usuários';

// Busca usuários
try {
    $search = sanitize($_GET['search'] ?? '');
    $where_clause = '';
    $params = [];
    
    if (!empty($search)) {
        $where_clause = "WHERE nome LIKE ? OR email LIKE ?";
        $params = ["%$search%", "%$search%"];
    }
    
    $query = "SELECT id, nome, email, nivel_acesso, ativo, data_criacao 
              FROM usuarios $where_clause 
              ORDER BY nome ASC";
    $usuarios = $database->select($query, $params);
    
} catch (Exception $e) {
    showError('Erro ao carregar usuários: ' . $e->getMessage());
    $usuarios = [];
}

include '../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Usuários</h1>
                <p class="text-muted">Gerenciar usuários do sistema</p>
                <link rel="stylesheet" href="../../assets/css/style.css">
            </div>
            <a href="create.php" class="btn btn-success">Novo Usuário</a>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-6">
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               placeholder="Buscar por nome ou email..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        <a href="index.php" class="btn btn-secondary">Limpar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Usuários -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Lista de Usuários (<?php echo count($usuarios); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($usuarios)): ?>
                    <p class="text-muted text-center">Nenhum usuário encontrado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="sortable">Nome</th>
                                    <th class="sortable">Email</th>
                                    <th>Nível</th>
                                    <th>Status</th>
                                    <th class="sortable">Cadastro</th>
                                    <th width="150">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $usuario['nivel_acesso'] === 'admin' ? 'badge-danger' : 'badge-secondary'; ?>">
                                                <?php echo ucfirst($usuario['nivel_acesso']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $usuario['ativo'] ? 'badge-success' : 'badge-secondary'; ?>">
                                                <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDateTime($usuario['data_criacao']); ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $usuario['id']; ?>" 
                                               class="btn btn-primary btn-sm" 
                                               title="Editar">
                                                Editar
                                            </a>
                                            
                                            <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                <a href="delete.php?id=<?php echo $usuario['id']; ?>" 
                                                   class="btn btn-danger btn-sm btn-delete" 
                                                   title="Excluir"
                                                   data-message="Tem certeza que deseja excluir o usuário <?php echo htmlspecialchars($usuario['nome']); ?>?">
                                                    Excluir
                                                </a>
                                            <?php endif; ?>
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

