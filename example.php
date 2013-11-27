<?php

require 'lib/box-view-api.php';
require 'lib/box-view-document.php';

//
// Manipulate documents using the API.
//

$api_key = 'YOUR_API_KEY';
$box = new Box_View_API($api_key);

// Create new document we want to upload.
$doc = new Box_View_Document(array(
  'name' => 'test document',
  'file_url' => 'PATH_TO_FILE',
));

// Upload the new document.
$box->upload($doc);

// Change name and update document.
$doc->name = 'new test document';
$box->update($doc);

// Delete the document.
$box->delete($doc);

// List all documents for this app.
$docs = $box->load();
var_dump($docs);

//
// View Documents using the API.
//

$api_key = 'YOUR_API_KEY';
$box = new Box_View_API($api_key);

$doc = new Box_View_Document();
$doc->id = 'DOCUMENT_ID';
$box->view($doc);

// Open file in iframe.
$html = '<iframe src="' . $doc->session->url . '"></iframe>';
echo $html;