<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hotel;

class HotelController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/hotels/autocomplete",
     *     operationId="searchHotels",
     *     tags={"Hotels"},
     *     summary="Get hotel name suggestions",
     *     description="Returns hotel name suggestions based on the search term",
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function search(Request $request)
    {
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

    /**
     * @OA\Get(
     *     path="/api/v1/hotels/filter",
     *     operationId="filterHotels",
     *     tags={"Hotels"},
     *     summary="Filter hotels based on criteria",
     *     description="Returns a list of hotels based on filter criteria",
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="description",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="country",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Hotel"))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
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
