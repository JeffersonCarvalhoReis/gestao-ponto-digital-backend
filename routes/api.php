<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\DadosContratoController;
use App\Http\Controllers\DiaNaoUtilController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\JustificativaController;
use App\Http\Controllers\LocalidadeController;
use App\Http\Controllers\RegistroPontoController;
use App\Http\Controllers\UnidadeController;
use App\Http\Controllers\UserController;
use App\Models\DadosContrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', [AuthController::class, 'user']);
Route::apiResource('/usuarios', controller: UserController::class);
Route::apiResource('/localidades', controller: LocalidadeController::class);
Route::apiResource('/cargos', controller: CargoController::class);
Route::apiResource('/unidades', controller: UnidadeController::class);
Route::apiResource('/funcionarios', controller: FuncionarioController::class);
Route::get('/justificativas/{id}',  [JustificativaController::class, 'index' ]);
Route::post('/justificativas',  [JustificativaController::class, 'store' ]);
Route::post('/dados-contratos',  [DadosContratoController::class, 'store' ]);
Route::put('/dados-contratos/{id}',  [DadosContratoController::class, 'update' ]);
Route::post('/registro-ponto',  [RegistroPontoController::class , 'registrarPonto' ]);
Route::post('/dias-nao-uteis',  [DiaNaoUtilController::class , 'preencherFinaisDeSemana']);










