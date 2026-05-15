<?php
declare(strict_types=1);

define('ROLE_ADMIN',      1);
define('ROLE_STUDENT',    2);
define('ROLE_DATA_ENTRY', 3);

function has_role(int ...$roles): bool {
    return isset($_SESSION['role']) && in_array((int)$_SESSION['role'], $roles, true);
}

function require_role(int ...$roles): void {
    foreach ($roles as $role) {
        if (has_role($role)) return;
    }
    header('Location: home.php');
    exit;
}