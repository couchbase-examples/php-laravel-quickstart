<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $fillable = [
        'name',
        'title',
        'description',
        'country',
        'city',
        'state',
    ];

    protected $bucket;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->bucket = app('couchbase.bucket');
    }

    public static function searchByName($name)
    {
        $instance = new static;
        $query = "SELECT name FROM `travel-sample`.`inventory`.`hotel` WHERE name LIKE '%$name%'";
        $result = $instance->bucket->scope('inventory')->query($query);
        return $result->rows();
    }

    public static function filter($filters, $offset, $limit)
    {
        $instance = new static;
        // 1 = 1 to continue the query 
        $query = "SELECT * FROM `travel-sample`.`inventory`.`hotel` WHERE 1=1";

        foreach ($filters as $field => $value) {
            if ($value) {
                $query .= " AND $field LIKE '%$value%'";
            }
        }

        $query .= " LIMIT $limit OFFSET $offset";
        $result = $instance->bucket->scope('inventory')->query($query);
        return $result->rows();
    }
}
