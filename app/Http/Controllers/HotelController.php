<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hotel;

class HotelController extends Controller
{
    public function search(Request $request)
    {
        \Log::info('Searching hotels by name');
        $name = $request->query('name');
        if (!$name) {
            return response()->json(['error' => 'name query parameter is required'], 400);
        }

        try {
            $hotels = Hotel::searchByName($name);
            return response()->json($hotels, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }

    public function filter(Request $request)
    {
        $filters = $request->only(['name', 'title', 'description', 'country', 'city', 'state']);
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        try {
            $hotels = Hotel::filter($filters, $offset, $limit);
            return response()->json($hotels, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
