<?php



function view(string $filename, array $data = []): void
{
    foreach ($data as $key => $value) {
        $$key = $value;
    }
    
    require_once __DIR__ . '/../inc/' . $filename . '.php'; 
}

function error_class(array $errors, string $field): string
{
    return isset($errors[$field]) ? 'error' : '';
}

function is_post_request(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
}

function is_get_request(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD']) === 'GET';
}

function redirect_to(string $url): void
{
    header('Location: ' . $url); 
    exit; 
}

function redirect_with(string $url, array $items): void
{
    foreach ($items as $key => $value) {
        $_SESSION[$key] = $value;
    }
    redirect_to($url);
}

function redirect_with_message(string $url, string $message, string $type = 'success')
{
     
    redirect_to($url);
}

function session_flash(...$keys): array
{
    $data = [];
    foreach ($keys as $key) {
        if (isset($_SESSION[$key])) {
            $data[] = $_SESSION[$key];
            unset($_SESSION[$key]);
        } else {
            $data[] = [];
        }
    }
    return $data;
}

function check_user_role(array $allowed_roles): void {
    if (!is_logged_in()) { 
        redirect_to('login.php'); 
    }

    $current_role = $_SESSION['user_role'] ?? 'Ziyaretçi';

    if (!in_array($current_role, $allowed_roles)) {
        http_response_code(403);
        echo "<h1>403 Forbidden</h1><p>Bu sayfaya erişim yetkiniz bulunmamaktadır. Rolünüz: " . htmlspecialchars($current_role) . "</p>";
        exit();
    }
}

function check_company_authorization(array $allowed_roles, string $required_company_id): void {
    
    
    check_user_role($allowed_roles);

    $current_role = $_SESSION['user_role'] ?? 'Ziyaretçi';
    $user_company_id = $_SESSION['company_id'] ?? null;

    if ($current_role !== 'Admin') { 
        
        if (empty($user_company_id) || $user_company_id !== $required_company_id) {
            
            http_response_code(403);
            echo "<h1>403 Forbidden - Firma Yetki Hatası</h1><p>Bu işlem, sadece kendi firmanıza ait veriler üzerinde yapılabilir.</p>";
            exit();
        }
    }
}
function redirect(string $url) {
    header("Location: $url");
    exit();
}