<?php

function find_user_by_id(string $id)
{
    $sql = 'SELECT id, full_name, email, role, company_id 
            FROM User
            WHERE id = :id';

    $statement = db()->prepare($sql);
    $statement->bindValue(':id', $id, PDO::PARAM_STR);
    $statement->execute();

    return $statement->fetch(PDO::FETCH_ASSOC);
}


function find_user_by_full_name(string $full_name)
{
    $sql = 'SELECT id, full_name, password, role, company_id
            FROM User
            WHERE full_name = :full_name';

    $statement = db()->prepare($sql);
    $statement->bindValue(':full_name', $full_name, PDO::PARAM_STR);
    $statement->execute();

    return $statement->fetch(PDO::FETCH_ASSOC);
}

function login(string $full_name, string $password): bool
{
    $user = find_user_by_full_name($full_name);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id();

        
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role']; 
        $_SESSION['company_id'] = $user['company_id'] ?? null; 

        return true;
    }

    return false;
}

function is_user_logged_in(): bool
{
    return isset($_SESSION['full_name']);
}

function logout(): void
{
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    
    if (is_logged_in()) {

       
        $_SESSION = array();

        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
    redirect('index.php');
}

function current_user()
{
    if (is_logged_in()) {
        return $_SESSION['full_name'];
    }
    return null;
}

function require_login(): void
{
    if (!is_logged_in()) { 
        
    }
}

function is_logged_in(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

function get_current_user_role(): string
{
    if (is_logged_in()) {
        return $_SESSION['user_role'] ?? 'User';
    }
    return 'Ziyaretçi';
}


function is_admin(): bool
{
    return get_current_user_role() === 'Admin';
}

function is_firma_admin(): bool
{
    return get_current_user_role() === 'Firma Admin';
}

function is_user(): bool
{
    return get_current_user_role() === 'User';
}

function get_current_user_company_id(): ?string
{
    if (is_logged_in()) {
        return $_SESSION['company_id'] ?? null;
    }
    return null;
}


function register_user(string $email, string $full_name, string $password, string $role = 'User', ?string $company_id = null): bool
{
    $id = bin2hex(random_bytes(16));
    $created_at = date('Y-m-d H:i:s');

    $sql = 'INSERT INTO User (id, full_name, email, password, role, created_at, company_id)
            VALUES (:id, :full_name, :email, :password, :role, :created_at, :company_id)'; 

    $statement = db()->prepare($sql);

    $statement->bindValue(':id', $id, PDO::PARAM_STR);
    $statement->bindValue(':full_name', $full_name, PDO::PARAM_STR);
    $statement->bindValue(':email', $email, PDO::PARAM_STR);
    $statement->bindValue(':password', password_hash($password, PASSWORD_BCRYPT), PDO::PARAM_STR);
    $statement->bindValue(':role', $role, PDO::PARAM_STR);
    $statement->bindValue(':created_at', $created_at, PDO::PARAM_STR);
    $statement->bindValue(':company_id', $company_id, PDO::PARAM_STR); 

    return $statement->execute();
}
function check_role($required_roles): bool {
    if (!is_logged_in()) {
        return false;
    }
    
    $current_role = $_SESSION['user_role'] ?? 'Ziyaretçi'; 

    if (is_string($required_roles)) {
        return $current_role === $required_roles;
    }
    
    if (is_array($required_roles)) {
        return in_array($current_role, $required_roles);
    }
    
    return false;
}


function require_auth(string|array $roles_to_allow, string $redirect_page = 'login.php') {
    if (!is_logged_in()) {
        $redirect_url = urlencode($_SERVER['REQUEST_URI']);
        redirect("$redirect_page?redirect=$redirect_url");
    }
    
    if (!check_role($roles_to_allow)) {
        http_response_code(403);
        die("<h1>Yetkisiz Erişim (403)</h1><p>Bu sayfaya erişim yetkiniz yok.</p><a href='index.php'>Ana Sayfaya Dön</a>");
    }
}