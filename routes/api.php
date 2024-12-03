<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiometriaController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\DadosContratoController;
use App\Http\Controllers\DiaNaoUtilController;
use App\Http\Controllers\FeriaController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\JustificativaController;
use App\Http\Controllers\LocalidadeController;
use App\Http\Controllers\RecessoController;
use App\Http\Controllers\RegistroPontoController;
use App\Http\Controllers\RelatorioPontoController;
use App\Http\Controllers\UnidadeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Registro de Ponto
Route::post('registro-ponto', [RegistroPontoController::class, 'registrarPonto']);

Route::middleware(['auth:sanctum'])->group(function(){


    // Biometria
    Route::get('biometria/carregar', [BiometriaController::class, 'loadToMemory']);
    Route::get('biometria/identificar', [BiometriaController::class, 'identify']);
    Route::get('biometria/registrar/{funcionario}', [BiometriaController::class, 'captureHash']);

    Route::apiResource('/usuarios',  UserController::class);
    Route::apiResource('/localidades',   LocalidadeController::class);
    Route::apiResource('/cargos',  CargoController::class);
    Route::apiResource('/unidades',  UnidadeController::class);
    Route::apiResource('/funcionarios',  FuncionarioController::class);
    Route::apiResource('/justificativas',  JustificativaController::class);
    Route::apiResource('/dia-nao-util',  DiaNaoUtilController::class);


     // Dados Contratos
     Route::post('dados-contratos', [DadosContratoController::class, 'store']);
     Route::put('dados-contratos/{id}', [DadosContratoController::class, 'update']);

      // Dias não úteis
    Route::get('dias-nao-uteis', [DiaNaoUtilController::class, 'preencherFeriados']);

    // Férias
    Route::post('ferias', [FeriaController::class, 'store']);
    Route::get('ferias', [FeriaController::class, 'index']);
    Route::delete('ferias', [FeriaController::class, 'destroy']);




    // User (Autenticação)
    Route::get('api/user', [AuthController::class, 'user']);
});

// Relatório de Pontos
Route::post('relatorio', [RelatorioPontoController::class, 'gerarRelatorio']);

// Recesso
Route::post('recesso', [RecessoController::class, 'store']);
Route::get('recesso', [RecessoController::class, 'index']);
Route::delete('recesso', [RecessoController::class, 'destroy']);










