Box View PHP SDK
================
**Unofficial PHP SDK for the [Box View API](https://developers.box.com/view).**


*Created by [Daniel Pepin](http://danieljpepin.com) @ 
[CommonPlaces, Inc](http://commonplaces.com)*  
*Sponsored by [LawJacked](http://www.jurify.com)*

- - -

Documentation
-------------
For general API documentaion, please review the [Box View API Documentation](https://developers.box.com/view).


To get started,
Include the required classes:
```php
require 'lib/box-view-api.php';
require 'lib/box-view-document.php';
```

Intializing the API class:
```php
$api_key = 'YOUR_API_KEY';
$box = new Box_View_API($api_key);
```

Creating a document to upload:
```php
$doc = new Box_View_Document();
$doc->name = 'My Awesome Document';
$doc->file_url = 'http://my-public-url';
```

Uploading a document to the API:
```php
$box->upload($doc);
```

Uploading an array of documents to the API:
```php
// Create array of Box_View_Document objects.
$docs[] = new Box_View_Document(array('file_url' => 'http://foo.bar/why-cats-purrrr.pdf'));
$docs[] = new Box_View_Document(array('file_url' => 'http://foo.bar/10-ways-to-love-your-cat.docx'));
$docs[] = new Box_View_Document(array('file_url' => 'http://foo.bar/funny-cat-links.xlsx'));

// Wrap API calls in try/catch.
try
{
  $box->uploadMultiple($docs);
}
catch(Exception $e)
{
  log('error', $e->getMessage());
}
```

After some time, the document will be processed and can be viewed:
```php
$box->view($doc);
echo $doc->session->url; // Links to the HTML5 document.
```

Embed the document in an iframe.
```php
<iframe src="<?= $doc->session->url ?>"></iframe>
```

Showing a PDF version of the file.
```php
$box->getPDF($doc);
header('Content-Type: application/pdf');
print $doc->content->pdf;
```

Getting a Zip folder containing the HTML5 files for the document:
```php
$box->getZip($doc);
file_put_contents($doc->name . '.zip', $doc->content->zip);
// Unzip the file.
```

Deleting the document:
```php
$box->delete($doc);
```

####Handling Exceptions
API calls will throw an instance of `Box_View_Exception` when an error is encountered.  
You should wrap your API calls with a `try/catch`.
```php
try
{
  $box->upload($doc);
}
catch(Exception $e)
{
  log('error', $e->getMessage(), $http_code = $e->getCode());
}
```