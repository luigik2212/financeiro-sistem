<?php
/**
 * Editar Conta
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once '../../includes/init.php';
require_once '../../includes/auth_check.php';

$current_page = 'contas';
$page_title = 'Editar Conta';

$user_id = $_SESSION['user_id'];
$conta_id = (int)($_GET['id'] ?? 0);
$errors = [];

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

$form_data = [
    'descricao' => $conta['descricao'],
    'categoria_id' => $conta['categoria_id'],
    'tipo' => $conta['tipo'],
    'valor' => formatMoney($conta['valor']),
    'data_vencimento' => formatDate($conta['data_vencimento']),
    'observacoes' => $conta['observacoes']
];

// Busca categorias
try {
    $categorias = $database->select("SELECT * FROM categorias WHERE ativo = 1 ORDER BY tipo, nome");
} catch (Exception $e) {
    $categorias = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['descricao'] = sanitize($_POST['descricao'] ?? '');
    $form_data['categoria_id'] = (int)($_POST['categoria_id'] ?? 0);
    $form_data['tipo'] = sanitize($_POST['tipo'] ?? 'despesa');
    $form_data['valor'] = sanitize($_POST['valor'] ?? '');
    $form_data['data_vencimento'] = sanitize($_POST['data_vencimento'] ?? '');
    $form_data['observacoes'] = sanitize($_POST['observacoes'] ?? '');
    
    // Validações
    if (empty($form_data['descricao'])) {
        $errors[] = 'Descrição é obrigatória.';
    }
    
    if (empty($form_data['valor'])) {
        $errors[] = 'Valor é obrigatório.';
    } else {
        $valor_numerico = str_replace(['R$', ' ', '.'], '', $form_data['valor']);
        $valor_numerico = str_replace(',', '.', $valor_numerico);
        
        if (!is_numeric($valor_numerico) || $valor_numerico <= 0) {
            $errors[] = 'Valor deve ser um número positivo.';
        }
    }
    
    if (empty($form_data['data_vencimento'])) {
        $errors[] = 'Data de vencimento é obrigatória.';
    } else {
        $data_mysql = dateToMysql($form_data['data_vencimento']);
        if (!$data_mysql) {
            $errors[] = 'Data de vencimento inválida.';
        }
    }
    
    // Atualiza conta
    if (empty($errors)) {
        try {
            $query = "UPDATE contas SET descricao = ?, categoria_id = ?, tipo = ?, valor = ?, data_vencimento = ?, observacoes = ? WHERE id = ? AND usuario_id = ?";
            $params = [
                $form_data['descricao'],
                $form_data['categoria_id'] ?: null,
                $form_data['tipo'],
                $valor_numerico,
                $data_mysql,
                $form_data['observacoes'],
                $conta_id,
                $user_id
            ];
            
            $database->execute($query, $params);
            
            showSuccess('Conta atualizada com sucesso!');
            redirect('index.php');
            
        } catch (Exception $e) {
            $errors[] = 'Erro ao atualizar conta: ' . $e->getMessage();
        }
    }
}

include '../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Editar Conta</h1>
                <link rel="stylesheet" href="../../assets/css/style.css">
                <p class="text-muted">Alterar dados da conta: <?php echo htmlspecialchars($conta['descricao']); ?></p>
            </div>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Dados da Conta</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="descricao" class="form-label">Descrição *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="descricao" 
                                       name="descricao" 
                                       value="<?php echo htmlspecialchars($form_data['descricao']); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="categoria_id" class="form-label">Categoria</label>
                                <select class="form-control form-select" id="categoria_id" name="categoria_id">
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>" 
                                                <?php echo $form_data['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tipo" class="form-label">Tipo *</label>
                                <select class="form-control form-select" id="tipo" name="tipo" required>
                                    <option value="receita" <?php echo $form_data['tipo'] === 'receita' ? 'selected' : ''; ?>>
                                        Receita
                                    </option>
                                    <option value="despesa" <?php echo $form_data['tipo'] === 'despesa' ? 'selected' : ''; ?>>
                                        Despesa
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="valor" class="form-label">Valor *</label>
                                <input type="text" 
                                       class="form-control money-input" 
                                       id="valor" 
                                       name="valor" 
                                       value="<?php echo htmlspecialchars($form_data['valor']); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="data_vencimento" class="form-label">Data de Vencimento *</label>
                                <input type="text" 
                                       class="form-control date-input" 
                                       id="data_vencimento" 
                                       name="data_vencimento" 
                                       value="<?php echo htmlspecialchars($form_data['data_vencimento']); ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" 
                                  id="observacoes" 
                                  name="observacoes" 
                                  rows="3"><?php echo htmlspecialchars($form_data['observacoes']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6>Informações da Conta</h6>
            </div>
            <div class="card-body">
                <p><strong>ID:</strong> <?php echo $conta['id']; ?></p>
                <p><strong>Status:</strong> 
                    <?php
                    $status = getAccountStatus($conta['data_vencimento'], $conta['data_pagamento']);
                    echo ucfirst($status);
                    ?>
                </p>
                <p><strong>Criado em:</strong> <?php echo formatDateTime($conta['data_criacao']); ?></p>
                
                <?php if ($conta['parcelado']): ?>
                    <div class="alert alert-info">
                        <strong>Conta Parcelada</strong><br>
                        Parcela <?php echo $conta['parcela_atual']; ?> de <?php echo $conta['total_parcelas']; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>

