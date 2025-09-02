<?php
/**
 * Instalação do Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);

$error_message = '';
$success_message = '';

// Configurações do banco
$db_config = [
    'host' => 'localhost',
    'name' => 'u850382502_financeiro',
    'user' => 'u850382502_admin',
    'pass' => 'Porto2025.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_config['host'] = $_POST['db_host'] ?? 'localhost';
    $db_config['name'] = $_POST['db_name'] ?? 'u850382502_financeiro';
    $db_config['user'] = $_POST['db_user'] ?? 'u850382502_admin';
    $db_config['pass'] = $_POST['db_pass'] ?? 'Porto2025.';
    
    try {
        // Conecta ao MySQL
        $dsn = "mysql:host=" . $db_config['host'] . ";charset=utf8mb4";
        $pdo = new PDO($dsn, $db_config['user'], $db_config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Cria o banco de dados
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . $db_config['name'] . "`");
        $pdo->exec("USE `" . $db_config['name'] . "`");
        
        // Lê e executa o script SQL
        $sql_file = __DIR__ . '/database_structure.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            
            // Remove comentários e linhas vazias
            $sql = preg_replace('/--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
            
            // Divide em comandos individuais
            $commands = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($commands as $command) {
                if (!empty($command)) {
                    $pdo->exec($command);
                }
            }
            
            // Atualiza o arquivo de configuração
            $config_content = "<?php
/**
 * Configurações do Banco de Dados
 * Sistema Financeiro
 */

// Configurações do banco de dados
define('DB_HOST', '{$db_config['host']}');
define('DB_NAME', '{$db_config['name']}');
define('DB_USER', '{$db_config['user']}');
define('DB_PASS', '{$db_config['pass']}');
define('DB_CHARSET', 'utf8mb4');

// Configurações do sistema
define('SITE_URL', 'http://localhost');
define('SITE_NAME', 'Sistema Financeiro');
define('TIMEZONE', 'America/Sao_Paulo');

// Configurações de sessão
define('SESSION_NAME', 'sistema_financeiro_session');
define('SESSION_LIFETIME', 3600); // 1 hora

// Configurações de segurança
define('HASH_ALGORITHM', 'sha256');
define('ENCRYPTION_KEY', '" . bin2hex(random_bytes(32)) . "');

// Definir timezone
date_default_timezone_set(TIMEZONE);

// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
?>";
            
            file_put_contents(__DIR__ . '/config/database.php', $config_content);
            
            $success_message = 'Sistema instalado com sucesso! Você pode fazer login com:<br>
                               <strong>Email:</strong> admin@sistema.com<br>
                               <strong>Senha:</strong> admin123';
            
        } else {
            $error_message = 'Arquivo de estrutura do banco não encontrado.';
        }
        
    } catch (PDOException $e) {
        $error_message = 'Erro ao conectar com o banco de dados: ' . $e->getMessage();
    } catch (Exception $e) {
        $error_message = 'Erro durante a instalação: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema Financeiro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Sistema Financeiro</h1>
                <p>Instalação do Sistema</p>
            </div>
            <div class="login-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-primary">Acessar Sistema</a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="db_host" class="form-label">Host do Banco</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="db_host" 
                                   name="db_host" 
                                   value="<?php echo htmlspecialchars($db_config['host']); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_name" class="form-label">Nome do Banco</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="db_name" 
                                   name="db_name" 
                                   value="<?php echo htmlspecialchars($db_config['name']); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_user" class="form-label">Usuário do Banco</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="db_user" 
                                   name="db_user" 
                                   value="<?php echo htmlspecialchars($db_config['user']); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_pass" class="form-label">Senha do Banco</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="db_pass" 
                                   name="db_pass" 
                                   value="<?php echo htmlspecialchars($db_config['pass']); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            Instalar Sistema
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

