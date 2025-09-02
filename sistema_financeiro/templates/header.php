<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
            
            <nav>
                <ul class="nav">
                   <li><a href="/sistema_financeiro/index.php" class="<?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
<li><a href="/sistema_financeiro/pages/contas/index.php" class="<?php echo ($current_page === 'contas') ? 'active' : ''; ?>">Contas</a></li>
<li><a href="/sistema_financeiro/pages/relatorios/index.php" class="<?php echo ($current_page === 'relatorios') ? 'active' : ''; ?>">Relatórios</a></li>
<?php if (isAdmin()): ?>
    <li><a href="/sistema_financeiro/pages/usuarios/index.php" class="<?php echo ($current_page === 'usuarios') ? 'active' : ''; ?>">Usuários</a></li>
<?php endif; ?>

                </ul>
            </nav>
            
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="/sistema_financeiro/pages/auth/logout.php" class="btn btn-secondary btn-sm">Sair</a>
            </div>
        </div>
    </header>
    
    <main class="main">
        <div class="container">
            <?php
            // Exibe mensagens de sucesso
            $success_message = getSuccessMessage();
            if ($success_message):
            ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php
            // Exibe mensagens de erro
            $error_message = getErrorMessage();
            if ($error_message):
            ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

