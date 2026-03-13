<?php

namespace App\Http\Controllers\Api\V1\Chk;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Models\Chk\Checklist;

class ChecklistController
{
    public function index(): JsonResponse
    {
        $checklists = Checklist::all();

        if(!$checklists->empty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No checklists found'
            ], 404);
        }

        return response()->json($checklists);
    }

    public function show($id): JsonResponse
    {

        $checklist = Checklist::find($id);

        if(!$checklist){
            return response()->json([
                'status' => 'error',
                'message' => 'Checklist not found'
            ], 404);
        }

        return response()->json($checklist);
    }

    public function create(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'email_group_id' => 'nullable|uuid',
        ]);

        $usuario = $request->get('login');

        $checklist = Checklist::create($validatedData);

        return response()->json($checklist, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'email_group_id' => 'nullable|uuid',
        ]);

        $checklist = Checklist::find($id);

        if(!$checklist){
            return response()->json([
                'status' => 'error',
                'message' => 'Checklist not found'
            ], 404);
        }

        $checklist->update($validatedData);

        return response()->json($checklist);
    }

    public function destroy($id): JsonResponse
    {
        $checklist = Checklist::find($id);

        if(!$checklist){
            return response()->json([
                'status' => 'error',
                'message' => 'Checklist not found'
            ], 404);
        }

        $checklist->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Checklist deleted successfully'
        ]);
    }
}
