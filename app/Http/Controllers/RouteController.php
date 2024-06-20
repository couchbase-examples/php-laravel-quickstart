<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Route;

class RouteController extends Controller
{
    public function index(Request $request)
    {
        $offset = $request->query('offset', 0);
        $limit = $request->query('limit', 10);
        $routes = Route::getAll($offset, $limit);
        if ($routes->isEmpty()) {
            return response()->json(['message' => 'No routes found'], 404);
        }
        return response()->json($routes);
    }

    public function show($id)
    {
        $route = Route::findRoute($id);
        if (!$route) {
            return response()->json(['message' => 'Route not found'], 404);
        }
        return response()->json($route);
    }

    public function store(Request $request, $id)
    {
        $route = new Route($request->all());
        $route->attributes['id'] = $id;
        $route->saveRoute();
        return response()->json(['message' => 'Route created successfully']);
    }

    public function update(Request $request, $id)
    {
        $route = Route::findRoute($id);
        if ($route) {
            $route = new Route($request->all());
            $route->attributes['id'] = $id;
            $route->saveRoute();
            return response()->json(['message' => 'Route updated successfully']);
        } else {
            return response()->json(['message' => 'Route not found'], 404);
        }
    }

    public function destroy($id)
    {
        Route::destroyRoute($id);
        return response()->json(['message' => 'Route deleted successfully']);
    }
}
