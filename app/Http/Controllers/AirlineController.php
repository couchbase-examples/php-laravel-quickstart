<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Airline;

class AirlineController extends Controller
{
    public function index(Request $request)
    {
        $offset = $request->query('offset', 0);
        $limit = $request->query('limit', 10);
        $airlines = Airline::getAll($offset, $limit);
        if ($airlines->isEmpty()) {
            return response()->json(['message' => 'No airlines found'], 404);
        }
        return response()->json($airlines);
    }

    public function show($id)
    {
        $airline = Airline::findAirline($id);
        if (!$airline) {
            return response()->json(['message' => 'Airline not found'], 404);
        }
        return response()->json($airline);
    }

    public function store(Request $request, $id)
    {
        $airline = new Airline($request->all());
        $airline->attributes['id'] = $id;
        $airline->saveAirline();
        return response()->json(['message' => 'Airline created successfully']);
    }

    public function update(Request $request, $id)
    {
        $airline = Airline::findAirline($id);
        if ($airline) {
            $airline = new Airline($request->all());
            $airline->attributes['id'] = $id;
            $airline->saveAirline();
            return response()->json(['message' => 'Airline updated successfully']);
        } else {
            return response()->json(['message' => 'Airline not found'], 404);
        }
    }

    public function destroy($id)
    {
        Airline::destroyAirline($id);
        return response()->json(['message' => 'Airline deleted successfully']);
    }

    public function toAirport()
    {
        // Implement this method based on your business logic
    }
}
