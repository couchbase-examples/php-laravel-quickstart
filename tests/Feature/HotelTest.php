<?php

namespace Tests\Feature;

use Tests\TestCase;

class HotelTest extends TestCase
{
    protected $hotelCollection;

    public function setUp(): void
    {
        parent::setUp();

        // Initialize Couchbase hotel collection
        $this->hotelCollection = app('couchbase.hotelCollection');
    }

    /**
     * Tests for searching hotels by name
     */

    /** @test */
    public function it_can_search_hotels_by_name()
    {
        // Act
        $response = $this->getJson('/api/v1/hotels/autocomplete?name=Hotel Drisco');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Hotel Drisco']);
    }

    /** @test */
    public function it_returns_empty_when_no_hotels_match_name()
    {
        // Act
        $response = $this->getJson('/api/v1/hotels/autocomplete?name=NonExistentHotel');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([]);
    }

    /** @test */
    public function it_can_search_hotels_by_partial_name()
    {
        // Act
        $response = $this->getJson('/api/v1/hotels/autocomplete?name=Hotel');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Hotel Diva']);
        $response->assertJsonFragment(['name' => 'Hotel Rex']);
    }

    /** @test */
    public function it_returns_422_when_searching_without_name()
    {
        // Act
        $response = $this->getJson('/api/v1/hotels/autocomplete');

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Tests for filtering hotels
     */

    /** @test */
    public function it_can_filter_hotels_by_exact_attributes()
    {
        // Arrange
        $filterParams = [
            'country' => 'United States',
            'city' => 'San Francisco'
        ];

        // Act
        $response = $this->getJson('/api/v1/hotels/filter?' . http_build_query($filterParams));

        // Assert
        $response->assertStatus(200);

        $response->assertJsonFragment(['name' => 'Hotel Rex']);
        $response->assertJsonFragment(['name'=> 'Cova Hotel']);
    }

    /** @test */
    public function it_returns_empty_when_no_hotels_match_filter()
    {
        // Arrange
        $filterParams = [
            'name' => 'NonExistentHotel',
            'country' => 'Nowhere'
        ];

        // Act
        $response = $this->getJson('/api/v1/hotels/filter?' . http_build_query($filterParams));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([]);
    }

    /** @test */
    public function it_can_filter_hotels_by_multiple_attributes()
    {
        // Arrange
        $filterParams = [
            'city' => 'San Francisco',
            'state' => 'California',
            'country' => 'United States'
        ];

        // Act
        $response = $this->getJson('/api/v1/hotels/filter?' . http_build_query($filterParams));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment(['city' => 'San Francisco', 'state' => 'California']);
    }

    /** @test */
    public function it_can_filter_hotels_with_offset_and_limit()
    {
        // Arrange
        $filterParams = [
            'country' => 'United States'
        ];

        // Act
        $response = $this->getJson('/api/v1/hotels/filter?' . http_build_query($filterParams) . '&offset=1&limit=3');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

}
