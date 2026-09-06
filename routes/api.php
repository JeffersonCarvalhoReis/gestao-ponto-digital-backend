<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BancoHorasController;
use App\Http\Controllers\BiometriaController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\ConfiguracaoPontoController;
use App\Http\Controllers\DiaNaoUtilController;
use App\Http\Controllers\EscalaController;
use App\Http\Controllers\FeriaController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\JustificativaController;
use App\Http\Controllers\LocalidadeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RecessoController;
use App\Http\Controllers\RegistroPontoController;
use App\Http\Controllers\RelatorioPontoController;
use App\Http\Controllers\SetorController;
use App\Http\Controllers\TipoTurnoController;
use App\Http\Controllers\UnidadeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('user', [AuthController::class, 'user']);
    Route::get('/usuario', [UserController::class, 'profile']);
    Route::put('/usuario', [UserController::class, 'updateUser']);
    Route::delete('/usuario', [UserController::class, 'deleteUser']);
    // Biometria
    Route::post('biometria/registrar/{funcionario}', [BiometriaController::class, 'capturarBiometria']);
    Route::delete('biometria/excluir/{id}', [BiometriaController::class, 'excluirBiometria']);
    Route::get('biometria/carregar', [BiometriaController::class, 'carregar']);

    // Registro de Ponto
    Route::post('registro-ponto/biometria', [RegistroPontoController::class, 'buscarFuncionarioBiometria']);
    Route::post('registro-ponto/manual/{funcionario}', [RegistroPontoController::class, 'buscarFuncionarioManualmente']);
    Route::get('/registros-do-dia', [RegistroPontoController::class, 'registroDoDia']);

    // Configuração de ponto (bloqueio de ponto sem biometria por setor)
    Route::get('/configuracao-ponto', [ConfiguracaoPontoController::class, 'show']);
    Route::put('/configuracao-ponto', [ConfiguracaoPontoController::class, 'atualizar']);

    // Relatório de Pontos
    Route::post('relatorio', [RelatorioPontoController::class, 'gerarRelatorio']);
    Route::post('/relatorio-ponto/exportar', [RelatorioPontoController::class, 'exportarRelatorioExcel']);
    Route::post('/relatorio-ponto/individual', [RelatorioPontoController::class, 'gerarRelatorioIndividual']);

    //Recuros
    Route::apiResource('/usuarios', UserController::class);
    Route::apiResource('/localidades', LocalidadeController::class);
    Route::apiResource('/cargos', CargoController::class);
    Route::apiResource('/unidades', UnidadeController::class);
    Route::apiResource('/justificativas', JustificativaController::class);
    Route::apiResource('/setores', SetorController::class);

    // Dados dos funcionarios
    Route::apiResource('/funcionarios', FuncionarioController::class);
    Route::get('/funcionarios/verificar-cpf/{cpf}', [FuncionarioController::class, 'verificaCPF']);
    Route::post('/funcionarios/exportar', [FuncionarioController::class, 'exportarFuncionarios']);
    Route::delete('/funcionarios/apagar-foto/{id}', [FuncionarioController::class, 'apagarFoto']);

    // Dias sem expediente
    Route::post('ferias', [FeriaController::class, 'store']);
    Route::get('ferias', [FeriaController::class, 'index']);
    Route::delete('ferias', [FeriaController::class, 'destroy']);
    Route::post('recesso', [RecessoController::class, 'store']);
    Route::get('recesso', [RecessoController::class, 'index']);
    Route::delete('recesso', [RecessoController::class, 'destroy']);
    Route::apiResource('/dia-nao-util', DiaNaoUtilController::class);
    Route::get('/proximos-feriados', [DiaNaoUtilController::class, 'proximosFeriados']);

    // notificações
    Route::get('/notifications', [NotificationController::class, 'allUnreadNotifications']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'readNotification']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAllNotifications']);
    Route::post('/notifications/read-by-status', [NotificationController::class, 'markAllAsReadByStatus']);

    // Tipos de turno (P, MT, SD, M, F, AL, AS...)
    Route::apiResource('/tipos-turno', TipoTurnoController::class);

    // Escalas (grade mensal de plantões)
    Route::get('/escalas/grade', [EscalaController::class, 'grade']);
    Route::post('/escalas/dia', [EscalaController::class, 'definirDia']);
    Route::post('/escalas/lote', [EscalaController::class, 'definirDiasEmLote']);
    Route::delete('/escalas/dia', [EscalaController::class, 'removerDia']);
    Route::post('/escalas/copiar-mes', [EscalaController::class, 'copiarMes']);

    // Banco de horas (fechamento mensal, sem acumular saldo)
    Route::get('/banco-horas/extrato', [BancoHorasController::class, 'extrato']);
    Route::get('/banco-horas/{funcionario}/extrato', [BancoHorasController::class, 'extratoFuncionario']);
    Route::post('/banco-horas/{funcionario}/fechar-mes', [BancoHorasController::class, 'fecharMesFuncionario']);
    Route::post('/banco-horas/fechar-mes', [BancoHorasController::class, 'fecharMes']);
    Route::post('/banco-horas/{funcionario}/reabrir-mes', [BancoHorasController::class, 'reabrirMes']);

});
