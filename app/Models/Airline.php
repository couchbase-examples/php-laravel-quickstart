<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Couchbase\Bucket;
use Couchbase\QueryOptions;

class Airline extends Model
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
        $query = "SELECT * FROM `travel-sample`.`inventory`.`airline` LIMIT $limit OFFSET $offset";
        try {
            $result = $instance->bucket->scope('inventory')->query($query);
            return collect($result->rows());
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
            return $document->content();
        } catch (\Exception $e) {
            \Log::error('Error finding airline: ' . $e->getMessage());
            return null;
        }
    }

    public function saveAirline()
    {
        $data = $this->attributesToArray();
        $id = $data['id'];
        unset($data['id']);
        try {
            $this->bucket->scope('inventory')->collection('airline')->upsert($id, $data);
        } catch (\Exception $e) {
            \Log::error('Error saving airline: ' . $e->getMessage());
        }
    }

    public static function destroyAirline($id)
    {
        $instance = new static;
        try {
            $instance->bucket->scope('inventory')->collection('airline')->remove($id);
        } catch (\Exception $e) {
            \Log::error('Error destroying airline: ' . $e->getMessage());
        }
    }
}
