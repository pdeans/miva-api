## Miva JSON Api PHP SDK

PHP SDK library for interacting with the Miva JSON API.

### Installation

Install via [Composer](https://getcomposer.org/).

```
$ composer require pdeans/miva-api
```

### Configuring the Api Manager

Utilizing the SDK to interact with the Api is accomplished via the `Manager` class. The `Manager` class accepts an array containing Api and HTTP client (cURL) configuration options in key/value format.

#### Manager Configuration Options

Key | Required | Type | Description
----|:--------:|:----:|------------
url | **Yes** | string | The Api endpoint URL.
store_code | **Yes** | string | The Miva store code.
access_token | **Yes** | string | The Api access token.
private_key | **Yes** | string | The Api private key. **Hint:** If omitting signature validation, pass in an `''` empty string literal value.
hmac | No | string | HMAC signature type. Defaults to sha256. Valid types are one of: `sha256`, `sha1`, `''` (Enter a blank string literal if omitting signature validation).
timestamp | No | boolean | Enable/disable Api request timestamp validation. Defaults to true (Enabled).
http_headers | No | array | HTTP request headers. Note that the library will automatically send the `Content-Type: application/json` and `X-Miva-API-Authorization` headers with each Api request. For this reason, these headers should not be included in this list.
http_client | No | array | Associative array of [curl options](http://php.net/curl_setopt).

Example:

```php
use pdeans\Miva\Api\Manager;

$api = new Manager([
    'url'          => 'https://www.domain.com/mm5/json.mvc',
    'store_code'   => 'PS',
    'access_token' => '0f90f77b58ca98836eba3d50f526f523',
    'private_key'  => '12345privatekey',
]);
```

### Authentication

The Miva Api authorization header will be automatically generated based on the configuration settings passed into the `Manager` object and sent along with each Api request. The configuration settings should match the Miva store settings for the given Api token.

### JSON Request Format

The required `Miva_Request_Timestamp` and `Store_Code` properties are automatically generated based on the configuration settings passed into the `Manager` object and added to the JSON body for every Api request. The `Function` property is also automatically added to the JSON body for every request. The JSON data generated for the `Function` property will vary based on the provided request function list.

### Function Builder

The `func` method is used to generate Api request functions. The method accepts the request function name as its only argument.

The `add` method is used to "publish" the function and append it to the request function list.

**Note:** All function builder methods are chainable.

```php
$api->func('OrderCustomFieldList_Load')->add();
```

#### Function Request Parameters

This section showcases how to construct and add function parameters.

##### Common Filter List Parameters

Each common [filter list parameter](https://docs.miva.com/json-api/list-load-query-overview#filter-list-parameters) for the `xxxList_Load_Query` functions has a corresponding helper method to seamlessly set the parameter value. The example below shows each of the methods in action.

```php
$api->func('OrderList_Load_Query')
    ->count(10)   // Limit number of records to return
    ->offset(5)   // Set offset of first record to return
    ->sort('id')  // Column sorting value
    // ->sort('-id')    // Column sorting value -descending
    // ->sortDesc('id') // Column sorting value with explicit descending
    ->filter('Customer_ID', 1850)
    ->add();
```

##### Function Request Filters

Most of the function search/display filters have an associated helper method that acts as a shorthand, or factory for creating the respective filter. The `filter` method must be used for any filter that does not have a linked helper method, as shown in the example above. This method can also be used to generate the filters with helper methods as well. The method accepts two arguments, with the first argument always being the filter name. The second argument takes the filter value, which will vary per filter type.

The available search/display helper methods are covered below. 

**Search**

The `search` method may be used to attach a search filter to a function's filter list. The most basic call to `search` requires three arguments. The first argument is the search field column. The second argument is the search operator, which can be any of the supported Api search operators. Finally, the third argument is the value to evaluate against the search column.

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
    ->search('price', 'GE', 10.00)
    ->add();
    
$api->func('ProductList_Load_Query')
    ->search('Category', 'IN', '13707,13708')
    ->add();
```

The `search` method can be issued multiple times to perform an AND search:

```php
$api->func('ProductList_Load_Query')
    ->search('Category', 'IN', '13707')
    ->search('price', 'GT', 50)
    ->add();
```

Performing OR searches and parenthetical comparisons can be achieved by passing in an array to the `search` method as the first and only argument. The array should be modeled to match the desired search value output, with value nesting as needed:

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

**On Demand Columns**

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

**Show**

The "show" filters can be created using the `show` method. This method takes one argument, the show filter value, which will vary per `xxxList_Load_Query` function. Note that this filter is currently available for the following functions only:

+ CategoryList_Load_Query
+ CategoryProductList_Load_Query
+ ProductList_Load_Query

Example:

```php
$api->func('ProductList_Load_Query')->show('Active')->add();
```

##### Function Request Input Parameters

**Passphrase**

The `passphrase` method is used to set the Passphrase parameter. The method takes a single argument, the decryption passphrase.

```php
$api->func('OrderList_Load_Query')
    ->odc(['payment_data', 'customer', 'items', 'charges'])
    ->passphrase('helloworldhelloworld@123')
    ->add();
```

**Input Parameters**

The `params` method is used to set all other input parameters. A good use case for this method are the request body parameters for the `Xx_Create` / `Xx_Insert` functions. The function accepts a key/value array which maps to the input parameter key/values as its only argument.

Example:

```php
$api->func('Product_Insert')
    ->params(['product_code' => 'new-product', 'name' => 'New Product'])
    ->add();
```

#### API Requests

This section covers configuring and issuing Api requests.

**HTTP Headers**

You may specify which HTTP headers are attached to all Api requests with the `addHeader` and `addHeaders` methods. Please note that the SDK library automatically creates and attaches the `Content-Type: application/json` and `X-Miva-API-Authorization` headers to each Api request.

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

**Sending Requests**

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

You may preview the current request body at any time before sending the request by using the `getRequestBody` method. Helpful for debugging requests.

```php
echo '<pre>', $api->getRequestBody(), '</pre>';
```

#### API Responses

By default, Api responses will return a `pdeans\Miva\Api\Response` instance. The `Response` object hosts a number of helper methods for interacting with the Api responses.

**Checking For Request Errors**

Checking for errors that may have occurred on the Api request can be accomplished with the `getErrors` method. This method will return a `stdClass` object containing the error code and error message thrown. The `isSuccess` method can be used as a flag to determine if a request error occurred:

```php
$response = $api->func('ProductList_Load_Query')->add()->send();
var_dump($response->getErrors());

if (!$response->isSuccess()) {
   echo 'Error: ', $response->getErrors()->message;
}
```

**Response Body**

The raw JSON response body can be retrieved anytime using the `getBody` method:

```php
// Print raw JSON Api response
$response = $api->func('ProductList_Load_Query')->add()->send();
echo '<pre>', $response->getBody(), '</pre>';
```

To receive an iterable form of the Api response, issue the `getResponse` method. This will return a key/value array, with the array keys mapping to the function names supplied to the Api request function list. The keys are mapped in identical order to the Api request function list. Each function key value is a Laravel style `Collection` instance. Refer to the list of [available methods](https://laravel.com/docs/5.7/eloquent-collections#available-methods), as well as the `Collection` class [documentation](https://laravel.com/docs/5.7/collections) to see all of the handy features that collections offer. Each collection item corresponds to each request iteration of the function, in the same order that the request was issued.

The `getFunction` method may be used to explicitly return the response results for a specific function name. This can also be accomplished with the `getResponse` method by passing the function name as the first argument.

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

// Isolate and return responses for specific function
var_dump($response->getFunction('ProductList_Load_Query'));
var_dump($response->getResponse('OrderCustomFieldList_Load'));
```

#### Helpers

**Troubleshooting Api Requests And Responses**

To aid in troubleshooting Api requests and responses, [PSR-7 Request](http://www.php-fig.org/psr/psr-7/) and [Response](http://www.php-fig.org/psr/psr-7/) objects can be obtained using the `getLastRequest` and `getLastResponse` methods respectively after an Api request has been issued:

```php
// Add functions to request function list
$api->func('ProductList_Load_Query')->add();
$api->func('OrderCustomFieldList_Load')->add();
$response = $api->send();

// Output Api request authentication header value
echo $api->getLastRequest()->getHeader('X-Miva-API-Authorization')[0];

// Output the response HTTP status line
$last_response = $api->getLastResponse();
echo $last_response->getStatusCode(), ' ', $last_response->getReasonPhrase();
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

#### Further Reading

Having a general understanding of the [Miva JSON Api](https://docs.miva.com/json-api/) configuration and schema is highly recommended before using the SDK library.