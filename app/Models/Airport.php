<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Airport",
 *     type="object",
 *     title="Airport",
 *     required={"airportname", "city", "country", "icao", "tz", "geo"},
 *     @OA\Property(
 *         property="airportname",
 *         type="string",
 *         description="Name of the airport"
 *     ),
 *     @OA\Property(
 *         property="city",
 *         type="string",
 *         description="City where the airport is located"
 *     ),
 *     @OA\Property(
 *         property="country",
 *         type="string",
 *         description="Country where the airport is located"
 *     ),
 *     @OA\Property(
 *         property="faa",
 *         type="string",
 *         description="FAA code of the airport"
 *     ),
 *     @OA\Property(
 *         property="icao",
 *         type="string",
 *         description="ICAO code of the airport"
 *     ),
 *     @OA\Property(
 *         property="tz",
 *         type="string",
 *         description="Time zone of the airport"
 *     ),
 *     @OA\Property(
 *         property="geo",
 *         type="object",
 *         description="Geographical coordinates of the airport",
 *         @OA\Property(
 *             property="lat",
 *             type="number",
 *             format="float",
 *             description="Latitude"
 *         ),
 *         @OA\Property(
 *             property="lon",
 *             type="number",
 *             format="float",
 *             description="Longitude"
 *         ),
 *         @OA\Property(
 *             property="alt",
 *             type="number",
 *             format="float",
 *             description="Altitude"
 *         )
 *     )
 * )
 */
class Airport extends Model
{
    protected $bucket;

    protected $fillable = [
        'airportname',
        'city',
        'country',
        'faa',
        'icao',
        'tz',
        'geo',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->bucket = app('couchbase.bucket');
    }

    public static function getAllAirports($offset = 0, $limit = 10)
    {
        $instance = new static;
        $query = "SELECT * FROM `travel-sample`.`inventory`.`airport` LIMIT $limit OFFSET $offset";
        try {
            $result = $instance->bucket->scope('inventory')->query($query);
            $rows = $result->rows();
            $rows = array_map(function ($row) {
                unset($row['id']);
                unset($row['type']);
                return $row;
            }, $rows);
            return collect($rows);
        } catch (\Exception $e) {
            \Log::error('Error fetching airports: ' . $e->getMessage());
            return collect([]);
        }
    }

    public static function findAirport($id)
    {
        $instance = new static;
        try {
            $document = $instance->bucket->scope('inventory')->collection('airport')->get($id);
            $data = $document->content();
            return new static($data); // Return an Airport instance
        } catch (\Exception $e) {
            \Log::error('Error finding airport: ' . $e->getMessage());
            return null;
        }
    }

    public function saveAirport($id)
    {
        $data = $this->attributesToArray();
        unset($data['id']); // Ensure the id is not included in the document content
        try {
            $this->bucket->scope('inventory')->collection('airport')->upsert($id, $data);
        } catch (\Exception $e) {
            \Log::error('Error saving airport', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public static function destroyAirport($id)
    {
        $instance = new static;
        try {
            $instance->bucket->scope('inventory')->collection('airport')->remove($id);
        } catch (\Exception $e) {
            \Log::error('Error destroying airport: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function getDirectConnections($sourceAirportCode, $offset = 0, $limit = 10)
    {
        $instance = new static;
        $query = "
        SELECT DISTINCT route.destinationairport
        FROM `travel-sample`.`inventory`.`route` AS route
        JOIN `travel-sample`.`inventory`.`airport` AS airport ON route.sourceairport = airport.faa
        WHERE airport.faa = '$sourceAirportCode' AND route.stops = 0
        LIMIT $limit OFFSET $offset";

        try {
            $result = $instance->bucket->scope('inventory')->query($query);
            $rows = $result->rows();
            return collect($rows);
        } catch (\Exception $e) {
            \Log::error('Error fetching direct connections: ' . $e->getMessage());
            return collect([]);
        }
    }
}
