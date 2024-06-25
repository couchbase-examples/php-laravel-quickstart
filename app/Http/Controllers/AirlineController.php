<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Airline;

class AirlineController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/airlines/list",
     *     operationId="getAirlinesList",
     *     tags={"Airlines"},
     *     summary="Get list of airlines",
     *     description="Returns list of airlines",
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/airlines/{id}",
     *     operationId="getAirlineById",
     *     tags={"Airlines"},
     *     summary="Get airline information",
     *     description="Returns airline data",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     )
     * )
     */
    public function show($id)
    {
        $airline = Airline::findAirline($id);
        if (!$airline) {
            return response()->json(['message' => 'Airline not found'], 404);
        }
        return response()->json($airline);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/airlines/{id}",
     *     operationId="createAirline",
     *     tags={"Airlines"},
     *     summary="Create a new airline",
     *     description="Create a new airline",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Airline object that needs to be added",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Airline")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, $id)
    {
        try {
            $airline = new Airline($request->all());
            $airline->attributes['id'] = $id;
            $airline->saveAirline();
            return response()->json(['message' => 'Airline created successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Airline not created', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/airlines/{id}",
     *     operationId="updateAirline",
     *     tags={"Airlines"},
     *     summary="Update an existing airline",
     *     description="Update an existing airline",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Airline object that needs to be updated",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Airline")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/v1/airlines/{id}",
     *     operationId="deleteAirline",
     *     tags={"Airlines"},
     *     summary="Delete an airline",
     *     description="Delete an airline",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     )
     * )
     */
    public function destroy($id)
    {
        Airline::destroyAirline($id);
        return response()->json(['message' => 'Airline deleted successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/airlines/to-airport",
     *     operationId="getAirlinesToAirport",
     *     tags={"Airlines"},
     *     summary="Get airlines flying to a destination airport",
     *     description="Returns list of airlines flying to a specific airport",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function toAirport()
    {
        // Implement this method based on your business logic
    }
}
