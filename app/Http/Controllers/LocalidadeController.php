<?php

namespace App\Http\Controllers;

use App\Http\Resources\LocalidadeResource;
use App\Models\Localidade;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class LocalidadeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_localidades')->only(['index', 'show']);
        $this->middleware('permission:criar_localidades')->only('store');
        $this->middleware('permission:atualizar_localidades')->only('update');
        $this->middleware('permission:excluir_localidades')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Localidade::query();

        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $query->where('setor_id', $user->setor_id)->get();
        }

        $query->when($request->nome, function ($query, $nome){

            $query->where('nome','like', "%$nome%");
        });

        $perPage = $request->input('per_page', 10);
        if($perPage == -1) {
            $perPage = Localidade::count();
        }

        if(!$request->order) {
            $query->orderBy('updated_at', 'desc');
        }

        $order = $request->order;
        $query->when( $request->sortBy, function ($query, $sortBy) use ($order){
            $query->orderBy($sortBy, $order);
        });

        $LocalidadesPaginada= $query->paginate($perPage);
        $localidades = LocalidadeResource::collection($LocalidadesPaginada);

        return response()->json([
            'data' => $localidades,
            'meta' => [
                'current_page' => $LocalidadesPaginada->currentPage(),
                'last_page' => $LocalidadesPaginada->lastPage(),
                'per_page' => $LocalidadesPaginada->perPage(),
                'total' => $LocalidadesPaginada->total(),
            ],
        ], 200);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $request->merge(['setor_id' => $user->setor_id]);
        }

        $data = $request->validate([
            'nome' => [
                'required',
                'min:2',
                Rule::unique('localidades', 'nome')->where(function ($query) use ($request) {
                    return $query->where('setor_id', $request->setor_id);
                }),
            ],
            'setor_id' => 'required|exists:setores,id'
        ]);

        Localidade::create($data);

        return response()->json([
            'message' => 'Localidade criada com sucesso.'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $query = Localidade::query();
        $query->findOrFail($id);

        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $query->where('setor_id', $user->setor_id);
        }

        $localidade = $query->get();

        $localidade = new LocalidadeResource($localidade);
        return response()->json($localidade, 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = auth()->user();

        $data = $request->validate([
            'nome' => [
                'sometimes',
                'min:2',
                Rule::unique('localidades', 'nome')->where(function ($query) use ($user) {
                    return $query->where('setor_id', $user->setor_id);
                })->ignore($id),
            ],
            'setor_id' => 'sometimes|exists:setores,id'
        ]);

        $query = Localidade::query();
        $query->where('id',$id);

        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $query->where('setor_id', $user->setor_id);
        }

        $localidade = $query->first();

        $localidade->update($data);
        $localidade = new LocalidadeResource($localidade);

        return response()->json([
            'message' => 'Localidade atualizada com sucesso',
            'Localidade' => $localidade
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $query = Localidade::query();
        $query->where('id',$id);

        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $query->where('setor_id', $user->setor_id);
        }

        $localidade = $query->first();
        $localidade->delete();

        return response()->json([
            'message' => 'Localidade excluída com sucesso.'
        ], 200);
    }
}
