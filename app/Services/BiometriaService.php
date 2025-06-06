<?php

namespace App\Services;

use App\Exceptions\BiometricException;
use App\Models\Biometria;

class BiometriaService
{
    public function identificar(array $dados)
    {
        
        if ($dados['message'] == "Error on Capture: 513") {
            throw new BiometricException("Captura cancelada");
        }

        if ($dados['message'] == "Error on Capture: 261") {
            throw new BiometricException("Dispositivo não encontrado");
        }

        $biometria = Biometria::find($dados['id']);

        if ($biometria) {
            return [
                'funcionario' => $biometria->funcionario_id,
                'sucesso' => true,
            ];
        }

        return [
            'sucesso' => false
        ];
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

        public function limparMemoria()
    {
        $this->templates = null;

        return;

    }
}
