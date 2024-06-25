<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Airport;

class AirportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/airports/{id}",
     *     operationId="getAirportById",
     *     tags={"Airports"},
     *     summary="Get airport information",
     *     description="Returns airport data",
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
        try {
            $airport = Airport::findAirport($id);
            if (!$airport) {
                return response()->json(['message' => 'Airport not found'], 404);
            }
            return response()->json($airport);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching airport', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/airports/{id}",
     *     operationId="createAirport",
     *     tags={"Airports"},
     *     summary="Create a new airport",
     *     description="Create a new airport",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Airport object that needs to be added",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Airport")
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
            $airport = new Airport($request->all());
            $airport->attributes['id'] = $id;
            $airport->saveAirport();
            return response()->json(['message' => 'Airport created successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Airport not created', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/airports/{id}",
     *     operationId="updateAirport",
     *     tags={"Airports"},
     *     summary="Update an existing airport",
     *     description="Update an existing airport",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Airport object that needs to be updated",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Airport")
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
        try {
            $airport = Airport::findAirport($id);
            if ($airport) {
                $airport = new Airport($request->all());
                $airport->attributes['id'] = $id;
                $airport->saveAirport();
                return response()->json(['message' => 'Airport updated successfully']);
            } else {
                return response()->json(['message' => 'Airport not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Airport not updated', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/airports/{id}",
     *     operationId="deleteAirport",
     *     tags={"Airports"},
     *     summary="Delete an airport",
     *     description="Delete an airport",
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
        try {
            Airport::destroyAirport($id);
            return response()->json(['message' => 'Airport deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Airport not deleted', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/airports/direct-connections",
     *     operationId="getDirectConnections",
     *     tags={"Airports"},
     *     summary="Get direct connections to/from the airport",
     *     description="Returns direct connections for a specific airport",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function directConnections()
    {
        try {
            // Implement this method based on your business logic
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching direct connections', 'error' => $e->getMessage()], 500);
        }
    }
}
