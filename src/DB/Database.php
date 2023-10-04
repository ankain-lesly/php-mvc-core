<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */


namespace Devlee\PHPMVCCore\DB;

use Devlee\PHPMVCCore\Exceptions\CoreException;
use PDO;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\DB\Database
 */

class Database
{
  private string $DB_HOST;
  private string $DB_USER;
  private string $DB_PASSWORD;
  private string $DB_NAME;

  private static string $ERROR_PROPERTY = "conn_property";
  private static string $ERROR_FAILED = "conn_failed";

  public function __construct()
  {
    $this->DB_HOST = DB_HOST ?: $this->SetServerError(self::$ERROR_PROPERTY);
    $this->DB_USER = DB_USERNAME ?: $this->SetServerError(self::$ERROR_PROPERTY);
    $this->DB_PASSWORD = DB_PASSWORD ?: "";
    $this->DB_NAME = DB_NAME ?: $this->SetServerError(self::$ERROR_PROPERTY);
  }

  public function connect(): PDO
  {
    try {
      $dns = 'mysql:host=' . $this->DB_HOST . ';dbname=' . $this->DB_NAME;
      $pdo = new PDO($dns, $this->DB_USER, $this->DB_PASSWORD);

      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      return $pdo;
    } catch (\PDOException $error) {
      $this->SetServerError(self::$ERROR_FAILED);
    }
  }

  private function SetServerError(string $error_type)
  {
    // TODO:
    switch ($error_type) {
      case self::$ERROR_PROPERTY:
        $message = "All database connection properties are required in the config.php file";
        die($message);
        // throw new CoreException($message, 404);
        break;
      case self::$ERROR_FAILED:
        $message = "Error creating connections with server...";
        die($message);
        // throw new CoreException($message, 500);
        break;

      default:
        $message = "Unknown Error";
        die($message);
        // throw new CoreException($message, 501);
        break;
    }
  }
}
