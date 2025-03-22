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

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function(){

    Route::get('user', [AuthController::class, 'user']);
    // Biometria
    Route::post('biometria/registrar/{funcionario}', [BiometriaController::class, 'capturarBiometria']);
    Route::delete('biometria/excluir/{id}', [BiometriaController::class, 'excluirBiometria']);
    Route::get('biometria/carregar', [BiometriaController::class, 'carregar']);

    // Registro de Ponto
    Route::post('registro-ponto/biometria', [RegistroPontoController::class, 'buscarFuncionarioBiometria']);
    Route::post('registro-ponto/manual/{funcionario}', [RegistroPontoController::class, 'buscarFuncionarioManualmente']);
    Route::get('/registros-do-dia', [RegistroPontoController::class, 'registroDoDia']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Relatório de Pontos
    //ainda falta implemnetar
    Route::post('relatorio', [RelatorioPontoController::class, 'gerarRelatorio']);

    //Recuros
    Route::apiResource('/usuarios',  UserController::class);
    Route::apiResource('/localidades',   LocalidadeController::class);
    Route::apiResource('/cargos',  CargoController::class);
    Route::apiResource('/unidades',  UnidadeController::class);
    Route::apiResource('/justificativas',  JustificativaController::class);

    // Dados dos funcionarios
    Route::apiResource('/funcionarios',  FuncionarioController::class);
    Route::get('/funcionarios/verificar-cpf/{cpf}',  [FuncionarioController::class, 'verificaCPF']);
    Route::delete('/funcionarios/apagar-foto/{id}',  [FuncionarioController::class, 'apagarFoto']);
    Route::post('dados-contratos', [DadosContratoController::class, 'store']);
    Route::put('dados-contratos/{id}', [DadosContratoController::class, 'update']);

    // Dias sem expediente
    Route::post('ferias', [FeriaController::class, 'store']);
    Route::get('ferias', [FeriaController::class, 'index']);
    Route::delete('ferias', [FeriaController::class, 'destroy']);
    Route::post('recesso', [RecessoController::class, 'store']);
    Route::get('recesso', [RecessoController::class, 'index']);
    Route::delete('recesso', [RecessoController::class, 'destroy']);
    Route::apiResource('/dia-nao-util',  DiaNaoUtilController::class);
    Route::get( '/proximos-feriados', [DiaNaoUtilController::class, 'proximosFeriados']);

      // notificações não lidas para todos os usuarios
    Route::get('/notifications', function () {
        return [
            'notifications' => auth()->user()->unreadNotifications
        ];
    });

    // marcar notificação como lida
    Route::post('/notifications/{id}/read', function ($id) {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    });

    Route::post('/relatorio-ponto/exportar', [RelatorioPontoController::class, 'exportarRelatorioExcel']);
});













