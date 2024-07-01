<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Airport;

class AirportController extends Controller
{
    protected $allowedAttributes = [
        'airportname',
        'city',
        'country',
        'faa',
        'icao',
        'tz',
        'geo'
    ];

    private function validateRequest(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'airportname' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'faa' => 'nullable|string|size:3',
            'icao' => 'required|string|size:4',
            'tz' => 'required|string',
            'geo.lat' => 'required|numeric',
            'geo.lon' => 'required|numeric',
            'geo.alt' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $disallowedAttributes = array_diff(array_keys($request->all()), $this->allowedAttributes);
        if (!empty($disallowedAttributes)) {
            return response()->json(['message' => 'Disallowed attributes: ' . implode(', ', $disallowedAttributes)], 422);
        }

        return null;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/airports/list",
     *     operationId="getAirportsList",
     *     tags={"Airports"},
     *     summary="Get list of airports",
     *     description="Returns list of airports",
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         example=0,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         example=10,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Airport")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $offset = $request->query('offset', 0);
            $limit = $request->query('limit', 10);
            $airports = Airport::getAllAirports($offset, $limit);
            if ($airports->isEmpty()) {
                return response()->json(['message' => 'No airports found'], 404);
            }
            return response()->json($airports);
        } catch (\Exception $e) {
            \Log::error('Error fetching airports', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }

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
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Airport")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
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
            \Log::error('Error fetching airport', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
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
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function store(Request $request, $id)
    {
        if ($errorResponse = $this->validateRequest($request)) {
            return $errorResponse;
        }

        try {
            $data = $request->only($this->allowedAttributes);
            $airport = new Airport($data);
            $airport->saveAirport($id);
            return response()->json(['message' => 'Airport created successfully'], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating airport', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/airports/{id}",
     *     operationId="updateAirport",
     *     tags={"Airports"},
     *     summary="Update an existing airport or create a new one if it does not exist",
     *     description="Update an existing airport or create a new one if it does not exist",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Airport object that needs to be updated or created",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Airport")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Resource created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        if ($errorResponse = $this->validateRequest($request)) {
            return $errorResponse;
        }

        try {
            \Log::info('Updating airport', ['id' => $id, 'data' => $request->all()]);

            $data = $request->only($this->allowedAttributes);
            $airport = Airport::findAirport($id);

            if ($airport) {
                $airport->fill($data);
                $airport->saveAirport($id);
                return response()->json(['message' => 'Airport updated successfully'], 200);
            } else {
                $newAirport = new Airport($data);
                $newAirport->saveAirport($id);
                return response()->json(['message' => 'Airport created successfully'], 201);
            }
        } catch (\Exception $e) {
            \Log::error('Error updating or creating airport', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $airport = Airport::findAirport($id);

            if (!$airport) {
                return response()->json(['message' => 'Airport not found'], 404);
            }

            Airport::destroyAirport($id);
            return response()->json(['message' => 'Airport deleted successfully'], 200);
        } catch (\Exception $e) {
            \Log::error('Error deleting airport', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/airports/direct-connections",
     *     operationId="getDirectConnections",
     *     tags={"Airports"},
     *     summary="Get airports with direct flights from a target airport",
     *     description="Returns list of airports that have direct flights from a target airport",
     *     @OA\Parameter(
     *         name="sourceAirportCode",
     *         in="query",
     *         required=true,
     *         example="ATL",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         example=0,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         example=10,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function getDirectConnections(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'sourceAirportCode' => 'required|string',
            'offset' => 'integer',
            'limit' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        try {
            $sourceAirportCode = $request->query('sourceAirportCode');
            $offset = $request->query('offset', 0);
            $limit = $request->query('limit', 10);

            $airports = Airport::getDirectConnections($sourceAirportCode, $offset, $limit);
            if ($airports->isEmpty()) {
                return response()->json(['message' => 'No direct connections found'], 404);
            }

            $formattedAirports = $airports->map(function ($airport) {
                return $airport['destinationairport'];
            });

            return response()->json($formattedAirports);
        } catch (\Exception $e) {
            \Log::error('Error fetching direct connections', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }
}
