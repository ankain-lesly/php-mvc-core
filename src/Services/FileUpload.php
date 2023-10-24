<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */


namespace Devlee\WakerORM\Services;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Waker-ORM
 */

class FileUpload
{
  // Modes
  public static string $MODE_SINGLE = 'single';
  public static string $MODE_MULTIPLE = 'multiple';

  // Modifiers
  private array $ACCEPT_TYPES;
  private string $FILENAME;
  private ?string $MAX_SIZE = null;
  private string $PATH;
  private string $MODE;

  // upload errors
  private array $upload_errors = [];

  // file to upload
  private array $uploadObj = [];

  // upload dir, filename,

  public function __construct()
  {
    // Default Values
    $this->MODE = "single";
    $this->ACCEPT_TYPES = ['.jpg', '.png', '.jpeg'];
  }

  public function upload()
  {
    if (!empty($this->upload_errors)) return false;

    foreach ($this->uploadObj as $key => $obj) {
      move_uploaded_file($obj['temp'], $obj['path']);
    }
  }

  // setup files
  public function setup(array $options, array $file_object)
  {
    // setting up files
    $this->options($options);

    if (empty($file_object)) return $this->setUploadErrors('', "Please provide a file to upload");

    switch ($this->MODE) {
      case 'single':
        return $this->singleFile($file_object);
        break;
      case 'multiple':
        return $this->multipleFiles($file_object);
        break;
      default:
        return $this->singleFile($file_object);
        break;
    }
  }

  // Setup Single File  
  private function singleFile(array $file)
  {
    $f_name = $file['name'];
    $tmp_file = $file['tmp_name'];

    $nameExtension = explode('.', $f_name);
    $extension = '.' . strtolower(end($nameExtension));

    $validateObj = array(
      "size" => $file['size'],
      "type" => $extension,
    );

    // validating file
    if (!$this->validate($validateObj)) return false;

    $upload_name = $this->FILENAME . $extension;

    $this->uploadObj[] = [
      'temp' => $tmp_file,
      'path' => $this->PATH . $upload_name,
    ];
    return $upload_name;
  }
  // Setup Multiple Files  
  private function multipleFiles(array $files)
  {
    $filenames = array();

    for ($index = 0; $index < count($files['name']); $index++) {

      $f_name = $files['name'][$index];
      $tmp_file = $files['tmp_name'][$index];

      $nameExtension = explode('.', $f_name);
      $extension = '.' . strtolower(end($nameExtension));

      $validateObj = array(
        "size" => $files['size'][$index],
        "type" => $extension,
      );

      // validating file
      if (!$this->validate($validateObj)) return false;

      $upload_name = $this->FILENAME . '-00' . $index . $extension;
      $filenames[] = $upload_name;

      $this->uploadObj[] = [
        'temp' => $tmp_file,
        'path' => $this->PATH . $upload_name,
      ];
    }
    return $filenames;
  }
  private function options(array $options)
  {
    $this->ACCEPT_TYPES = $options['accept'];
    $this->FILENAME = $options['filename'];
    $this->PATH = $options['path'];

    $this->MODE = $options['mode'] ?? $this->MODE;
    $this->MAX_SIZE = $options['maxsize'] ?? $this->MAX_SIZE;
  }

  // VALIDATOR: errors
  private function validate(array $rules)
  {
    extract($rules);
    //File Type
    if ($type) {
      if (!in_array($type, $this->ACCEPT_TYPES)) {
        $this->setUploadErrors("Invalid file format: $type. Expected " . implode(", ", $this->ACCEPT_TYPES));
        return false;
      }
    }

    return true;
  }
  //add Errors
  private function setUploadErrors(string $error, string $message = null)
  {
    $message = $message ? $message : "Error uploading file! " . $this->FILENAME;
    $this->upload_errors['errors'][] = $error;
    $this->upload_errors['message'] = $message;
  }

  // Getting Errors
  public function errors()
  {
    return !empty($this->upload_errors) ? $this->upload_errors : false;
  }
}
