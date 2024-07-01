<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Airline;

class AirlineTest extends TestCase
{
    use RefreshDatabase;

    protected $airlineCollection;

    public function setUp(): void
    {
        parent::setUp();

        // Initialize Couchbase airline collection
        $this->airlineCollection = app('couchbase.airlineCollection');
    }

    /**
     * Tests for listing airlines
     */

    /** @test */
    public function it_can_get_list_of_airlines()
    {
        // Act
        $response = $this->getJson('/api/v1/airlines/list');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['airline' => ['callsign', 'country', 'iata', 'icao', 'name']],
        ]);
    }

    /** @test */
    public function it_returns_404_if_no_airlines_found()
    {
        // Act
        $response = $this->getJson('/api/v1/airlines/list?country=unknown');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'No airlines found']);
    }

    /**
     * Tests for getting an airline by ID
     */

    /** @test */
    public function it_can_get_airline_by_id()
    {
        // Arrange
        $airline = $this->createAirline(); // airline_6682558b0357d

        // Act
        $response = $this->getJson('/api/v1/airlines/' . $airline->id);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'callsign' => $airline->callsign,
            'country' => $airline->country,
            'iata' => $airline->iata,
            'icao' => $airline->icao,
            'name' => $airline->name,
        ]);

        // Clean up
        $this->airlineCollection->remove($airline->id);
    }

    /** @test */
    public function it_returns_404_if_airline_not_found_by_id()
    {
        // Act
        $response = $this->getJson('/api/v1/airlines/non_existing_id');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Airline not found']);
    }

    /**
     * Tests for creating an airline
     */

    /** @test */
    public function it_can_create_a_new_airline()
    {
        // Arrange
        $airlineData = $this->validAirlineData();
        $airlineId = 'airline_' . uniqid();

        // Act
        $response = $this->postJson('/api/v1/airlines/' . $airlineId, $airlineData);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(['message' => 'Airline created successfully']);

        // Clean up
        $this->airlineCollection->remove($airlineId);
    }

    /** @test */
    public function it_returns_422_when_creating_airline_with_invalid_data()
    {
        // Arrange
        $invalidData = ['callsign' => ''];

        // Act
        $response = $this->postJson('/api/v1/airlines/airline_1', $invalidData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Tests for updating an airline
     */

    /** @test */
    public function it_can_update_an_existing_airline()
    {
        // Arrange
        $airline = $this->createAirline();
        $updatedData = $this->validAirlineData();

        // Act
        $response = $this->putJson('/api/v1/airlines/' . $airline->id, $updatedData);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Airline updated successfully']);
    }

    /** @test */
    public function it_can_create_a_new_airline_when_updating_non_existing_airline()
    {
        // Arrange
        $updatedData = $this->validAirlineData();
        $airlineId = 'airline_' . uniqid();

        // Act
        $response = $this->putJson('/api/v1/airlines/' . $airlineId, $updatedData);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(['message' => 'Airline created successfully']);
    }

    /** @test */
    public function it_returns_422_when_updating_airline_with_invalid_data()
    {
        // Arrange
        $airline = $this->createAirline();
        $invalidData = ['callsign' => ''];

        // Act
        $response = $this->putJson('/api/v1/airlines/' . $airline->id, $invalidData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Tests for deleting an airline
     */

    /** @test */
    public function it_can_delete_an_airline()
    {
        // Arrange
        $airline = $this->createAirline();

        // Act
        $response = $this->deleteJson('/api/v1/airlines/' . $airline->id);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Airline deleted successfully']);
    }

    /** @test */
    public function it_returns_404_if_airline_not_found_for_deletion()
    {
        // Act
        $response = $this->deleteJson('/api/v1/airlines/non_existing_id');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Airline not found']);
    }

    /**
     * Tests for getting airlines flying to a specific airport
     */

    /** @test */
    public function it_can_get_airlines_flying_to_a_specific_airport()
    {
        // Arrange
        $this->seedAirlinesAndRoutes();

        // Act
        $response = $this->getJson('/api/v1/airlines/to-airport/ATL');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['callsign', 'country', 'iata', 'icao', 'name'],
        ]);

        // Clean up
        $this->airlineCollection->remove('airline_9999');
    }

    /** @test */
    public function it_returns_404_if_no_airlines_flying_to_a_specific_airport()
    {
        // Act
        $response = $this->getJson('/api/v1/airlines/to-airport/unknown_airport');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'No airlines found']);
    }

    private function createAirline()
    {
        $airlineData = $this->validAirlineData();
        $airlineId = 'airline_' . uniqid();
        $this->airlineCollection->upsert($airlineId, $airlineData);

        return (object) array_merge($airlineData, ['id' => $airlineId]);
    }

    private function validAirlineData()
    {
        return [
            'callsign' => 'AA',
            'country' => 'USA',
            'iata' => 'AA',
            'icao' => 'AAL',
            'name' => 'American Airlines',
        ];
    }

    private function seedAirlinesAndRoutes()
    {
        // Seed test data in Couchbase
        $airline = ['callsign' => 'AA', 'country' => 'USA', 'iata' => 'AA', 'icao' => 'AAL', 'name' => 'American Airlines'];
        $this->airlineCollection->upsert('airline_9999', $airline);

        $routes = [
            ['airlineid' => 'airline_1', 'destinationairport' => 'ATL', 'sourceairport' => 'JFK', 'stops' => 0],
            ['airlineid' => 'airline_1', 'destinationairport' => 'ATL', 'sourceairport' => 'LAX', 'stops' => 0],
        ];

        foreach ($routes as $route) {
            app('couchbase.routeCollection')->upsert('route_' . uniqid(), $route);
        }
    }
}
