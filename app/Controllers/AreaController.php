<?php
namespace App\Controllers;
use App\Core\Auth; use App\Core\View; use App\Core\Flash; use App\Models\Area;

final class AreaController {
    public function index(): void { Auth::requireLogin(); View::render('areas/index', ['areas'=>Area::all()]); }
    public function store(): void { Auth::requireLogin(); if(trim($_POST['name'] ?? '') !== '') { Area::create($_POST['name']); Flash::set('success','Área creada correctamente.'); } header('Location: index.php?r=areas'); exit; }
    public function update(): void { Auth::requireLogin(); Area::update((int)($_GET['id'] ?? 0), $_POST['name'] ?? '', isset($_POST['active'])); Flash::set('success','Área actualizada correctamente.'); header('Location: index.php?r=areas'); exit; }
}
