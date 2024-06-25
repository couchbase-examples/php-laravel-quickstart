<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Route;

class RouteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/routes/{id}",
     *     operationId="getRouteById",
     *     tags={"Routes"},
     *     summary="Get route information",
     *     description="Returns route data",
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
            $route = Route::findRoute($id);
            if (!$route) {
                return response()->json(['message' => 'Route not found'], 404);
            }
            return response()->json($route);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching route', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/routes/{id}",
     *     operationId="createRoute",
     *     tags={"Routes"},
     *     summary="Create a new route",
     *     description="Create a new route",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Route object that needs to be added",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Route")
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
            $route = new Route($request->all());
            $route->attributes['id'] = $id;
            $route->saveRoute();
            return response()->json(['message' => 'Route created successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Route not created', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/routes/{id}",
     *     operationId="updateRoute",
     *     tags={"Routes"},
     *     summary="Update an existing route",
     *     description="Update an existing route",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Route object that needs to be updated",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Route")
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
            $route = Route::findRoute($id);
            if ($route) {
                $route = new Route($request->all());
                $route->attributes['id'] = $id;
                $route->saveRoute();
                return response()->json(['message' => 'Route updated successfully']);
            } else {
                return response()->json(['message' => 'Route not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Route not updated', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/routes/{id}",
     *     operationId="deleteRoute",
     *     tags={"Routes"},
     *     summary="Delete a route",
     *     description="Delete a route",
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
            Route::destroyRoute($id);
            return response()->json(['message' => 'Route deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Route not deleted', 'error' => $e->getMessage()], 500);
        }
    }
}
