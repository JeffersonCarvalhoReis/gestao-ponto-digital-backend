<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\JustificativaController;
use App\Http\Controllers\LocalidadeController;
use App\Http\Controllers\UnidadeController;
use App\Http\Controllers\UserController;
use App\Models\DadosContrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', [AuthController::class, 'user']);
Route::middleware(['auth:sanctum'])->apiResource('/usuarios', controller: UserController::class);
Route::middleware(['auth:sanctum'])->apiResource('/localidades', controller: LocalidadeController::class);
Route::middleware(['auth:sanctum'])->apiResource('/unidades', controller: UnidadeController::class);
Route::middleware(['auth:sanctum'])->apiResource('/funcionarios', controller: FuncionarioController::class);
Route::middleware(['auth:sanctum'])->get('/justificativas/{id}',  [JustificativaController::class, 'index' ]);
Route::middleware(['auth:sanctum'])->post('/justificativas',  [JustificativaController::class, 'store' ]);










