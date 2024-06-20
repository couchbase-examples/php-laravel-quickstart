<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Couchbase\Bucket;
use Couchbase\QueryOptions;

class Route extends Model
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
        $query = "SELECT * FROM `travel-sample`.`inventory`.`route` LIMIT $limit OFFSET $offset";
        try {
            $result = $instance->bucket->scope('inventory')->query($query);
            return collect($result->rows());
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
            return $document->content();
        } catch (\Exception $e) {
            \Log::error('Error finding route: ' . $e->getMessage());
            return null;
        }
    }

    public function saveRoute()
    {
        $data = $this->attributesToArray();
        $id = $data['id'];
        unset($data['id']);
        try {
            $this->bucket->scope('inventory')->collection('route')->upsert($id, $data);
        } catch (\Exception $e) {
            \Log::error('Error saving route: ' . $e->getMessage());
        }
    }

    public static function destroyRoute($id)
    {
        $instance = new static;
        try {
            $instance->bucket->scope('inventory')->collection('route')->remove($id);
        } catch (\Exception $e) {
            \Log::error('Error destroying route: ' . $e->getMessage());
        }
    }
}
