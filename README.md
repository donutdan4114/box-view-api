Box View API
============
**Unofficial PHP SDK for the [Box View API](https://developers.box.com/view).**


*Created by [Daniel Pepin](http://danieljpepin.com) @ 
[CommonPlaces, Inc](http://commonplaces.com)*  
*Sponsored by [LawJacked](http://www.jurify.com)*

- - -

Documentation
-------------
For general API documentaion, please review the [Box View API Documentation](https://developers.box.com/view).

Including the required classes:
```
require 'lib/box-view-api.php';
require 'lib/box-view-document.php';
```

Intializing the API class:
```
$api_key = 'YOUR_API_KEY';
$box = new Box_View_API($api_key);
```

Creating a document to upload:
```
$doc = new Box_View_Document();
$doc->name = 'My Awesome Document';
$doc->file_url = 'http://my-public-url';
```

Uploading a document to the API:
```
$box->upload($doc);
```

After some time, the document will be processed and can be viewed:
```
$box->view($doc);
echo $doc->session->url; // Links to the HTML5 document.
```

Embed the document in an iframe.
```
<iframe src="<?= $doc->session->url ?>"></iframe>
```

Showing a PDF version of the file.
```
$box->getPDF($doc);
header('Content-Type: application/pdf');
print $doc->content->pdf;
```

Getting a Zip folder containing the HTML5 files for the document:
```
$box->getZip($doc);
file_put_contents($doc->name . '.zip', $doc->content->zip);
// Unzip the file.
```

Deleting the document:
```
$box->delete($doc);
```

####Handling Exceptions
API calls will throw an instance of `Box_View_Exception` when an error is encountered.  
You should wrap your API calls with a `try/catch`.
```
try
{
  $box->upload($doc);
}
catch(Exception $e)
{
  log('error', $e->getMessage(), $http_code = $e->getCode());
}
```