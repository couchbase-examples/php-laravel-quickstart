<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Couchbase\Bucket;
use Couchbase\QueryOptions;

/**
 * @OA\Schema(
 *     schema="Airport",
 *     type="object",
 *     title="Airport",
 *     required={"id", "name"},
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         description="ID of the airport"
 *     ),
 *     @OA\Property(
 *         property="name",
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
 *         property="iata",
 *         type="string",
 *         description="IATA code of the airport"
 *     ),
 *     @OA\Property(
 *         property="icao",
 *         type="string",
 *         description="ICAO code of the airport"
 *     )
 * )
 */
class Airport extends Model
{
    protected $bucket;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->bucket = app('couchbase.bucket');
    }

    public static function getAll($offset = 0, $limit = 10)
    {
        $instance = new static;
        $query = "SELECT * FROM `travel-sample`.`inventory`.`airport` LIMIT $limit OFFSET $offset";
        try {
            $result = $instance->bucket->scope('inventory')->query($query);
            return collect($result->rows());
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
            return $document->content();
        } catch (\Exception $e) {
            \Log::error('Error finding airport: ' . $e->getMessage());
            return null;
        }
    }

    public function saveAirport()
    {
        $data = $this->attributesToArray();
        $id = $data['id'];
        unset($data['id']);
        try {
            $this->bucket->scope('inventory')->collection('airport')->upsert($id, $data);
        } catch (\Exception $e) {
            \Log::error('Error saving airport: ' . $e->getMessage());
        }
    }

    public static function destroyAirport($id)
    {
        $instance = new static;
        try {
            $instance->bucket->scope('inventory')->collection('airport')->remove($id);
        } catch (\Exception $e) {
            \Log::error('Error destroying airport: ' . $e->getMessage());
        }
    }
}
