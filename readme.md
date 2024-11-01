<!--
  Title: Miva JSON Api PHP Library
  Description: PHP library for interacting with the Miva JSON API.
  Keywords: Miva, Merchant, json, api
  Author: pdeans
  -->

## Miva JSON Api PHP Library

PHP library for interacting with the Miva JSON API.

### Table Of Contents

- [Installation](#installation)
- [Configuring the Api Client](#configuring-the-api-client)
    * [Client Configuration Options](#client-configuration-options)
- [Authentication](#authentication)
- [JSON Request Format](#json-request-format)
- [Function Builder](#function-builder)
    * [Function Request Parameters](#function-request-parameters)
        + [Common Filter List Parameters](#common-filter-list-parameters)
        + [Function Request Filters](#function-request-filters)
            - [Search](#search)
            - [On Demand Columns](#on-demand-columns)
            - [Show](#show)
        + [Function Request Input Parameters](#function-request-input-parameters)
            - [Passphrase](#passphrase)
            - [Additional Input Parameters](#additional-input-parameters)
- [API Requests](#api-requests)
    * [HTTP Headers](#http-headers)
    * [Sending Requests](#sending-requests)
- [API Responses](#api-responses)
    - [Checking For Request Errors](#checking-for-request-errors)
    - [Response Body](#response-body)
- [Helpers](#helpers)
    * [Troubleshooting Api Requests And Responses](#troubleshooting-api-requests-and-responses)
    * [Further Reading](#further-reading)

## Installation

Install via [Composer](https://getcomposer.org/).

```
$ composer require pdeans/miva-api
```

## Configuring the Api Client

Utilizing the library to interact with the Api is accomplished via the `Client` class. The `Client` class accepts an array containing Api and HTTP client (cURL) configuration options in key/value format.

### Client Configuration Options

Key | Required | Type | Description
----|:--------:|:----:|------------
url | **Yes** | string | The Api endpoint URL.
store_code | **Yes** | string | The Miva store code.
access_token | **Yes** | string | The Api access token.
private_key | **Yes** | string | The Api private key. **Hint:** If omitting signature validation, pass in an `''` empty string literal value.
hmac | No | string | HMAC signature type. Defaults to sha256. Valid types are one of: `sha256`, `sha1`, `''` (Enter a blank string literal if omitting signature validation).
timestamp | No | boolean | Enable/disable Api request timestamp validation. Defaults to true (Enabled).
http_headers | No | array | HTTP request headers. Note that the library will automatically send the `Content-Type: application/json` and `X-Miva-API-Authorization` headers with each Api request. For this reason, these headers should not be included in this list.
http_client | No | array | Associative array of [curl options](https://php.net/curl_setopt).

Example:

```php
use pdeans\Miva\Api\Client;

$api = new Client([
    'url'          => 'https://www.domain.com/mm5/json.mvc',
    'store_code'   => 'PS',
    'access_token' => '0f90f77b58ca98836eba3d50f526f523',
    'private_key'  => '12345privatekey',
]);

// Example with Basic Authentication header and curl options
$api = new Client([
    'url'          => 'https://www.domain.com/mm5/json.mvc',
    'store_code'   => 'PS',
    'access_token' => '0f90f77b58ca98836eba3d50f526f523',
    'private_key'  => '12345privatekey',
    'http_headers' => [
        'Authorization' => 'Basic ' . base64_encode('username:password'),
        'Connection'    => 'close',
        'Cache-Control' => 'no-cache',
    ],
    'http_client' => [
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
    ],
]);
```

## Authentication

The Miva Api authorization header will be automatically generated based on the configuration settings passed into the `Client` object and sent along with each Api request. The configuration settings should match the Miva store settings for the given Api token.

## JSON Request Format

The required `Miva_Request_Timestamp` and `Store_Code` properties are automatically generated based on the configuration settings passed into the `Client` object and added to the JSON body for every Api request. The `Function` property is also automatically added to the JSON body for every request. The JSON data generated for the `Function` property will vary based on the provided request function list.

## Function Builder

The `func` method is used to generate Api request functions. The method accepts the request function name as its only argument.

The `add` method is used to "publish" the function and append it to the request function list.

**Note:** All function builder methods are chainable.

```php
$api->func('OrderCustomFieldList_Load')->add();
```

### Function Request Parameters

This section showcases how to construct and add function parameters.

#### Common Filter List Parameters

Each common [filter list parameter](https://docs.miva.com/json-api/list-load-query-overview#filter-list-parameters) for the `xxxList_Load_Query` functions has a corresponding helper method to seamlessly set the parameter value. The example below shows each of the methods in action.

```php
$api->func('OrderList_Load_Query')
    ->count(10)   // Limit number of records to return
    ->offset(5)   // Set offset of first record to return
    ->sort('id')  // Column sorting value
    // ->sort('-id')    // Column sorting value -descending
    // ->sortDesc('id') // Column sorting value with explicit descending
    ->filter('Customer_ID', 1850) // Add a filter
    ->add();
```

#### Function Request Filters

Most of the function search/display filters have an associated helper method that acts as a shorthand, or factory for creating the respective filter. The `filter` method must be used for any filter that does not have a linked helper method, as shown in the example above. This method can also be used to create each filter covered below. The method accepts two arguments, with the first argument always being the filter name. The second argument takes the filter value, which will vary per filter type.

The available search/display helper methods are covered below.

##### Search

The `search` method may be used to attach a search filter to a function's filter list. The most basic call to `search` requires three arguments. The first argument is the search field column. The second argument is the search operator, which can be any of the supported Api [search operators](https://docs.miva.com/json-api/list-load-query-overview#filter-list-parameters). Finally, the third argument is the value to evaluate against the search column.

Below is an example to issue a search filter for a specific product code:

```php
$api->func('ProductList_Load_Query')
    ->search('code', 'EQ', 'test-product')
    ->add();
```

For convenience, if you want to verify that a column is equal (`'EQ'`) to a given value, you may pass the value directly as the second argument to the `search` method. The following will achieve the same result as the first example above:

```php
$api->func('ProductList_Load_Query')
    ->search('code', 'test-product')
    ->add();
```

Of course, you may use a variety of other supported operators when writing a `search` filter:

```php
$api->func('ProductList_Load_Query')
    ->search('active', 'FALSE')
    ->add();

$api->func('ProductList_Load_Query')
    ->search('price', 'GE', 2.20)
    ->add();

$api->func('ProductList_Load_Query')
    ->search('Category', 'IN', '13707,13708')
    ->add();
```

The `search` method can be issued multiple times to perform an **AND** search:

```php
$api->func('ProductList_Load_Query')
    ->search('Category', 'IN', '13707')
    ->search('price', 'GT', 19.86)
    ->add();
```

Performing **OR** searches and parenthetical comparisons can be achieved by passing in an array to the `search` method as the first and only argument. The array should be modeled to match the desired search value output, with value nesting as needed:

```php
// OR search
$api->func('OrderList_Load_Query')
    ->search([
        [
            'field'    => 'ship_lname',
            'operator' => 'EQ',
            'value'    => 'Griffin',
        ],
        [
            'field'    => 'ship_lname',
            'operator' => 'EQ',
            'value'    => 'Star',
        ],
    ])
    ->add();

// Parenthetical search
$api->func('OrderList_Load_Query')
    ->search([
        [
            'field'    => 'ship_lname',
            'operator' => 'EQ',
            'value'    => 'Griffin',
        ],
        [
            'field'    => 'search_OR',
            'operator' => 'SUBWHERE',
            'value'    => [
                [
                    'field'    => 'ship_fname',
                    'operator' => 'EQ',
                    'value'    => 'Patrick',
                ],
                [
                    'field'    => 'ship_lname',
                    'operator' => 'EQ',
                    'value'    => 'Star',
                ],
            ],
        ],
    ])
    ->add();
```

##### On Demand Columns

Using the `ondemandcolumns` method, you can specify the explicit columns to return. The method takes one argument, the list of on demand columns to select:

```php
$api->func('ProductList_Load_Query')
    ->ondemandcolumns(['catcount', 'productinventorysettings'])
    ->add();
```

For convenience, the `odc` method can be utilized as an alias to the `ondemandcolumns` method:

```php
$api->func('ProductList_Load_Query')
    ->odc(['catcount', 'productinventorysettings'])
    ->add();
```

##### Show

The "show" filters can be created using the `show` method. This method takes one argument, the show filter value, which will vary per `xxxList_Load_Query` function. Note that this filter is currently available for the following functions only:

+ CategoryList_Load_Query
+ CategoryProductList_Load_Query
+ ProductList_Load_Query

Example:

```php
$api->func('ProductList_Load_Query')->show('Active')->add();
```

#### Function Request Input Parameters

##### Passphrase

The `passphrase` method is used to set the Passphrase parameter. The method takes a single argument, the decryption passphrase.

```php
$api->func('OrderList_Load_Query')
    ->odc(['payment_data', 'customer', 'items', 'charges'])
    ->passphrase('helloworldhelloworld@123')
    ->add();
```

##### Additional Input Parameters

The `params` method is used to set all other input parameters. Some example use cases for this method are the request body parameters for the `Xx_Create` / `Xx_Insert` / `Xx_Update` / `Xx_Delete` functions, `Module` level functions, and essentially all other functions that require specific input parameters to perform actions. The function accepts a key/value array which maps to the input parameter key/values as its only argument.

Examples:

```php
// Example: Create A New Product
$api->func('Product_Insert')
    ->params([
        'product_code' => 'new-product',
        'product_name' => 'New Product',
    ])
    ->add();

// Example: Update Product Inventory
$api->func('Product_Update')
    ->params([
        'product_code'      => 'new-product',
        'product_inventory' => 250,
    ])
    ->add();

// Example: Load An Order Queue
$api->func('Module')
    ->params([
        'Module_Code'     => 'orderworkflow',
        'Module_Function' => 'QueueOrderList_Load_Query',
        'Queue_Code'      => 'new_updated_orders',
    ])
    ->add();

// Example: Acknowledge An Order
$api->func('Module')
    ->params([
        'Module_Code'     => 'orderworkflow',
        'Module_Function' => 'OrderList_Acknowledge',
        'Order_Ids'       => [1000, 10001, 10002],
    ])
    ->add();

// Example: Create A Shipment
$api->func('OrderItemList_CreateShipment')
    ->params([
        'Order_Id' => '200103',
        'line_ids' => [100, 101],
    ])
    ->add();

// Example: Update Shipments
$api->func('OrderShipmentList_Update')
    ->params([
        'Shipment_Updates' => [
            [
                'shpmnt_id'    => 1,
                'mark_shipped' => true,
                'tracknum'     => '1234567890',
                'tracktype'    => 'UPS',
                'cost'         => 5,
            ], [
                'shpmnt_id'    => 2,
                'mark_shipped' => true,
                'tracknum'     => '0987654321',
                'tracktype'    => 'USPS',
            ],
        ],
    ])
    ->add();
```

## API Requests

This section covers configuring and issuing Api requests.

### HTTP Headers

You may specify which HTTP headers are attached to all Api requests with the `addHeader` and `addHeaders` methods. Please note that the library automatically creates and attaches the `Content-Type: application/json` and `X-Miva-API-Authorization` headers to each Api request.

```php
// Add single header at a time
$api->addHeader('Custom-Header-Name', 'custom-header-value');
$api->addHeader('Cache-Control', 'no-cache');

// Add multiple headers in one swoop
$api->addHeaders([
    'Custom-Header-Name'=> 'custom-header-value',
    'Cache-Control'=> 'no-cache',
]);
```

### Sending Requests

The `send` method will issue an Api request, and return the results in the library's `Response` object. If you wish to bypass this object and return the raw JSON response from the Api, pass a `true` value as the first argument for the `send` method.

Example requests:

```php

// First add functions to request function list
$api->func('ProductList_Load_Query')
    ->search('code', 'prod1')
    ->add();

$api->func('CategoryList_Load_Query')
    ->sortDesc('id')
    ->count(5)
    ->filter('Category_Show', 'Active')
    ->add();

// Issue Api request - returns \pdeans\Miva\Api\Response object
$response = $api->send();

// Alternatively - returns raw JSON response
$response = $api->send(true);
```

You may preview the current request body at any time before sending the request by using the `getRequestBody` method. This is helpful for debugging requests.

```php
echo '<pre>', $api->getRequestBody(), '</pre>';
```

## API Responses

By default, Api responses will return a `pdeans\Miva\Api\Response` class instance. The `Response` object includes a number of helper methods for interacting with the Api responses.

### Checking For Request Errors

Checking for errors that may have occurred on the Api request can be accomplished with the `getErrors` method. This method will return a `stdClass` object containing the error code and error message thrown. The `isSuccess` method returns a boolean value which can be used as a flag to determine if a request error occurred:

```php
$response = $api->func('ProductList_Load_Query')->add()->send();
var_dump($response->getErrors());

if (!$response->isSuccess()) {
   echo 'Error: ', $response->getErrors()->message;
}
```

### Response Body

The raw JSON response body can be retrieved anytime using the `getBody` method:

```php
// Print raw JSON Api response
$response = $api->func('ProductList_Load_Query')->add()->send();
echo '<pre>', $response->getBody(), '</pre>';
```

To receive an iterable form of the Api response, issue the `getResponse` method. This will return an array of objects, with the array keys mapping to the function names supplied to the Api request function list. The items are sorted in identical order to the Api request function list. Each item or "function", contains its own array of the results of the function request. These array items correlate to each of the function's iterations that were sent in the request. The items are sorted in the same order that they were issued in the request. Use the `getFunctions` method to retrieve the list of available functions.

The `getFunction` method may be used to explicitly return the response results for a specific function name. This can also be accomplished with the `getResponse` method by passing the function name as the first argument.

The `getData` method returns the response `data` property for a specific function name. By default, the `data` property is returned for the first iteration index for the function name provided. However, an optional second argument can be provided to return the `data` property for a specific iteration index on the given function name.

Examples:

```php
// Add functions to request function list
$api->func('ProductList_Load_Query')->add();
$api->func('OrderCustomFieldList_Load')->add();
$response = $api->send();

// Full response array
$results = $response->getResponse();
var_dump($results);

// Access function key on response array
var_dump($results['OrderCustomFieldList_Load']);

// Results are also iterable (same for result items)
foreach ($results as $result) {
    var_dump($result);
}

// Return list of available functions in the response
var_dump($response->getFunctions());

// Isolate and return responses for specific function
var_dump($response->getFunction('ProductList_Load_Query'));
var_dump($response->getResponse('OrderCustomFieldList_Load'));

// Add functions to request function list
$api->func('ProductList_Load_Query')->add();
$api->func('ProductList_Load_Query')->count(5)->add();
$api->func('ProductList_Load_Query')->count(10)->add();
$response = $api->send();

/**
 * Get the response "data" property for specific function.
 * Defaults to the first iteration index result for the given function.
 */
var_dump($response->getData('ProductList_Load_Query'));
/**
 * Use the optional second parameter to return the "data" property for
 * a specific iteration index. The example below will return the "data"
 * property for the 3rd iteration result on the given function.
 */
var_dump($response->getData('ProductList_Load_Query', 2));
```

## Helpers

This section covers library helper methods.

### Troubleshooting Api Requests And Responses

To aid in troubleshooting Api requests and responses, [PSR-7 Request](https://www.php-fig.org/psr/psr-7/) and [PSR-7 Response](https://www.php-fig.org/psr/psr-7/) objects can be obtained using the `getPreviousRequest` and `getPreviousResponse` methods respectively after an Api request has been issued:

```php
// Add functions to request function list
$api->func('ProductList_Load_Query')->add();
$api->func('OrderCustomFieldList_Load')->add();
$response = $api->send();

// Output Api request authentication header value
echo $api->getPreviousRequest()->getHeader('X-Miva-API-Authorization')[0];

// Output the response HTTP status line
$prevResponse = $api->getPreviousResponse();
echo $prevResponse->getStatusCode(), ' ', $prevResponse->getReasonPhrase();
```

Furthermore, the `getUrl`, `getHeaders`, and `getFunctionList` methods may be used to inspect and troubleshoot requests before they are sent off to the Api:

```php
// Output Api endpoint url
echo $api->getUrl();

// Output request header list
$api->addHeader('Custom-Header-Name', 'custom-header-value');
var_dump($api->getHeaders());

// Output request function list
$api->func('ProductList_Load_Query')->add();
$api->func('OrderCustomFieldList_Load')->add();
var_dump($api->getFunctionList());
```

### Further Reading

Having a general understanding of the [Miva JSON Api](https://docs.miva.com/json-api/) configuration and schema is highly recommended before using the library.
