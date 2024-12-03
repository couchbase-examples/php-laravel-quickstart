<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Couchbase\ClusterOptions;
use Couchbase\Cluster;
use Couchbase\Management\SearchIndex;
use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\UnambiguousTimeoutException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;

class CouchbaseServiceProvider extends ServiceProvider
{
    /**
     * Commands that don't require database access
     */
    private $nonDatabaseCommands = [
        'key:generate',
        'config:cache',
        'config:clear',
        'view:cache',
        'view:clear',
        'route:cache',
        'route:clear',
        'package:discover'
    ];

    private function shouldSkipDatabaseOperations(): bool
    {
        if (!$this->app->runningInConsole()) {
            return false;
        }

        $command = $_SERVER['argv'][1] ?? null;
        return $command && in_array($command, $this->nonDatabaseCommands);
    }

    private function createClusterConnection($config, $retries = 3)
    {
        $lastException = null;
        $attempt = 0;

        while ($attempt < $retries) {
            try {
                $options = new ClusterOptions();
                $options->credentials($config['username'], $config['password']);
                $options->applyProfile('wan_development');

                $cluster = new Cluster($config['host'], $options);
                
                // Test the connection by trying to get the bucket
                $cluster->bucket($config['bucket']);
                
                return $cluster;
            } catch (UnambiguousTimeoutException $e) {
                $lastException = $e;
                \Log::warning(sprintf("Connection attempt %d failed with timeout, retrying...", $attempt + 1), [
                    'error' => $e->getMessage(),
                    'host' => $config['host']
                ]);
                $attempt++;
                sleep(1); // Wait 1 second before retrying
            } catch (\Exception $e) {
                \Log::error('Failed to connect to Couchbase cluster', [
                    'error' => $e->getMessage(),
                    'host' => $config['host']
                ]);
                throw $e;
            }
        }

        if ($lastException) {
            \Log::error('All connection attempts failed', [
                'error' => $lastException->getMessage(),
                'host' => $config['host']
            ]);
            throw $lastException;
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->shouldSkipDatabaseOperations()) {
            return;
        }

        // Bind cluster as a singleton but don't create it yet
        $this->app->singleton('couchbase.cluster', function ($app) {
            $config = $app['config']['couchbase'];
            return $this->createClusterConnection($config);
        });

        // Bind bucket as a singleton but don't create it yet
        $this->app->singleton('couchbase.bucket', function ($app) {
            $cluster = $app->make('couchbase.cluster');
            $config = $app['config']['couchbase'];
            return $cluster->bucket($config['bucket']);
        });

        // Bind collections as singletons but don't create them yet
        $collections = ['airline', 'airport', 'route', 'hotel'];
        foreach ($collections as $collection) {
            $this->app->bind("couchbase.{$collection}Collection", function ($app) use ($collection) {
                $bucket = $app->make('couchbase.bucket');
                return $bucket->scope('inventory')->collection($collection);
            });
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->shouldSkipDatabaseOperations()) {
            return;
        }

        if (!$this->app->environment('testing')) {
            $this->createSearchIndex();
        }
    }

    /**
     * Create the search index if needed
     */
    private function createSearchIndex()
    {
        try {
            $indexFilePath = 'hotel_search_index.json';

            if (!Storage::exists($indexFilePath)) {
                \Log::warning("Index file not found at storage/app/{$indexFilePath}");
                return;
            }

            $indexContent = Storage::get($indexFilePath);
            $indexData = json_decode($indexContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('Failed to parse index JSON file', [
                    'error' => json_last_error_msg()
                ]);
                return;
            }

            $cluster = $this->app->make('couchbase.cluster');
            $searchIndexManager = $cluster->searchIndexes();

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

            $searchIndexManager->upsertIndex($index);

            \Log::info("Hotel Search index created or updated successfully.");
        } catch (CouchbaseException $e) {
            if ($e->getCode() === 18) {
                \Log::warning("Search index already exists.");
            } else {
                \Log::error("Couchbase Exception Occurred", [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Exception Occurred", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
