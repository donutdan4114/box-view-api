<?php

/**
 * Class Box_View_Document
 *
 * Simple object for handling Box View Documents.
 * This class is only useful when combined with the Box_View_API class.
 *
 * @see Box_View_API
 *
 * @link https://developers.box.com/view Box View Documentation @endlink
 *
 * @author Daniel Pepin <me@danieljpepin.com>
 */
class Box_View_Document {

  public $name; // The name of this document.
  public $thumbnails = ''; // Comma-separated list of thumbnail dimensions of the format {width}x{height} e.g. 128×128,256×256
  public $non_svg = false; // Whether to also create the non-svg version of the document

  public $file_url; // URL to the document you want to upload.
  public $file_path; // Internal path to the document you want to upload.

  public $type; // Currently, should only be 'document'.
  public $id; // A unique string identifying this document.
  public $status; // An enum indicating the conversion status of this document. Can be queued, processing, done, or error.
  public $created_at; // The time the document was uploaded.
  public $modified_at; // The time the document was last modified by an end-user.

  /**
   * Sessions are used whenever an end-user needs to view, download,
   * or otherwise interact with a document. This is done so that end-users
   * can interact with Box View documents without the API client needing to
   * expose an API Key to the end-user.
   *
   * Sessions are generally short-lived and by default expire in one hour.
   * Sessions can only be created for documents that have a status of done
   */
  public $session;

  /**
   * Fetches a document in the form specified by extension,
   * which can be one of pdf or zip. If an extension is not specified,
   * the document's original format is returned.
   *
   * For displaying the converted assets contained in the zip version,
   * please see the documentation for viewer.js.
   *
   * @link http://developers.box.com/viewer-js Box Viewer.js Documentation @endlink
   */
  public $content;

  /**
   * Creates a new Box View Document.
   *
   * @params array $props
   *  Array of document properties to set.
   */
  function __construct(array $props = array()) {
    foreach ($props as $key => $val) {
      $this->{$key} = $val;
    }
  }

}