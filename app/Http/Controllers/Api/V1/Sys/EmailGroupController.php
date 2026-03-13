<?php

namespace App\Http\Controllers\Api\V1\Sys;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sys\EmailGroup;
use Illuminate\Support\Str;

class EmailGroupController extends Controller
{
    public function index()
    {
        return EmailGroup::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'emails' => 'required|string'
        ]);

        $group = EmailGroup::create($data);

        return response()->json(['message' => 'Grupo criado com sucesso.', 'group' => $group], 201);
    }

    public function show($id)
    {
        $group = EmailGroup::findOrFail($id);

        return response()->json($group);
    }

    public function update(Request $request, $id)
    {
        $group = EmailGroup::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'emails' => 'sometimes|string'
        ]);

        $group->update($data);

        return response()->json(['message' => 'Grupo atualizado com sucesso.', 'group' => $group]);
    }

    public function destroy($id)
    {
        $group = EmailGroup::findOrFail($id);
        $group->delete();

        return response()->json(['message' => 'Grupo excluído com sucesso.']);
    }
}
