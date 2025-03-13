<?php

namespace App\Http\Controllers;

use App\Exceptions\BiometricException;
use App\Models\Biometria;
use App\Models\Funcionario;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BiometriaController extends Controller
{
    private $apiUrl = 'http://localhost:5000/apiservice/';
    private $templates = null;


    public function __construct()
    {
        $this->middleware('permission:registrar_biometria')->only('capturarBiometria');

    }

    public function capturarBiometria(Funcionario $funcionario)
    {
        $response = Http::timeout(0)->get("{$this->apiUrl}capture-hash/");

        if (!$response->successful()) {
            throw new BiometricException();
        }

        if ($response->successful()) {
            $data = $response->json();

            $template = Biometria::upsert([
                'funcionario_id' => $funcionario->id,
                'template' => $data['template'],
            ],
            ['funcionario_id'],
            ['template', 'updated_at']
        );

            $this->limparMemoria();
            $this->carregar();

            return response()->json(['message' => 'Template capturado com sucesso!', 'data' => $template]);
        }

        return response()->json($response->json(), 400);
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

        try {
            Http::post("{$this->apiUrl}load-to-memory/", $this->templates);
            Log::info('carregou templates');


        }
        catch (Throwable $th) {
            throw new BiometricException("Erro inesperado ao conectar-se à API biométrica.", 500);
        }

    }
    public function identificar()
    {
        // $this->limparMemoria();

        // if (!$this->templates) {

        //     $this->carregar();

        // }

        try {
            $response = Http::timeout(0)->get("{$this->apiUrl}identification/");

            if ($response['message']== "Error on Capture: 513") {
                Log::info("Cancelado");
                throw new BiometricException("Captura cancelada");
            }
            if ($response['message'] == "Error on Capture: 261") {
                Log::info("Dispositivo nao encontrado");
                throw new BiometricException("Dispositivo não encontrado");
            }
        } catch (RequestException $e) {
            throw new BiometricException("Falha na requisição biométrica: " . $e->getMessage(), 0, $e);
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
        Http::get("{$this->apiUrl}delete-all-from-memory/");

        return;

    }
}
