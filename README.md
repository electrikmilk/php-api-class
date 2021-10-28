# PHP API Class
My custom API class, some parts borrowed from [php-curl-class](https://github.com/php-curl-class/php-curl-class).

# Usage

```php
$service = new API('YOUR_API_KEY','https://api.example.com/v1/'); // send all data as JSON
$service = new API('YOUR_API_KEY','https://api.example.com/v1/',false); // don't send json, build http query
$service->get('endpoint');
```

## Options

```php
$service->header('key','value'); // set header
$service->opt('key','value'); // set option
$service->json = true || false; // send data with json_encode() or http_build_query()
```
## Requests

```php
// request functions: get(), post(), patch(), delete()
// all of them can be given a fields array
// each of them starts a new cURL instance, meaning the previous one is discarded
$get_data = $service->post('endpoint',array(
  "key"=>"value"
));
// if no response, returns http code
// if response and http success code (200), returns response
// if response and http error code, returns false, $service->error() = response

if($service->http_code === 200) {
  // do something...
  echo "Response: ({$service->http_code}): {$service->output}";
} else {
  echo $service->error();
  // return curl_error() if there is one
  // returns response, if http error code
  // otherwise, returns null
}
```
