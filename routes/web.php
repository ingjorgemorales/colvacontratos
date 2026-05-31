<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LegacyRouterController;

Route::any('/', LegacyRouterController::class)->name('legacy.home');
Route::any('/index.php', LegacyRouterController::class)->name('legacy.index');

/*
|--------------------------------------------------------------------------
| Rutas nativas Laravel futuras
|--------------------------------------------------------------------------
| La app actual usa el parámetro ?r=... heredado. El controlador LegacyRouter
| mantiene compatibilidad mientras se migran módulos a controladores Laravel.
|
| Ejemplo futuro:
| Route::get('/contratos', [ContractController::class, 'index'])->name('contracts.index');
*/

Route::fallback(LegacyRouterController::class);
