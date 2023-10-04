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
  private static string $ERROR_STATUS = "conn_failed";

  public function __construct()
  {
    $this->DB_HOST = DB_HOST ?? $this->SetServerError(self::$ERROR_PROPERTY);
    $this->DB_USER = DB_USERNAME ?? $this->SetServerError(self::$ERROR_PROPERTY);
    $this->DB_PASSWORD = DB_PASSWORD ?? $this->SetServerError(self::$ERROR_PROPERTY);
    $this->DB_NAME = DB_NAME ?? $this->SetServerError(self::$ERROR_PROPERTY);
  }

  public function connect(): PDO
  {
    $host = empty(self::$DB_HOST) ? die("DB_HOST is required for Database connection use the DBModel::SetDatabaseDetails(DB_CONFIG_Object)") : self::$DB_HOST;
    $name = empty(self::$DB_NAME) ? die("DB_NAME is required for Database connection use the DBModel::SetDatabaseDetails(DB_CONFIG_Object)") : self::$DB_NAME;
    $username = empty(self::$DB_USER) ? die("DB_USER is required for Database connection use the DBModel::SetDatabaseDetails(DB_CONFIG_Object)") : self::$DB_USER;
    $password =  self::$DB_PASSWORD;
    try {
      //code...
      $dns = 'mysql:host=' . $host . ';dbname=' . $name;
      $pdo = new PDO($dns, $username, $password);

      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      return $pdo;
    } catch (\PDOException $error) {
      // TODO:
      echo "Error creating connections with server...";
      exit;
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

      default:
        # code...
        break;
    }
  }
}
