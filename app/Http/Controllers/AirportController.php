<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Airport;

class AirportController extends Controller
{
    public function index(Request $request)
    {
        $offset = $request->query('offset', 0);
        $limit = $request->query('limit', 10);
        $airports = Airport::getAll($offset, $limit);
        if ($airports->isEmpty()) {
            return response()->json(['message' => 'No airports found'], 404);
        }
        return response()->json($airports);
    }

    public function show($id)
    {
        $airport = Airport::findAirport($id);
        if (!$airport) {
            return response()->json(['message' => 'Airport not found'], 404);
        }
        return response()->json($airport);
    }

    public function store(Request $request, $id)
    {
        $airport = new Airport($request->all());
        $airport->attributes['id'] = $id;
        $airport->saveAirport();
        return response()->json(['message' => 'Airport created successfully']);
    }

    public function update(Request $request, $id)
    {
        $airport = Airport::findAirport($id);
        if ($airport) {
            $airport = new Airport($request->all());
            $airport->attributes['id'] = $id;
            $airport->saveAirport();
            return response()->json(['message' => 'Airport updated successfully']);
        } else {
            return response()->json(['message' => 'Airport not found'], 404);
        }
    }

    public function destroy($id)
    {
        Airport::destroyAirport($id);
        return response()->json(['message' => 'Airport deleted successfully']);
    }

    public function directConnections()
    {
        // Implement this method based on your business logic
    }
}
