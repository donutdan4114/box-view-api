<?php

/**
 * Class Box_View_API
 *
 * Box View API implementation.
 * Allows you to easily work with the Box View API,
 * uploading, deleting, modifying, and viewing documents.
 *
 * @link https://developers.box.com/view Box View Documentation @endlink
 *
 * @author Daniel Pepin <me@danieljpepin.com>
 */
class Box_View_API {

  const API_PROTOCOL = 'https';
  const API_URL = 'view-api.box.com';
  const API_VERSION = '1';
  const API_OBJ = 'documents';

  private $api_key;
  private $api_url;
  private $api_session_url;
  private $api_upload_url;

  /**
   * Initializes the Box_View_API object.
   * Ensures we have access to cURL,
   * that the api_key is set,
   * and sets various URLs needed for interacting with the API.
   *
   * @param string $api_key
   *  API Key for your Box View Application.
   *
   * @throws Box_View_Exception
   */
  public function __construct($api_key) {
    // Ensure we have access to cURL.
    if (!$this->curlInstalled()) {
      throw new Box_View_Exception('cURL extension not found.');
    }
    $this->api_key = $api_key;
    // Set the basic URL used for most API calls.
    $this->api_url = self::API_PROTOCOL . '://' . self::API_URL . '/' . self::API_VERSION . '/' . self::API_OBJ;
    // Uploads use a separate URL.
    $this->api_upload_url = self::API_PROTOCOL . '://upload.' . self::API_URL . '/' . self::API_VERSION . '/' . self::API_OBJ;
    // Sessions use a separate URL.
    $this->api_session_url = self::API_PROTOCOL . '://' . self::API_URL . '/' . self::API_VERSION . '/sessions';
    return $this;
  }

