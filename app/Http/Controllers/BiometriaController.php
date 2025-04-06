<?php

namespace App\Http\Controllers;

use App\Exceptions\BiometricException;
use App\Models\Biometria;
use App\Models\Funcionario;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BiometriaController extends Controller
{

    private $templates = null;


    public function __construct()
    {
        $this->middleware('permission:registrar_biometria')->only('capturarBiometria');

    }

    public function capturarBiometria(Funcionario $funcionario, Request $request)
    {
        $message = $request->input('message'); // pegando 'message' enviado do frontend

        if ($message === "Error on Capture: 513") {
            throw new BiometricException("Captura cancelada");
        }

        if ($message === "Error on Capture: 261") {
            throw new BiometricException("Dispositivo não encontrado");
        }

        if ($request->input('success')) {
            $data = $request->all();

            $template = Biometria::upsert(
                [
                    'funcionario_id' => $funcionario->id,
                    'template' => $data['template'],
                ],
                ['funcionario_id'],
                ['template', 'updated_at']
            );

            $this->limparMemoria();
            $this->carregar();

            return response()->json([
                'message' => 'Template capturado com sucesso!',
                'data' => $template
            ]);
        }

        // se chegar aqui, quer dizer que não houve sucesso e nem erro tratado acima
        return response()->json([
            'message' => 'Erro desconhecido ao capturar biometria',
            'dados_recebidos' => $request->all()
        ], 400);
    }


    public function carregar()
    {
        $unidadeId = auth()->user()->unidade_id;
        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'super admin'])) {
          $this->templates = Biometria::all(['id', 'template'])->toArray();
        } else {
           $this->templates = Biometria::whereHas('funcionario', function ($query) use ($unidadeId) {
                $query->where('unidade_id', $unidadeId);
                })->get(['id', 'template'])->toArray();
        }

        return $this->templates;

    }
    public function identificar($response)
    {
        // $this->limparMemoria();

        // if (!$this->templates) {

        //     $this->carregar();

        // }

            if ($response['message']== "Error on Capture: 513") {
                Log::info("Cancelado");
                throw new BiometricException("Captura cancelada");
            }
            if ($response['message'] == "Error on Capture: 261") {
                throw new BiometricException("Dispositivo não encontrado");
            }


        $biometria = Biometria::find($response->json('id'));

        if ($biometria) {

            $funcionario = $biometria->funcionario_id;
        }

        return $biometria
            ? response()->json(['message' => 'Biometria encontrada com sucesso', 'funcionario' => $funcionario, 'sucesso' => true], 200)
            : response()->json(['message' => 'Biometria não encontrada', 'sucesso' => false], 404);
    }

    public function excluirBiometria(string $id)
    {

            $biometria = Biometria::findOrFail($id);
            $biometria->delete();
            $this->limparMemoria();
            $this->carregar();

            return response()->json([
                'message' => 'Biometria excluída com sucesso.'
            ], 200);
    }

    public function limparMemoria()
    {
        $this->templates = null;

        return;

    }
}
