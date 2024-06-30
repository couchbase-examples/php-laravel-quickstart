<?php

namespace App;

use Illuminate\Support\ServiceProvider;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Quickstart in Couchbase with PHP and Laravel",
 *     description="
A quickstart API using PHP and Laravel with Couchbase and travel-sample data.

We have a visual representation of the API documentation using Swagger which allows you to interact with the API's endpoints directly through the browser. It provides a clear view of the API including endpoints, HTTP methods, request parameters, and response objects.

### Trying Out the API

You can try out an API by clicking on the 'Try it out' button next to the endpoints.

- **Parameters:** If an endpoint requires parameters, Swagger UI provides input boxes for you to fill in. This could include path parameters, query strings, headers, or the body of a POST/PUT request.
- **Execution:** Once you've inputted all the necessary parameters, you can click the 'Execute' button to make a live API call. Swagger UI will send the request to the API and display the response directly in the documentation. This includes the response code, response headers, and response body.

### Models

Swagger documents the structure of request and response bodies using models. These models define the expected data structure using JSON schema and are extremely helpful in understanding what data to send and expect.

For details on the API, please check the tutorial on the Couchbase Developer Portal: [Couchbase Quickstart PHP Laravel](https://developer.couchbase.com/tutorial-quickstart-php-laravel).
",
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local server"
 * )
 */
class SwaggerConfig extends ServiceProvider
{
    public function boot()
    {
        // You can put any logic that needs to be run during the bootstrapping process of your application here.
    }

    public function register()
    {
        // This method can be used to bind any services into the container if needed.
    }
}