  /**
   * Creates a session for a single document.
   * Sessions can only be created for documents that have a status of done
   *
   * @param Box_View_Document $doc
   * @param array $params
   *  duration -- The duration in minutes until the session expires. (default=60)
   *  expires_at -- The timestamp at which the session should expire.
   *
   * @return object
   *  Returns session response object.
   *
   * @throws Box_View_Exception
   */
  public function view(Box_View_Document &$doc, $params = array()) {
    if (empty($doc->id)) {
      throw new Box_View_Exception('Missing required field: id');
    }
    $params['document_id'] = $doc->id;
    $curl_params[CURLOPT_URL] = $this->api_session_url;
    $curl_params[CURLOPT_POST] = TRUE;
    $curl_params[CURLOPT_POSTFIELDS] = $this->formatData($params);
    $curl_params[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
    $result = $this->httpRequest($curl_params);
    if ($result->headers->code !== 201) {
      throw new Box_View_Exception('Could not create session.', $result->headers->code);
    }
    $doc->session = $result->response;
    $doc->session->url = $this->api_session_url . '/' . $doc->session->id . '/view';
    return $result->response;
  }

  /**
   * Fetches a document in the form specified by extension,
   * which can be one of pdf or zip. If an extension is not specified,
   * the document's original format is returned.
   * For displaying the converted assets contained in the zip version,
   * please see the documentation for viewer.js.
   *
   * @link http://developers.box.com/viewer-js Box Viewer.js Documentation @endlink
   *
   * @param Box_View_Document $doc
   * @param string $ext
   *  Extension of the content to retrieve. Valid extensions are 'zip' and 'pdf'.
   *  If no extension is specified, the original document will be retrieved.
   *
   * @return object
   *
   * @throws Box_View_Exception
   */
  public function getContent(Box_View_Document &$doc, $ext = '') {
    if (empty($doc->id)) {
      throw new Box_View_Exception('Missing required field: id');
    }
    // Add a period to the ext if we are using one.
    $ext = !empty($ext) ? '.' . $ext : '';
    $curl_params[CURLOPT_URL] = $this->api_url . '/' . $doc->id . '/content' . $ext;
    $result = $this->httpRequest($curl_params);
    if ($result->headers->code !== 200) {
      throw new Box_View_Exception('Error getting content.', $result->headers->code);
    }
    $ext = $ext ? : 'original';
    $doc->content->{$ext} = $result->response;
    return $result;
  }

  /**
   * Gets the original document.
   *
   * @param Box_View_Document $doc
   * @return mixed
   *  Returns the document.
   */
  public function getOriginal(Box_View_Document &$doc) {
    return $this->getContent($doc)->response;
  }

  /**
   * Gets the PDF content for a document.
   *
   * @param Box_View_Document $doc
   * @return mixed
   *  Returns the raw PDF code.
   */
  public function getPDF(Box_View_Document &$doc) {
    return $this->getContent($doc, 'pdf')->response;
  }

  /**
   * Gets a zip file of the document.
   *
   * @param Box_View_Document $doc
   * @return mixed
   *  Returns the raw Zip file.
   */
  public function getZip(Box_View_Document &$doc) {
    return $this->getContent($doc, 'zip')->response;
  }

  /**
   * Retrieve a thumbnail image of the first page of a document.
   *
   * @param Box_View_Document $doc
   * @param int $width
   * @param int $height
   *
   * @return mixed
   *  Returns the raw Png file.
   */
  public function getThumbnail(Box_View_Document &$doc, $width = 1024, $height = 768) {
    if (empty($doc->id)) {
      throw new Box_View_Exception('Missing required field: id');
    }
    $curl_params[CURLOPT_URL] = $this->api_url . '/' . $doc->id . '/thumbnail?width='. $width . '&height=' . $height;
    $result = $this->httpRequest($curl_params);
    if ($result->headers->code !== 200) {
      throw new Box_View_Exception('Error getting content.', $result->headers->code);
    }
    return $result->response;
  }

  /**
   * Loads all documents that have been uploaded using this API Key.
   *
   * Valid params are:
   *  limit -- The number of document to return (default=10, max=50).
   *  created_before -- An upper limit on the creation timestamps of documents returned (default=now).
   *  created_after -- A lower limit on the creation timestamps of documents returned.
   */
  public function load($params = array()) {
    $url_params = http_build_query($params);
    $curl_params[CURLOPT_URL] = $this->api_url . '?' . $url_params;

    $result = $this->httpRequest($curl_params);
    if ($result->headers->code !== 200) {
      throw new Box_View_Exception('Error loading documents.', $result->headers->code);
    }

    // Create document objects based on the returned data.
    $docs = array();
    foreach ($result->response->document_collection->entries as $metadata) {
      $doc = new Box_View_Document((array) $metadata);
      $docs[$metadata->id] = $doc;
    }
    return $docs;
  }


  /**
   * Formats data when passing information to the API.
   * For the View API, we use JSON.
   *
   * @param array $data
   * @return string
   *   Returns valid JSON.
   */
  private function formatData($data = array()) {
    return json_encode($data);
  }

  /**
   * Makes an HTTP request to the Box View API and returns the result.
   *
   * @param array $curl_params
   *  Array of CURLOPT params.
   *
   * @throws Box_View_Exception
   * @return array $response
   */
  private function httpRequest($curl_params = array()) {
    $ch = curl_init();

    // Return the result of the curl_exec().
    $curl_params[CURLOPT_RETURNTRANSFER] = TRUE;
    $curl_params[CURLOPT_FOLLOWLOCATION] = TRUE;

    // Need to set the authorization header.
    $curl_params[CURLOPT_HTTPHEADER][] = 'Authorization: Token ' . $this->api_key;

    // Set other CURL_OPT params.
    foreach ($curl_params as $curl_opt => $val) {
      curl_setopt($ch, $curl_opt, $val);
    }

    // Get the response.
    $response = curl_exec($ch);

    // Ensure our request didn't have errors.
    if ($error = curl_error($ch)) {
      throw new Box_View_Exception($error);
    }

    // Close and return the curl response.
    $result = $this->parseResponse($ch, $response);
    curl_close($ch);
    if (is_object($result->response) && property_exists($result->response, 'type') && $result->response->type === 'error') {
      throw new Box_View_Exception('Error: ' . $result->response->message, $result->headers->code);
    }
    return $result;
  }

  /**
   * Parses the response in a more friendly format.
   *
   * @param $ch
   * @param string $response
   * @return object
   */
  private function parseResponse($ch, $response = '') {
    $headers = $this->parseHeaders($ch);
    if ($decoded = json_decode($response)) {
      $body = $decoded;
    }
    else {
      $body = $response;
    }
    return (object) array('headers' => $headers, 'response' => $body);
  }

  /**
   * Parse header information into something usable.
   * Currently, we should only have to look at the status code.
   *
   * @param $ch
   * @return stdClass
   */
  private function parseHeaders($ch) {
    $headers = new stdClass();
    $headers->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return $headers;
  }

  /**
   * Checks whether or not PHP has the cURL extension enabled.
   *
   * @return bool
   *   Returns TRUE if cURL if is enabled.
   */
  private function curlInstalled() {
    return in_array('curl', get_loaded_extensions()) && function_exists('curl_version');
  }

  /**
   * Updates the metadata of a specific document.
   *
   * @param Box_View_Document $doc
   *
   * @return object
   *  Returns the response from the server.
   *
   * @throws Box_View_Exception
   */
  public function update(Box_View_Document &$doc) {
    if (empty($doc->id)) {
      throw new Box_View_Exception('Missing required field: id');
    }
    // Currently, only changing of a document name is supported.
    if (empty($doc->name)) {
      throw new Box_View_Exception('Missing required field: name');
    }

    $curl_params[CURLOPT_CUSTOMREQUEST] = 'PUT';
    $curl_params[CURLOPT_URL] = $this->api_url . '/' . $doc->id;
    $curl_params[CURLOPT_POSTFIELDS] = $this->formatData(array('name' => $doc->name));
    $curl_params[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';

    $result = $this->httpRequest($curl_params);
    if ($result->headers->code !== 200) {
      throw new Box_View_Exception('Could not modify document.', $result->headers->code);

    }
    $this->refreshDocumentMetaData($doc, $result->response);
    return $result->response;
  }

  /**
   * Refreshes the document data for this document.
   *
   * @param Box_View_Document $doc
   * @param array|object $metadata
   *  Array or object of metadata with key values matching document properties.
   */
  private function refreshDocumentMetaData(Box_View_Document &$doc, $metadata) {
    foreach ($metadata as $key => $val) {
      $doc->{$key} = $val;
    }
  }


  /**
   * Deletes multiple documents at once.
   *
   * @param array $docs
   *  Array of Box_View_Document instances.
   * @return array
   *  Returns array of responses received from deleting.
   * @throws Box_View_Exception
   */
  public function deleteMultiple(array &$docs = array()) {
    $responses = array();
    foreach ($docs as &$doc) {
      if ($doc instanceof Box_View_Document) {
        $responses[] = $this->delete($doc);
      }
      else {
        throw new Box_View_Exception('Each document must be of type Box_View_Document.');
      }
    }
    return $responses;
  }

  /**
   * Removes a document completely from the View API servers.
   * This action cannot be undone.
   *
   * @param Box_View_Document $doc
   *
   * @return NULL
   *  Nothing should be returned.
   *
   * @throws Box_View_Exception
   */
  public function delete(Box_View_Document &$doc) {
    if (empty($doc->id)) {
      throw new Box_View_Exception('Missing required field: id');
    }
    $curl_params[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    $curl_params[CURLOPT_URL] = $this->api_url . '/' . $doc->id;
    $result = $this->httpRequest($curl_params);
    if ($result->headers->code !== 204) {
      throw new Box_View_Exception('Error: ' . print_r($result), $result->headers->code);
    }
    // Doc has been deleted set to empty Box_View_Document.
    $doc = new Box_View_Document();
    return $result->response;
  }

  /**
   * Uploads multiple documents at once.
   *
   * @param array $docs
   *  Array of Box_View_Document instances.
   * @return array
   *  Returns array of responses received from uploading.
   * @throws Box_View_Exception
   */
  public function uploadMultiple(array &$docs = array()) {
    $responses = array();
    foreach ($docs as &$doc) {
      if ($doc instanceof Box_View_Document) {
        $responses[] = $this->upload($doc);
      }
      else {
        throw new Box_View_Exception('Each document must be of type Box_View_Document.');
      }
    }
    return $responses;
  }

  /**
   * Upload a new file to the View API for conversion.
   * Files can be uploaded either through a publicly accessible URL or
   * through a multipart POST.
   *
   * @param Box_View_Document $doc
   *
   * @throws Box_View_Exception
   */
  public function upload(Box_View_Document &$doc) {
    // Either url or file is required to upload.
    if (empty($doc->file_url) && empty($doc->file_path)) {
      throw new Box_View_Exception('Missing file information. url or file must be set.');
    }

    // To upload we must POST the url or file.
    $curl_params[CURLOPT_CUSTOMREQUEST] = 'POST';

    $post_fields = array();

    if (!empty($doc->file_url)) {
      // We are doing a URL Upload.
      $post_fields = $this->formatData(array(
        'name' => $doc->name ? : basename($doc->file_url),
        'url' => $doc->file_url,
        'thumbnails' => $doc->thumbnails,
        'non_svg' => $doc->non_svg,
      ));
      $curl_params[CURLOPT_URL] = $this->api_url;
      $curl_params[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
    }

    if (!empty($doc->file_path)) {
      // To upload we must use the special upload URL.
      $curl_params[CURLOPT_URL] = $this->api_upload_url;
      $curl_params[CURLOPT_HTTPHEADER][] = 'Content-Type: multipart/form-data';
      $post_fields['file'] = '@' . $doc->file_path;
      $post_fields['name'] = $doc->name ? : basename($doc->file_path);
      $post_fields['thumbnails'] = $doc->thumbnails;
      $post_fields['non_svg'] = $doc->non_svg;
    }

    $curl_params[CURLOPT_POSTFIELDS] = $post_fields;

    // Upload the file.
    $result = $this->httpRequest($curl_params);
    if ($result->headers->code !== 202) {
      throw new Box_View_Exception('Got error code: ' . $result->headers->code);
    }

    $this->refreshDocumentMetaData($doc, $result->response);

    return $result->response;
  }

  /**
   * Retrieves the metadata for a single document.
   *
   * @param Box_View_Document $doc
   * @param array $fields
   *  Comma-separated list of fields to return. id and type are always returned.
   *
   * @return object
   *  Returns the metadata
   *
   * @throws Box_View_Exception
   */
  public function getMetaData(Box_View_Document &$doc, array $fields = array()) {
    if (empty($doc->id)) {
      throw new Box_View_Exception('Missing required field: id');
    }
    if (empty($fields)) {
      // Fields we want to retrieve for this document.
      $fields = array('status', 'name', 'created_at', 'modified_at');
    }
    $curl_params[CURLOPT_URL] = $this->api_url . '/' . $doc->id . '?fields=' . implode(',', $fields);

    $result = $this->httpRequest($curl_params);
    if ($result->headers->code !== 200) {
      throw new Box_View_Exception('Error getting metadata.', $result->headers->code);
    }
    $this->refreshDocumentMetaData($doc, $result->response);
    return $result->response;
  }

}


/**
 * Class Box_View_Exception
 */
class Box_View_Exception extends Exception {
  // Redefine the exception so message isn't optional.
  public function __construct($message, $code = 0, Exception $previous = NULL) {
    // Make sure everything is assigned properly.
    parent::__construct($message, $code, $previous);
  }
}
