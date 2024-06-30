<?php

namespace App\Models;

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
        $instance = new static;
        $query = "SELECT * FROM `travel-sample`.`inventory`.`airline`";
        if ($country) {
            $query .= " WHERE country = '$country'";
        }
        $query .= " LIMIT $limit OFFSET $offset";
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
            \Log::error('Error fetching airlines: ' . $e->getMessage());
            return collect([]);
        }
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

}
