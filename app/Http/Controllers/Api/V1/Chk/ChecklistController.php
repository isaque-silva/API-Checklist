<?php

namespace App\Http\Controllers\Api\V1\Chk;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Chk\Checklist;

class ChecklistController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->get('auth_user');
        $query = Checklist::query();

        if (!$user->is_super_admin) {
            $query->forClient($user->client_id);
        }

        $checklists = $query->get();

        return response()->json($checklists);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->get('auth_user');
        $query = Checklist::query();

        if (!$user->is_super_admin) {
            $query->forClient($user->client_id);
        }

        $checklist = $query->find($id);

        if (!$checklist) {
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

        $user = $request->get('auth_user');
        $validatedData['client_id'] = $user->client_id;

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

        $user = $request->get('auth_user');
        $query = Checklist::query();

        if (!$user->is_super_admin) {
            $query->forClient($user->client_id);
        }

        $checklist = $query->find($id);

        if (!$checklist) {
            return response()->json([
                'status' => 'error',
                'message' => 'Checklist not found'
            ], 404);
        }

        $checklist->update($validatedData);

        return response()->json($checklist);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->get('auth_user');
        $query = Checklist::query();

        if (!$user->is_super_admin) {
            $query->forClient($user->client_id);
        }

        $checklist = $query->find($id);

        if (!$checklist) {
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
