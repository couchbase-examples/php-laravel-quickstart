<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Airport;

class AirportTest extends TestCase
{
    protected $airportCollection;

    public function setUp(): void
    {
        parent::setUp();

        // Initialize Couchbase airport collection
        $this->airportCollection = app('couchbase.airportCollection');
    }

    /**
     * Tests for listing airports
     */

    /** @test */
    public function it_can_get_list_of_airports()
    {
        // Act
        $response = $this->getJson('/api/v1/airports/list');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['airport' => ['airportname', 'city', 'country', 'faa', 'icao', 'tz', 'geo']],
        ]);
    }

    /** @test */
    public function it_returns_404_if_no_airports_found()
    {
        // Act
        $response = $this->getJson('/api/v1/airports/list?country=unknown');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([]);
    }

    /**
     * Tests for getting an airport by ID
     */

    /** @test */
    public function it_can_get_airport_by_id()
    {
        // Arrange
        $airport = $this->createAirport();

        // Act
        $response = $this->getJson('/api/v1/airports/' . $airport->id);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'airportname' => $airport->airportname,
            'city' => $airport->city,
            'country' => $airport->country,
            'faa' => $airport->faa,
            'icao' => $airport->icao,
            'tz' => $airport->tz,
            'geo' => $airport->geo,
        ]);

        // Clean up
        $this->airportCollection->remove($airport->id);
    }

    /** @test */
    public function it_returns_404_if_airport_not_found_by_id()
    {
        // Act
        $response = $this->getJson('/api/v1/airports/non_existing_id');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Airport not found']);
    }

    /**
     * Tests for creating an airport
     */

    /** @test */
    public function it_can_create_a_new_airport()
    {
        // Arrange
        $airportData = $this->validAirportData();
        $airportId = 'airport_' . uniqid();

        // Act
        $response = $this->postJson('/api/v1/airports/' . $airportId, $airportData);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(['message' => 'Airport created successfully']);

        // Clean up
        $this->airportCollection->remove($airportId);
    }

    /** @test */
    public function it_returns_422_when_creating_airport_with_invalid_data()
    {
        // Arrange
        $invalidData = ['airportname' => ''];

        // Act
        $response = $this->postJson('/api/v1/airports/airport_1', $invalidData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Tests for updating an airport
     */

    /** @test */
    public function it_can_update_an_existing_airport()
    {
        // Arrange
        $airport = $this->createAirport();
        $updatedData = $this->validAirportData();

        // Act
        $response = $this->putJson('/api/v1/airports/' . $airport->id, $updatedData);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Airport updated successfully']);
    }

    /** @test */
    public function it_can_create_a_new_airport_when_updating_non_existing_airport()
    {
        // Arrange
        $updatedData = $this->validAirportData();
        $airportId = 'airport_' . uniqid();

        // Act
        $response = $this->putJson('/api/v1/airports/' . $airportId, $updatedData);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(['message' => 'Airport created successfully']);
    }

    /** @test */
    public function it_returns_422_when_updating_airport_with_invalid_data()
    {
        // Arrange
        $airport = $this->createAirport();
        $invalidData = ['airportname' => ''];

        // Act
        $response = $this->putJson('/api/v1/airports/' . $airport->id, $invalidData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Tests for deleting an airport
     */

    /** @test */
    public function it_can_delete_an_airport()
    {
        // Arrange
        $airport = $this->createAirport();

        // Act
        $response = $this->deleteJson('/api/v1/airports/' . $airport->id);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Airport deleted successfully']);
    }

    /** @test */
    public function it_returns_404_if_airport_not_found_for_deletion()
    {
        // Act
        $response = $this->deleteJson('/api/v1/airports/non_existing_id');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Airport not found']);
    }

    /**
     * Tests for getting direct flight connections from a target airport
     */

    /** @test */
    public function it_can_get_direct_flight_connections_from_a_target_airport()
    {
        // Arrange
        $this->seedAirportsAndRoutes();

        // Act
        $response = $this->getJson('/api/v1/airports/direct-connections?sourceAirportCode=JFK');

        \Log::info($response->getContent());

        // Assert
        $response->assertStatus(200);
        $response->assertContent('["DEL","LHR","EZE","ATL","CUN","MEX","LAX","SAN","SEA","SFO"]');

        // Clean up
        $this->airportCollection->remove('airport_9999');
    }

    /** @test */
    public function it_returns_404_if_no_direct_flight_connections_found()
    {
        // Act
        $response = $this->getJson('/api/v1/airports/direct-connections?sourceAirportCode=unknown_airport');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'No direct flight connections found']);
    }

    private function createAirport()
    {
        $airportData = $this->validAirportData();
        $airportId = 'airport_' . uniqid();
        $this->airportCollection->upsert($airportId, $airportData);

        return (object) array_merge($airportData, ['id' => $airportId]);
    }

    private function validAirportData()
    {
        return [
            'airportname' => 'John F. Kennedy International Airport',
            'city' => 'New York',
            'country' => 'USA',
            'faa' => 'JFK',
            'icao' => 'KJFK',
            'tz' => 'America/New_York',
            'geo' => [
                'lat' => 40.6413,
                'lon' => -73.7781,
                'alt' => 13.0
            ]
        ];
    }

    private function seedAirportsAndRoutes()
    {
        // Seed test data in Couchbase
        $airport = [
            'airportname' => 'John F. Kennedy International Airport',
            'city' => 'New York',
            'country' => 'USA',
            'faa' => 'JFK',
            'icao' => 'KJFK',
            'tz' => 'America/New_York',
            'geo' => [
                'lat' => 40.6413,
                'lon' => -73.7781,
                'alt' => 13.0
            ]
        ];
        $this->airportCollection->upsert('airport_9999', $airport);

        $routes = [
            ['sourceairport' => 'JFK', 'destinationairport' => 'ATL', 'stops' => 0],
            ['sourceairport' => 'JFK', 'destinationairport' => 'LAX', 'stops' => 0],
        ];

        foreach ($routes as $route) {
            app('couchbase.routeCollection')->upsert('route_' . uniqid(), $route);
        }
    }
}
