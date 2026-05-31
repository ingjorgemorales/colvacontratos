<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

final class ConfigController {
    public function index(): void {
        Auth::requireLogin();
        View::render('config/index');
    }
}
