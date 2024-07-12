<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Hotel;

class HotelController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/hotels/autocomplete",
     *     operationId="searchHotels",
     *     tags={"Hotels"},
     *     summary="Search for hotels by name",
     *     description="Returns a list of hotels that match the provided name",
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
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
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Validation Error"),
     *             @OA\Property(property="message", type="string", example="Invalid request data"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function search(Request $request)
    {
        if (!$request->has('name')) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'Invalid request data',
                'errors' => [
                    'name' => ['The name query parameter is required.']
                ]
            ], 422);
        }

        try {
            $hotels = Hotel::searchByName($request->query('name'));
            return response()->json($hotels, 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching hotels', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/hotels/filter",
     *     operationId="filterHotels",
     *     tags={"Hotels"},
     *     summary="Filter hotels by various attributes",
     *     description="Returns a list of hotels that match the provided filters",
     *     @OA\Parameter(
     *         name="hotel[name]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="hotel[title]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="hotel[description]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="hotel[country]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="hotel[city]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="hotel[state]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
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
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Validation Error"),
     *             @OA\Property(property="message", type="string", example="Invalid request data"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function filter(Request $request)
    {
        try {
            $filters = $request->only(['name', 'title', 'description', 'country', 'city', 'state']);
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);

            $hotels = Hotel::filter($filters, $offset, $limit);
            return response()->json($hotels, 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching hotels', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }
}
