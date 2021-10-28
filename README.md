# PHP API Class
My custom API class, some parts borrowed from [php-curl-class](https://github.com/php-curl-class/php-curl-class).

## Basic Usage

```php
$service = new API($api_key = 'Basic {YOUR_API_KEY}',$base_url = 'https://api.example.com/v1/', $send_json = true | false);
$service->get('endpoint');
```

## Options

```php
$service->header('key','value'); // set header
$service->opt(CURLOPT_SOMETHING,'value'); // set option
$service->json = true || false; // send data with json_encode() or http_build_query()
```

## Requests

Use `get()`, `post()`, `patch()`, or `delete()` method. All of them can be given a fields array. Each of them starts a new request, meaning the previous one is discarded.

```php
$data = $service->post('endpoint',array(
  "key"=>"value"
));
// if response and http success code (200), returns response.
// if response and http error code, returns null, response is accessed via the error() method.
// if no response, returns http code.

// starts a new request, any methods or variables after this will be based on this request
$data = $service->delete("endpoint/{$id}");

if($service->http_code === 200) {
  echo "Response: ({$service->http_code}): {$service->response()}";
} else {
  echo $service->error();
  // return curl_error() if there is one.
  // returns response, if http error code.
  // otherwise, returns null.
}
```
