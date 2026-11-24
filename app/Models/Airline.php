<?php

namespace App\Models;

use App\Models\Concerns\QueriesInventory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Airline",
 *     type="object",
 *     title="Airline",
 *     required={"callsign", "country", "iata", "icao", "name"},
 *     @OA\Property(
 *         property="callsign",
 *         type="string",
 *         description="Callsign of the airline"
 *     ),
 *     @OA\Property(
 *         property="country",
 *         type="string",
 *         description="Country of the airline"
 *     ),
 *     @OA\Property(
 *         property="iata",
 *         type="string",
 *         description="IATA code of the airline"
 *     ),
 *     @OA\Property(
 *         property="icao",
 *         type="string",
 *         description="ICAO code of the airline"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Name of the airline"
 *     )
 * )
 */
class Airline extends Model
{
    use QueriesInventory;

    protected $bucket;

    protected $fillable = [
        'callsign',
        'country',
        'iata',
        'icao',
        'name'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->bucket = app('couchbase.bucket');
    }

    public static function getAllAirlinesByCountry($country, $offset = 0, $limit = 10)
    {
        $parameters = static::pagingParameters($offset, $limit);
        $query = "SELECT * FROM `travel-sample`.`inventory`.`airline`";
        if ($country) {
            $query .= " WHERE country = \$country";
            $parameters['country'] = $country;
        }
        $query .= " LIMIT \$limit OFFSET \$offset";

        return static::queryInventory($query, $parameters)->map(function ($row) {
            unset($row['id']);
            unset($row['type']);
            return $row;
        });
    }


    public static function findAirline($id)
    {
        $instance = new static;
        try {
            $document = $instance->bucket->scope('inventory')->collection('airline')->get($id);
            $data = $document->content();
            return new static($data); // Return an Airline instance
        } catch (\Exception $e) {
            \Log::error('Error finding airline: ' . $e->getMessage());
            return null;
        }
    }

    public function saveAirline($id)
    {
        $data = $this->attributesToArray();
        unset($data['id']); // Ensure the id is not included in the document content
        try {
            $this->bucket->scope('inventory')->collection('airline')->upsert($id, $data);
        } catch (\Exception $e) {
            \Log::error('Error saving airline', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    public static function destroyAirline($id)
    {
        $instance = new static;
        try {
            $instance->bucket->scope('inventory')->collection('airline')->remove($id);
        } catch (\Exception $e) {
            \Log::error('Error destroying airline: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function getAirlinesToAirport($destinationAirportCode, $offset = 0, $limit = 10)
    {
        $query = "
        SELECT air.callsign, air.country, air.iata, air.icao, META(air).id AS id, air.name, air.type
        FROM (
            SELECT DISTINCT route.airlineid AS airlineId
            FROM `travel-sample`.`inventory`.`route` AS route
            WHERE route.destinationairport = \$destinationAirportCode
        ) AS subquery
        JOIN `travel-sample`.`inventory`.`airline` AS air ON META(air).id = subquery.airlineId
        LIMIT \$limit OFFSET \$offset";

        return static::queryInventory(
            $query,
            static::pagingParameters($offset, $limit) + ['destinationAirportCode' => $destinationAirportCode]
        );
    }

}
