<?php
/**
 * Página de Login
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once '../../includes/init.php';

// Se já estiver logado, redireciona para dashboard
if (isLoggedIn()) {
    redirect('../../index.php');
}

$error_message = '';

// Processa o login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Por favor, preencha todos os campos.';
    } else {
        try {
            // Busca usuário no banco
            $query = "SELECT id, nome, email, senha, nivel_acesso, ativo FROM usuarios WHERE email = ? AND ativo = 1";
            $user = $database->selectOne($query, [$email]);
            
            if ($user && verifyPassword($password, $user['senha'])) {
                // Login válido - cria sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_level'] = $user['nivel_acesso'];
                $_SESSION['login_time'] = time();
                
                // Redireciona para dashboard
                redirect('../../index.php');
            } else {
                $error_message = 'Email ou senha incorretos.';
            }
        } catch (Exception $e) {
            $error_message = 'Erro interno do sistema. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Faça login para acessar o sistema</p>
            </div>
            <div class="login-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               required>
                        <div class="invalid-feedback">
                            Por favor, informe um email válido.
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required>
                        <div class="invalid-feedback">
                            Por favor, informe sua senha.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        Entrar
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Sistema Financeiro v1.0<br>
                        <strong>Login padrão:</strong> admin@sistema.com / admin123
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/script.js"></script>
</body>
</html>

