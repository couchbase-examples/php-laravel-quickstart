<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Route;

class RouteTest extends TestCase
{
    use RefreshDatabase;

    protected $routeCollection;

    public function setUp(): void
    {
        parent::setUp();

        // Initialize Couchbase route collection
        $this->routeCollection = app('couchbase.routeCollection');
    }

    /**
     * Tests for listing routes
     */

    /** @test */
    public function it_can_get_list_of_routes()
    {
        // Act
        $response = $this->getJson('/api/v1/routes/list');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['route' => ['airline', 'airlineid', 'sourceairport', 'destinationairport', 'stops', 'equipment', 'schedule', 'distance']],
        ]);
    }

    /** @test */
    public function it_returns_404_if_no_routes_found()
    {
        // Act
        $response = $this->getJson('/api/v1/routes/list?sourceAirportCode=unknown');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'No routes found']);
    }

    /**
     * Tests for getting a route by ID
     */

    /** @test */
    public function it_can_get_route_by_id()
    {
        // Arrange
        $route = $this->createRoute();

        // Act
        $response = $this->getJson('/api/v1/routes/' . $route->id);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'airline' => $route->airline,
            'airlineid' => $route->airlineid,
            'sourceairport' => $route->sourceairport,
            'destinationairport' => $route->destinationairport,
            'stops' => $route->stops,
            'equipment' => $route->equipment,
            'schedule' => $route->schedule,
            'distance' => $route->distance,
        ]);

        // Clean up
        $this->routeCollection->remove($route->id);
    }

    /** @test */
    public function it_returns_404_if_route_not_found_by_id()
    {
        // Act
        $response = $this->getJson('/api/v1/routes/non_existing_id');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Route not found']);
    }

    /**
     * Tests for creating a route
     */

    /** @test */
    public function it_can_create_a_new_route()
    {
        // Arrange
        $routeData = $this->validRouteData();
        $routeId = 'route_' . uniqid();

        // Act
        $response = $this->postJson('/api/v1/routes/' . $routeId, $routeData);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(['message' => 'Route created successfully']);

        // Clean up
        $this->routeCollection->remove($routeId);
    }

    /** @test */
    public function it_returns_422_when_creating_route_with_invalid_data()
    {
        // Arrange
        $invalidData = ['airline' => ''];

        // Act
        $response = $this->postJson('/api/v1/routes/route_1', $invalidData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Tests for updating a route
     */

    /** @test */
    public function it_can_update_an_existing_route()
    {
        // Arrange
        $route = $this->createRoute();
        $updatedData = $this->validRouteData();

        // Act
        $response = $this->putJson('/api/v1/routes/' . $route->id, $updatedData);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Route updated successfully']);
    }

    /** @test */
    public function it_can_create_a_new_route_when_updating_non_existing_route()
    {
        // Arrange
        $updatedData = $this->validRouteData();
        $routeId = 'route_' . uniqid();

        // Act
        $response = $this->putJson('/api/v1/routes/' . $routeId, $updatedData);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(['message' => 'Route created successfully']);
    }

    /** @test */
    public function it_returns_422_when_updating_route_with_invalid_data()
    {
        // Arrange
        $route = $this->createRoute();
        $invalidData = ['airline' => ''];

        // Act
        $response = $this->putJson('/api/v1/routes/' . $route->id, $invalidData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Tests for deleting a route
     */

    /** @test */
    public function it_can_delete_a_route()
    {
        // Arrange
        $route = $this->createRoute();

        // Act
        $response = $this->deleteJson('/api/v1/routes/' . $route->id);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Route deleted successfully']);
    }

    /** @test */
    public function it_returns_404_if_route_not_found_for_deletion()
    {
        // Act
        $response = $this->deleteJson('/api/v1/routes/non_existing_id');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Route not found']);
    }

    private function createRoute()
    {
        $routeData = $this->validRouteData();
        $routeId = 'route_' . uniqid();
        $this->routeCollection->upsert($routeId, $routeData);

        return (object) array_merge($routeData, ['id' => $routeId]);
    }

    private function validRouteData()
    {
        return [
            'airline' => 'AA',
            'airlineid' => 'airline_1',
            'sourceairport' => 'JFK',
            'destinationairport' => 'ATL',
            'stops' => 0,
            'equipment' => 'CRJ',
            'schedule' => [
                ['day' => 0, 'utc' => '05:02:00', 'flight' => 'AA288'],
                ['day' => 1, 'utc' => '19:40:00', 'flight' => 'AA230']
            ],
            'distance' => 365.28
        ];
    }
}
