<?php

namespace App\Http\Controllers\Api\V1\Chk;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Chk\Area;
use App\Models\Chk\Checklist;

class AreaController
{
    public function index($checklistId): JsonResponse
    {
        $areas = Area::where('checklist_id', $checklistId)->orderBy('order')->get();
        if ($areas->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No areas found for this checklist'
            ], 404);
        }
        return response()->json($areas);
    }

    public function show($id): JsonResponse
    {
        $area = Area::findOrFail($id);
        if (!$area) {
            return response()->json([
                'status' => 'error',
                'message' => 'Area not found'
            ], 404);
        }
        return response()->json($area);
    }

    public function store(Request $request, $checklistId): JsonResponse
    {
        $checklist = Checklist::find($checklistId);
        if (!$checklist) {
            return response()->json([
                'status' => 'error',
                'message' => 'Checklist not found'
            ], 404);
        }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $area = Area::create([
            'checklist_id' => $checklistId,
            'title' => $validatedData['title'],
            'description' => $validatedData['description'] ?? null,
            'order' => $validatedData['order'] ?? 0,
        ]);

        return response()->json($area, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $area = Area::findOrFail($id);
        if (!$area) {
            return response()->json([
                'status' => 'error',
                'message' => 'Area not found'
            ], 404);
        }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $area->update($validatedData);

        return response()->json($area);
    }

    public function destroy($id): JsonResponse
    {
        $area = Area::findOrFail($id);
        if (!$area) {
            return response()->json([
                'status' => 'error',
                'message' => 'Area not found'
            ], 404);
        }

        $area->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Area deleted successfully'
        ]);
    }
}
