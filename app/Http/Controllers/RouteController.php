<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Route;

class RouteController extends Controller
{
    protected $allowedAttributes = [
        'airline',
        'airlineid',
        'sourceairport',
        'destinationairport',
        'stops',
        'equipment',
        'schedule',
        'distance'
    ];

    private function validateRequest(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'airline' => 'required|string',
            'airlineid' => 'required|string',
            'sourceairport' => 'required|string',
            'destinationairport' => 'required|string',
            'stops' => 'required|integer',
            'equipment' => 'required|string',
            'schedule' => 'required|array',
            'schedule.*.day' => 'required|integer',
            'schedule.*.utc' => 'required|string',
            'schedule.*.flight' => 'required|string',
            'distance' => 'required|numeric',
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
     *     path="/api/v1/routes/list",
     *     operationId="getRoutesList",
     *     tags={"Routes"},
     *     summary="List Routes",
     *     description="Get List of Routes

This provides an example
of using Query operations
in Couchbase to retrieve a list of routes.

Query operations
are unique to Couchbase and allow you to search, transform, and analyze data in your documents

Code:
[app/Http/Controllers/RouteController.php](https://github.com/couchbase-examples/php-laravel-quickstart/blob/main/app/Http/Controllers/RouteController.php)

Method: index",
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
     *             @OA\Items(ref="#/components/schemas/Route")
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
            $routes = Route::getAllRoutes($offset, $limit);
            if ($routes->isEmpty()) {
                return response()->json(['message' => 'No routes found'], 404);
            }
            return response()->json($routes);
        } catch (\Exception $e) {
            \Log::error('Error fetching routes', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/routes/{id}",
     *     operationId="getRouteById",
     *     tags={"Routes"},
     *     summary="Get Document",
     *     description="Get Route with specified ID

This provides an example
of using Key Value operations
in Couchbase to retrieve a document with specified ID.

Key Value operations
are unique to Couchbase and provide very high speed get/set/delete operations

Code:
[app/Http/Controllers/RouteController.php](https://github.com/couchbase-examples/php-laravel-quickstart/blob/main/app/Http/Controllers/RouteController.php)

Method: show",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Route")
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
            $route = Route::findRoute($id);
            if (!$route) {
                return response()->json(['message' => 'Route not found'], 404);
            }
            return response()->json($route);
        } catch (\Exception $e) {
            \Log::error('Error fetching route', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/routes/{id}",
     *     operationId="createRoute",
     *     tags={"Routes"},
     *     summary="Create Document",
     *     description="Create Route with specified ID

This provides an example
of using Key Value operations
in Couchbase to create a document with specified ID.

Key Value operations
are unique to Couchbase and provide very high speed get/set/delete operations

Code:
[app/Http/Controllers/RouteController.php](https://github.com/couchbase-examples/php-laravel-quickstart/blob/main/app/Http/Controllers/RouteController.php)

Method: store",
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
            $route = new Route($data);
            $route->saveRoute($id);
            return response()->json(['message' => 'Route created successfully'], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating route', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/routes/{id}",
     *     operationId="updateRoute",
     *     tags={"Routes"},
     *     summary="Update Document",
     *     description="Update Route with specified ID

This provides an example
of using Key Value operations
in Couchbase to update a document with specified ID.

Key Value operations
are unique to Couchbase and provide very high speed get/set/delete operations

Code:
[app/Http/Controllers/RouteController.php](https://github.com/couchbase-examples/php-laravel-quickstart/blob/main/app/Http/Controllers/RouteController.php)

Method: update",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Route object that needs to be updated or created",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Route")
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
            $data = $request->only($this->allowedAttributes);
            $route = Route::findRoute($id);

            if ($route) {
                $route->fill($data);
                $route->saveRoute($id);
                return response()->json(['message' => 'Route updated successfully'], 200);
            } else {
                $newRoute = new Route($data);
                $newRoute->saveRoute($id);
                return response()->json(['message' => 'Route created successfully'], 201);
            }
        } catch (\Exception $e) {
            \Log::error('Error updating or creating route', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/routes/{id}",
     *     operationId="deleteRoute",
     *     tags={"Routes"},
     *     summary="Delete Document",
     *     description="Delete Route with specified ID

This provides an example
of using Key Value operations
in Couchbase to delete a document with specified ID.

Key Value operations
are unique to Couchbase and provide very high speed get/set/delete operations

Code:
[app/Http/Controllers/RouteController.php](https://github.com/couchbase-examples/php-laravel-quickstart/blob/main/app/Http/Controllers/RouteController.php)

Method: deleteRoute",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
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
            $route = Route::findRoute($id);

            if (!$route) {
                return response()->json(['message' => 'Route not found'], 404);
            }

            Route::destroyRoute($id);
            return response()->json(['message' => 'Route deleted successfully'], 200);
        } catch (\Exception $e) {
            \Log::error('Error deleting route', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }
}
