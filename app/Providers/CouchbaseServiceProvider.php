<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Couchbase\ClusterOptions;
use Couchbase\Cluster;

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
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
