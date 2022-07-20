### Quick Installation

To install the Picsmize library, Here is how you can install using command line,

```bash
$ composer require picsmize/picsmize-php
```

### Configuration

Load the Picsmize library into your page

```php
require_once 'Picsmize/Picsmize.php';
```

If you don't have your API Key just yet, you can [Sign Up](https://picsmize.com/register) for a free account.
And That's it! Start optimizing..!

### Quick Example

This Picsmize PHP library allows all the operations available with the [Picsmize API](https://picsmize.com/document/introduction). The following example uses image `fetch`, `compress`, `resize` and `filter` with different mode and get the output file directly with `toJSON()` method:

```php
/**
* Initialize `Picsmize` Class
*/

$picsmize = new Picsmize('your-api-key');

/**
* Use of fetch() method
*/

$picsmize->fetch('https://www.website.com/image.jpg')

/**
* Use of compress() method with medium mode
*/

->compress(Picsmize::COMPRESS_LOW)

/**
* Use of resize() method with auto mode
* and width set to 400
*/

->resize(Picsmize::RESIZE_AUTO, array(
'width' => 400
))

/**
* Use of filter() blur method with gaussian mode
* and value set to 10
*/

->filter(Picsmize::FILTER_BLUR, array(
'mode' => 'gaussian',
'value' => 10
))

/**
* Call toJSON() on the final step and return the JSON response
*/

->toJSON(function ($response) {
/**
* You'll find the full JSON metadata array within the `$response` variable.
* Remember to always check if the `status` property is set to `true`.
*/

if ($response['status'] == false) {
throw new Exception($response['message']);
}

print_r($response);
});
```