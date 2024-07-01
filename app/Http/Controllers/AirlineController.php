<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Airline;

class AirlineController extends Controller
{
    protected $allowedAttributes = [
        'callsign',
        'country',
        'iata',
        'icao',
        'name'
    ];

    private function validateRequest(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'callsign' => 'required|string',
            'country' => 'required|string',
            'iata' => 'required|string|size:2',
            'icao' => 'required|string|size:3',
            'name' => 'required|string',
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
     *     path="/api/v1/airlines/list",
     *     operationId="getAirlinesList",
     *     tags={"Airlines"},
     *     summary="Get list of airlines",
     *     description="Returns list of airlines",
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
     *     @OA\Parameter(
     *         name="country",
     *         in="query",
     *         required=false,
     *         example="United States",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Airline")
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
            $country = $request->query('country');
            $airlines = Airline::getAllAirlinesByCountry($country, $offset, $limit);
            if ($airlines->isEmpty()) {
                return response()->json(['message' => 'No airlines found'], 404);
            }
            return response()->json($airlines);
        } catch (\Exception $e) {
            \Log::error('Error fetching airlines', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
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
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Airline")
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
            $airline = Airline::findAirline($id);
            if (!$airline) {
                return response()->json(['message' => 'Airline not found'], 404);
            }
            return response()->json($airline);
        } catch (\Exception $e) {
            \Log::error('Error fetching airline', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
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
            $airline = new Airline($data);
            $airline->saveAirline($id);
            return response()->json(['message' => 'Airline created successfully'], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating airline', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/airlines/{id}",
     *     operationId="updateAirline",
     *     tags={"Airlines"},
     *     summary="Update an existing airline or create a new one if it does not exist",
     *     description="Update an existing airline or create a new one if it does not exist",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Airline object that needs to be updated or created",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Airline")
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
            \Log::info('Updating airline', ['id' => $id, 'data' => $request->all()]);

            $data = $request->only($this->allowedAttributes);
            $airline = Airline::findAirline($id);

            if ($airline) {
                $airline->fill($data);
                $airline->saveAirline($id);
                return response()->json(['message' => 'Airline updated successfully'], 200);
            } else {
                $newAirline = new Airline($data);
                $newAirline->saveAirline($id);
                return response()->json(['message' => 'Airline created successfully'], 201);
            }
        } catch (\Exception $e) {
            \Log::error('Error updating or creating airline', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
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
            $airline = Airline::findAirline($id);

            if (!$airline) {
                return response()->json(['message' => 'Airline not found'], 404);
            }

            Airline::destroyAirline($id);
            return response()->json(['message' => 'Airline deleted successfully'], 200);
        } catch (\Exception $e) {
            \Log::error('Error deleting airline', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/airlines/to-airport/{destinationAirportCode}",
     *     operationId="getAirlinesToAirport",
     *     tags={"Airlines"},
     *     summary="Get airlines flying to a destination airport",
     *     description="Returns list of airlines flying to a specific airport",
     *     @OA\Parameter(
     *         name="destinationAirportCode",
     *         in="path",
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
     *             @OA\Items(ref="#/components/schemas/Airline")
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
    public function toAirport(Request $request, $destinationAirportCode)
    {
        $validator = \Validator::make(['destinationAirportCode' => $destinationAirportCode], [
            'destinationAirportCode' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        try {
            $offset = $request->query('offset', 0);
            $limit = $request->query('limit', 10);

            $airlines = Airline::getAirlinesToAirport($destinationAirportCode, $offset, $limit);
            if ($airlines->isEmpty()) {
                return response()->json(['message' => 'No airlines found'], 404);
            }
            return response()->json($airlines);
        } catch (\Exception $e) {
            \Log::error('Error fetching airlines to airport', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }
}
