<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use App\Auth\Auth;

$auth = new Auth();

if ($auth->isLoggedIn()) {
    redirect('dashboard.php');
}

redirect('login.php');
