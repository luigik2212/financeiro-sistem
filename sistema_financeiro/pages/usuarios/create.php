<?php
/**
 * Criar Usuário
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once '../../includes/init.php';
require_once '../../includes/auth_check.php';

// Verifica se é admin
requireAdmin();

$current_page = 'usuarios';
$page_title = 'Novo Usuário';

$errors = [];
$form_data = [
    'nome' => '',
    'email' => '',
    'nivel_acesso' => 'usuario',
    'ativo' => 1
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
    
    if (empty($senha)) {
        $errors[] = 'Senha é obrigatória.';
    } elseif (strlen($senha) < 6) {
        $errors[] = 'Senha deve ter pelo menos 6 caracteres.';
    }
    
    if ($senha !== $confirmar_senha) {
        $errors[] = 'Senhas não conferem.';
    }
    
    // Verifica se email já existe
    if (empty($errors)) {
        try {
            $query = "SELECT id FROM usuarios WHERE email = ?";
            $existing_user = $database->selectOne($query, [$form_data['email']]);
            
            if ($existing_user) {
                $errors[] = 'Este email já está sendo usado por outro usuário.';
            }
        } catch (Exception $e) {
            $errors[] = 'Erro ao verificar email: ' . $e->getMessage();
        }
    }
    
    // Salva usuário
    if (empty($errors)) {
        try {
            $query = "INSERT INTO usuarios (nome, email, senha, nivel_acesso, ativo) 
                      VALUES (?, ?, ?, ?, ?)";
            $params = [
                $form_data['nome'],
                $form_data['email'],
                hashPassword($senha),
                $form_data['nivel_acesso'],
                $form_data['ativo']
            ];
            
            $database->execute($query, $params);
            
            showSuccess('Usuário criado com sucesso!');
            redirect('index.php');
            
        } catch (Exception $e) {
            $errors[] = 'Erro ao criar usuário: ' . $e->getMessage();
        }
    }
}

include '../../templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Novo Usuário</h1>
                <p class="text-muted">Adicionar novo usuário ao sistema</p>
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
                                <label for="senha" class="form-label">Senha *</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="senha" 
                                       name="senha" 
                                       minlength="6"
                                       required>
                                <div class="invalid-feedback">
                                    Senha deve ter pelo menos 6 caracteres.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirmar_senha" 
                                       name="confirmar_senha" 
                                       minlength="6"
                                       required>
                                <div class="invalid-feedback">
                                    Por favor, confirme a senha.
                                </div>
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
                        <button type="submit" class="btn btn-success">Criar Usuário</button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6>Informações</h6>
            </div>
            <div class="card-body">
                <p><strong>Níveis de Acesso:</strong></p>
                <ul>
                    <li><strong>Usuário:</strong> Acesso às funcionalidades básicas do sistema</li>
                    <li><strong>Administrador:</strong> Acesso completo, incluindo gerenciamento de usuários</li>
                </ul>
                
                <p><strong>Requisitos da Senha:</strong></p>
                <ul>
                    <li>Mínimo de 6 caracteres</li>
                    <li>Recomendado usar letras, números e símbolos</li>
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
    
    if (senha !== confirmarSenha) {
        this.setCustomValidity('Senhas não conferem');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include '../../templates/footer.php'; ?>

