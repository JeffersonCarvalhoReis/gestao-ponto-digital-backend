<?php
namespace App\Http\Controllers;

use App\Models\Setor;
use Illuminate\Http\Request;

class ConfiguracaoPontoController extends Controller
{
    public function __construct()
    {
        // Apenas admin/super admin podem ver e alterar essa configuração.
        // A opção só é válida para o setor do próprio admin logado.
        $this->middleware('role:admin');
    }

    /**
     * Retorna a configuração de ponto (sem biometria) do setor do usuário logado.
     */
    public function show()
    {
        $setor = Setor::findOrFail(auth()->user()->setor_id);

        return response()->json([
            'bloquear_ponto_sem_biometria' => (bool) $setor->bloquear_ponto_sem_biometria,
        ], 200);
    }

    /**
     * Atualiza a configuração de ponto (sem biometria) do setor do usuário logado.
     * A alteração é sempre aplicada ao setor do próprio admin, nunca a outro setor.
     */
    public function atualizar(Request $request)
    {
        $validated = $request->validate([
            'bloquear_ponto_sem_biometria' => 'required|boolean',
        ]);

        $setor = Setor::findOrFail(auth()->user()->setor_id);
        $setor->update([
            'bloquear_ponto_sem_biometria' => $validated['bloquear_ponto_sem_biometria'],
        ]);

        return response()->json([
            'message'                      => 'Configuração atualizada com sucesso!',
            'setor_id'                     => $setor->id,
            'bloquear_ponto_sem_biometria' => (bool) $setor->bloquear_ponto_sem_biometria,
        ], 200);
    }
}
