<?php

require_once __DIR__ . '/bootstrap.php';

if (is_user_logged_in()) {
    redirect_to('index.php');
}

$inputs = [];
$errors = [];

if (is_post_request()) {

    [$inputs, $errors] = filter($_POST, [
        'full_name' => 'string | required',
        'password' => 'string | required'
    ]);


    if ($errors) {
        redirect_with('login.php', [
            'errors' => $errors,
            'inputs' => $inputs
        ]);
    }

    $user = login($inputs['full_name'], $inputs['password']);
    
    if (!$user) {
        $errors['login'] = 'Invalid full_name or password';

        redirect_with('login.php', [
            'errors' => $errors,
            'inputs' => $inputs
        ]);
    }

    redirect_to('index.php');

} else if (is_get_request()) {
    [$errors, $inputs] = session_flash('errors', 'inputs');
}
