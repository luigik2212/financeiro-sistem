<?php
/**
 * Editar Usuário
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once '../../includes/init.php';
require_once '../../includes/auth_check.php';

// Verifica se é admin
requireAdmin();

$current_page = 'usuarios';
$page_title = 'Editar Usuário';

$user_id = (int)($_GET['id'] ?? 0);
$errors = [];

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

$form_data = [
    'nome' => $usuario['nome'],
    'email' => $usuario['email'],
    'nivel_acesso' => $usuario['nivel_acesso'],
    'ativo' => $usuario['ativo']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['nome'] = sanitize($_POST['nome'] ?? '');
    $form_data['email'] = sanitize($_POST['email'] ?? '');
    $form_data['nivel_acesso'] = sanitize($_POST['nivel_acesso'] ?? 'usuario');
    $form_data['ativo'] = isset($_POST['ativo']) ? 1 : 0;
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações
    if (empty($form_data['nome'])) {
        $errors[] = 'Nome é obrigatório.';
    }
    
    if (empty($form_data['email'])) {
        $errors[] = 'Email é obrigatório.';
    } elseif (!validateEmail($form_data['email'])) {
        $errors[] = 'Email inválido.';
    }
    
    // Validação de senha (apenas se informada)
    if (!empty($senha)) {
        if (strlen($senha) < 6) {
            $errors[] = 'Senha deve ter pelo menos 6 caracteres.';
        }
        
        if ($senha !== $confirmar_senha) {
            $errors[] = 'Senhas não conferem.';
        }
    }
    
    // Verifica se email já existe (exceto para o próprio usuário)
    if (empty($errors)) {
        try {
            $query = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
            $existing_user = $database->selectOne($query, [$form_data['email'], $user_id]);
            
            if ($existing_user) {
                $errors[] = 'Este email já está sendo usado por outro usuário.';
            }
        } catch (Exception $e) {
            $errors[] = 'Erro ao verificar email: ' . $e->getMessage();
        }
    }
    
    // Atualiza usuário
    if (empty($errors)) {
        try {
            if (!empty($senha)) {
                // Atualiza com nova senha
                $query = "UPDATE usuarios SET nome = ?, email = ?, senha = ?, nivel_acesso = ?, ativo = ? WHERE id = ?";
                $params = [
                    $form_data['nome'],
                    $form_data['email'],
                    hashPassword($senha),
                    $form_data['nivel_acesso'],
                    $form_data['ativo'],
                    $user_id
                ];
            } else {
                // Atualiza sem alterar senha
                $query = "UPDATE usuarios SET nome = ?, email = ?, nivel_acesso = ?, ativo = ? WHERE id = ?";
                $params = [
                    $form_data['nome'],
                    $form_data['email'],
                    $form_data['nivel_acesso'],
                    $form_data['ativo'],
                    $user_id
                ];
            }
            
            $database->execute($query, $params);
            
            showSuccess('Usuário atualizado com sucesso!');
            redirect('index.php');
            
        } catch (Exception $e) {
            $errors[] = 'Erro ao atualizar usuário: ' . $e->getMessage();
        }
    }
}

include '../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Editar Usuário</h1>
                <p class="text-muted">Alterar dados do usuário: <?php echo htmlspecialchars($usuario['nome']); ?></p>
                <link rel="stylesheet" href="../../assets/css/style.css">
            </div>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Dados do Usuário</h5>
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nome" class="form-label">Nome *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nome" 
                                       name="nome" 
                                       value="<?php echo htmlspecialchars($form_data['nome']); ?>"
                                       required>
                                <div class="invalid-feedback">
                                    Por favor, informe o nome.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                       required>
                                <div class="invalid-feedback">
                                    Por favor, informe um email válido.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="senha" class="form-label">Nova Senha</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="senha" 
                                       name="senha" 
                                       minlength="6">
                                <small class="form-text text-muted">
                                    Deixe em branco para manter a senha atual
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirmar_senha" 
                                       name="confirmar_senha" 
                                       minlength="6">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nivel_acesso" class="form-label">Nível de Acesso</label>
                                <select class="form-control form-select" id="nivel_acesso" name="nivel_acesso">
                                    <option value="usuario" <?php echo $form_data['nivel_acesso'] === 'usuario' ? 'selected' : ''; ?>>
                                        Usuário
                                    </option>
                                    <option value="admin" <?php echo $form_data['nivel_acesso'] === 'admin' ? 'selected' : ''; ?>>
                                        Administrador
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <div class="form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="ativo" 
                                           name="ativo" 
                                           <?php echo $form_data['ativo'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="ativo">
                                        Usuário ativo
                                    </label>
                                </div>
                            </div>
                        </div>
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
                <h6>Informações do Usuário</h6>
            </div>
            <div class="card-body">
                <p><strong>ID:</strong> <?php echo $usuario['id']; ?></p>
                <p><strong>Cadastrado em:</strong> <?php echo formatDateTime($usuario['data_criacao']); ?></p>
                <p><strong>Última atualização:</strong> <?php echo formatDateTime($usuario['data_atualizacao']); ?></p>
                
                <?php if ($user_id == $_SESSION['user_id']): ?>
                    <div class="alert alert-info">
                        <small>Você está editando seu próprio perfil.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6>Dicas</h6>
            </div>
            <div class="card-body">
                <ul>
                    <li>Deixe a senha em branco para mantê-la inalterada</li>
                    <li>Usuários inativos não conseguem fazer login</li>
                    <li>Apenas administradores podem gerenciar usuários</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Validação de confirmação de senha
document.getElementById('confirmar_senha').addEventListener('input', function() {
    const senha = document.getElementById('senha').value;
    const confirmarSenha = this.value;
    
    if (senha && senha !== confirmarSenha) {
        this.setCustomValidity('Senhas não conferem');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('senha').addEventListener('input', function() {
    const confirmarSenha = document.getElementById('confirmar_senha');
    if (this.value && this.value !== confirmarSenha.value) {
        confirmarSenha.setCustomValidity('Senhas não conferem');
    } else {
        confirmarSenha.setCustomValidity('');
    }
});
</script>

<?php include '../../templates/footer.php'; ?>

