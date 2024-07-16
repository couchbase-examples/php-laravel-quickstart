<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Couchbase\ClusterOptions;
use Couchbase\Cluster;
use Couchbase\Management\SearchIndex;
use Couchbase\Exception\CouchbaseException;
use Illuminate\Support\Facades\Storage;

class CouchbaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('couchbase.cluster', function ($app) {
            $config = $app['config']['couchbase'];

            $options = new ClusterOptions();
            $options->credentials($config['username'], $config['password']);
            $options->applyProfile('wan_development');

            return new Cluster($config['host'], $options);
        });

        $this->app->singleton('couchbase.bucket', function ($app) {
            $cluster = $app->make('couchbase.cluster');
            $config = $app['config']['couchbase'];
            return $cluster->bucket($config['bucket']);
        });

        $this->app->singleton('couchbase.airlineCollection', function ($app) {
            $bucket = $app->make('couchbase.bucket');
            return $bucket->scope('inventory')->collection('airline');
        });

        $this->app->singleton('couchbase.airportCollection', function ($app) {
            $bucket = $app->make('couchbase.bucket');
            return $bucket->scope('inventory')->collection('airport');
        });

        $this->app->singleton('couchbase.routeCollection', function ($app) {
            $bucket = $app->make('couchbase.bucket');
            return $bucket->scope('inventory')->collection('route');
        });

        $this->app->singleton('couchbase.hotelCollection', function ($app) {
            $bucket = $app->make('couchbase.bucket');
            return $bucket->scope('inventory')->collection('hotel');
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $indexFilePath = 'hotel_search_index.json';

        try {
            // Read the index configuration from the JSON file using Laravel's storage system
            if (!Storage::exists($indexFilePath)) {
                throw new \Exception("Index file not found at storage/app/{$indexFilePath}");
            }
            $indexContent = Storage::get($indexFilePath);
            $indexData = json_decode($indexContent, true);

            // Get the cluster instance
            $cluster = $this->app->make('couchbase.cluster');

            // Create an instance of the SearchIndexManager
            $searchIndexManager = $cluster->searchIndexes();

            // Create a new SearchIndex instance
            $index = new SearchIndex($indexData['name'], $indexData['sourceName']);
            if (isset($indexData['uuid'])) {
                $index->setUuid($indexData['uuid']);
            }
            if (isset($indexData['type'])) {
                $index->setType($indexData['type']);
            }
            if (isset($indexData['params'])) {
                $index->setParams($indexData['params']);
            }
            if (isset($indexData['sourceUUID'])) {
                $index->setSourceUuid($indexData['sourceUUID']);
            }
            if (isset($indexData['sourceType'])) {
                $index->setSourceType($indexData['sourceType']);
            }
            if (isset($indexData['sourceParams'])) {
                $index->setSourceParams($indexData['sourceParams']);
            }
            if (isset($indexData['planParams'])) {
                $index->setPlanParams($indexData['planParams']);
            }

            \Log::info("Upserting index: " . json_encode($indexData));

            // Upsert (create or update) the index
            $searchIndexManager->upsertIndex($index);

            echo "Hotel Search index created or updated successfully.\n";
        } catch (CouchbaseException $e) {
            \Log::error("Couchbase Exception Occurred: " . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error("Exception Ocurred: " . $e->getMessage());
        }
    }
}
