# guesty-client

WIP

Minimalist PHP wrapper to manage connection with guesty.com API 

see the full API doc here: https://guestyorg.github.io/guesty-api/

More support doc: https://support.guesty.com/hc/en-us/articles/214071469-API-documentation

## usage
```php
try {

    // init client with your API key and API secret
    $client = new Guesty_Client('your API KEY','your API secret');

    // get all listings in an array
    $all_listings = $client->getlistings();

    // get all data of one listing in an array
    $one_listing = $client->getlisting('listing ID');

    // get the availability and pricing calendar of a listing for the next 7 days
    $calendar_data = $client->getcalendar('listing ID',date('Y-m-d',time()),date('Y-m-d',time() + 60 * 60 * 24 * 7));

} catch (Exception $e) {
    // handle API error
    error_log($e->getMessage());
}
```
