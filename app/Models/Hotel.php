<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Couchbase\Cluster;
use Couchbase\MatchSearchQuery;
use Couchbase\ConjunctionSearchQuery;
use Couchbase\SearchOptions;
use Couchbase\TermSearchQuery;

/**
 * @OA\Schema(
 *     schema="Hotel",
 *     type="object",
 *     title="Hotel",
 *     required={"title", "name", "country", "city"},
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         description="Title of the hotel"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Name of the hotel"
 *     ),
 *     @OA\Property(
 *         property="address",
 *         type="string",
 *         description="Address of the hotel"
 *     ),
 *     @OA\Property(
 *         property="directions",
 *         type="string",
 *         nullable=true,
 *         description="Directions to the hotel"
 *     ),
 *     @OA\Property(
 *         property="phone",
 *         type="string",
 *         description="Phone number of the hotel"
 *     ),
 *     @OA\Property(
 *         property="tollfree",
 *         type="string",
 *         nullable=true,
 *         description="Toll-free number of the hotel"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="Email address of the hotel"
 *     ),
 *     @OA\Property(
 *         property="fax",
 *         type="string",
 *         nullable=true,
 *         description="Fax number of the hotel"
 *     ),
 *     @OA\Property(
 *         property="url",
 *         type="string",
 *         format="url",
 *         description="URL of the hotel's website"
 *     ),
 *     @OA\Property(
 *         property="checkin",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Check-in time"
 *     ),
 *     @OA\Property(
 *         property="checkout",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Check-out time"
 *     ),
 *     @OA\Property(
 *         property="price",
 *         type="string",
 *         description="Price of the stay"
 *     ),
 *     @OA\Property(
 *         property="geo",
 *         type="object",
 *         @OA\Property(
 *             property="lat",
 *             type="number",
 *             format="float",
 *             description="Latitude of the hotel location"
 *         ),
 *         @OA\Property(
 *             property="lon",
 *             type="number",
 *             format="float",
 *             description="Longitude of the hotel location"
 *         ),
 *         @OA\Property(
 *             property="accuracy",
 *             type="string",
 *             description="Accuracy of the geolocation"
 *         )
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         description="Type of the establishment (e.g., hotel)"
 *     ),
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="ID of the hotel"
 *     ),
 *     @OA\Property(
 *         property="country",
 *         type="string",
 *         description="Country where the hotel is located"
 *     ),
 *     @OA\Property(
 *         property="city",
 *         type="string",
 *         description="City where the hotel is located"
 *     ),
 *     @OA\Property(
 *         property="state",
 *         type="string",
 *         nullable=true,
 *         description="State where the hotel is located"
 *     ),
 *     @OA\Property(
 *         property="reviews",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(
 *                 property="content",
 *                 type="string",
 *                 description="Content of the review"
 *             ),
 *             @OA\Property(
 *                 property="ratings",
 *                 type="object",
 *                 @OA\Property(property="Service", type="integer", description="Service rating"),
 *                 @OA\Property(property="Cleanliness", type="integer", description="Cleanliness rating"),
 *                 @OA\Property(property="Overall", type="integer", description="Overall rating"),
 *                 @OA\Property(property="Value", type="integer", description="Value rating"),
 *                 @OA\Property(property="Sleep Quality", type="integer", description="Sleep Quality rating"),
 *                 @OA\Property(property="Rooms", type="integer", description="Rooms rating"),
 *                 @OA\Property(property="Location", type="integer", description="Location rating")
 *             ),
 *             @OA\Property(
 *                 property="author",
 *                 type="string",
 *                 description="Author of the review"
 *             ),
 *             @OA\Property(
 *                 property="date",
 *                 type="string",
 *                 format="date-time",
 *                 description="Date of the review"
 *             )
 *         )
 *     ),
 *     @OA\Property(
 *         property="public_likes",
 *         type="array",
 *         @OA\Items(type="string"),
 *         description="List of public likes"
 *     ),
 *     @OA\Property(
 *         property="vacancy",
 *         type="boolean",
 *         description="Vacancy status"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Description of the hotel"
 *     ),
 *     @OA\Property(
 *         property="alias",
 *         type="string",
 *         nullable=true,
 *         description="Alias of the hotel"
 *     ),
 *     @OA\Property(
 *         property="pets_ok",
 *         type="boolean",
 *         description="Whether pets are allowed"
 *     ),
 *     @OA\Property(
 *         property="free_breakfast",
 *         type="boolean",
 *         description="Whether free breakfast is offered"
 *     ),
 *     @OA\Property(
 *         property="free_internet",
 *         type="boolean",
 *         description="Whether free internet is offered"
 *     ),
 *     @OA\Property(
 *         property="free_parking",
 *         type="boolean",
 *         description="Whether free parking is offered"
 *     )
 * )
 */
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
    protected $collection;

    const HOTEL_SEARCH_INDEX_NAME = 'travel-sample.inventory.hotel_search';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->bucket = app('couchbase.bucket');
        $this->cluster = app('couchbase.cluster');
        $this->collection = $this->bucket->scope('inventory')->collection('hotel');
    }

    public static function searchByName($name)
    {
        try {
            $instance = new static;
            $query = new MatchSearchQuery($name);
            $query->field('name');
            $options = new SearchOptions();
            $options->fields(['name', 'title', 'description', 'country', 'city', 'state']);
            $result = $instance->cluster->searchQuery(self::HOTEL_SEARCH_INDEX_NAME, $query, $options);

            return array_map(function ($row) {
                return $row['fields'];
            }, $result->rows());

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            throw $e;
        }
    }

    public static function filter($filters, $offset, $limit)
    {
        $instance = new static;
        $conjuncts = new ConjunctionSearchQuery([]);

        foreach ($filters as $field => $value) {
            if ($value) {
                // Use TermSearchQuery for exact match
                $query = new TermSearchQuery($value);

                // Use MatchSearchQuery for partial match
                // $query = new MatchSearchQuery($value);
                
                $query->field($field);
                $conjuncts->and($query);
            }
        }

        $options = new SearchOptions();
        $options->skip($offset)->limit($limit)->fields(['name', 'title', 'description', 'country', 'city', 'state']);
        $result = $instance->cluster->searchQuery(self::HOTEL_SEARCH_INDEX_NAME, $conjuncts, $options);

        return array_map(function ($row) {
            return $row['fields'];
        }, $result->rows());
    }
}
