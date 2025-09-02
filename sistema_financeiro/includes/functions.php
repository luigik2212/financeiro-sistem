<?php
/**
 * Funções Auxiliares
 * Sistema Financeiro
 */

/**
 * Sanitiza dados de entrada
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Valida email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Gera hash da senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifica senha
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Verifica se usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifica se usuário é admin
 */
function isAdmin() {
    return isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'admin';
}

/**
 * Redireciona para uma página
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Formata valor monetário
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Formata data brasileira
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Formata data e hora brasileira
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Converte data brasileira para formato MySQL
 */
function dateToMysql($date) {
    if (empty($date)) return null;
    $parts = explode('/', $date);
    if (count($parts) === 3) {
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    return $date;
}

/**
 * Gera token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Exibe mensagem de sucesso
 */
function showSuccess($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Exibe mensagem de erro
 */
function showError($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Obtém e limpa mensagem de sucesso
 */
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

/**
 * Obtém e limpa mensagem de erro
 */
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

/**
 * Calcula parcelas
 */
function calculateInstallments($total_value, $installments) {
    $installment_value = $total_value / $installments;
    return round($installment_value, 2);
}

/**
 * Gera datas de vencimento das parcelas
 */
function generateInstallmentDates($start_date, $installments) {
    $dates = [];
    $current_date = new DateTime($start_date);
    
    for ($i = 0; $i < $installments; $i++) {
        $dates[] = $current_date->format('Y-m-d');
        $current_date->add(new DateInterval('P1M')); // Adiciona 1 mês
    }
    
    return $dates;
}

/**
 * Verifica se data está vencida
 */
function isOverdue($date) {
    return strtotime($date) < strtotime(date('Y-m-d'));
}

/**
 * Obtém status da conta baseado na data
 */
function getAccountStatus($due_date, $payment_date) {
    if (!empty($payment_date) && $payment_date !== '0000-00-00') {
        return 'pago';
    }
    
    if (isOverdue($due_date)) {
        return 'vencido';
    }
    
    return 'pendente';
}
?>

