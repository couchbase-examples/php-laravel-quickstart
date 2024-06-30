<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Route",
 *     type="object",
 *     title="Route",
 *     required={"airline", "airlineid", "sourceairport", "destinationairport", "stops", "equipment", "schedule", "distance"},
 *     @OA\Property(
 *         property="airline",
 *         type="string",
 *         description="Airline code"
 *     ),
 *     @OA\Property(
 *         property="airlineid",
 *         type="string",
 *         description="Airline ID"
 *     ),
 *     @OA\Property(
 *         property="sourceairport",
 *         type="string",
 *         description="Source airport code"
 *     ),
 *     @OA\Property(
 *         property="destinationairport",
 *         type="string",
 *         description="Destination airport code"
 *     ),
 *     @OA\Property(
 *         property="stops",
 *         type="integer",
 *         description="Number of stops"
 *     ),
 *     @OA\Property(
 *         property="equipment",
 *         type="string",
 *         description="Equipment used"
 *     ),
 *     @OA\Property(
 *         property="schedule",
 *         type="array",
 *         description="Flight schedule",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(
 *                 property="day",
 *                 type="integer",
 *                 description="Day of the flight"
 *             ),
 *             @OA\Property(
 *                 property="utc",
 *                 type="string",
 *                 description="UTC time of the flight"
 *             ),
 *             @OA\Property(
 *                 property="flight",
 *                 type="string",
 *                 description="Flight number"
 *             )
 *         )
 *     ),
 *     @OA\Property(
 *         property="distance",
 *         type="number",
 *         format="float",
 *         description="Distance of the route"
 *     )
 * )
 */
class Route extends Model
{
    protected $bucket;

    protected $fillable = [
        'airline',
        'airlineid',
        'sourceairport',
        'destinationairport',
        'stops',
        'equipment',
        'schedule',
        'distance'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->bucket = app('couchbase.bucket');
    }

    public static function getAllRoutes($offset = 0, $limit = 10)
    {
        $instance = new static;
        $query = "SELECT * FROM `travel-sample`.`inventory`.`route` LIMIT $limit OFFSET $offset";
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
            \Log::error('Error fetching routes: ' . $e->getMessage());
            return collect([]);
        }
    }

    public static function findRoute($id)
    {
        $instance = new static;
        try {
            $document = $instance->bucket->scope('inventory')->collection('route')->get($id);
            $data = $document->content();
            return new static($data); // Return a Route instance
        } catch (\Exception $e) {
            \Log::error('Error finding route: ' . $e->getMessage());
            return null;
        }
    }

    public function saveRoute($id)
    {
        $data = $this->attributesToArray();
        unset($data['id']); // Ensure the id is not included in the document content
        try {
            $this->bucket->scope('inventory')->collection('route')->upsert($id, $data);
        } catch (\Exception $e) {
            \Log::error('Error saving route', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public static function destroyRoute($id)
    {
        $instance = new static;
        try {
            $instance->bucket->scope('inventory')->collection('route')->remove($id);
        } catch (\Exception $e) {
            \Log::error('Error destroying route: ' . $e->getMessage());
            throw $e;
        }
    }
}
