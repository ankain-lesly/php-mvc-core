<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */


namespace Devlee\PHPMVCCore\DB;

use Devlee\PHPMVCCore\Exceptions\ConnectionException;
use Devlee\PHPMVCCore\Exceptions\HandleExceptionError;
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

  public function __construct()
  {
    $this->DB_HOST = $_ENV['DB_HOST'] ?? $this->handleError("DB_HOST");
    $this->DB_USER = $_ENV['DB_USERNAME'] ?? $this->handleError("DB_USERNAME");
    $this->DB_PASSWORD = $_ENV['DB_PASSWORD'] ?? "";
    $this->DB_NAME = $_ENV['DB_NAME'] ?? $this->handleError("DB_NAME");
  }

  public function connect(): PDO
  {
    try {
      $options = [
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ];
      $dns = 'mysql:host=' . $this->DB_HOST . ';dbname=' . $this->DB_NAME;
      $pdo = new PDO($dns, $this->DB_USER, $this->DB_PASSWORD, $options);

      // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      return $pdo;
    } catch (\PDOException $e) {
      $message = $e->getMessage() . " >>> Error creating connections with server!";
      HandleExceptionError::ErrorFromData("Connection Error", $message, $e->getCode());
    }
  }

  private function handleError($property)
  {
    $message = "Database $property >>> All database connection properties are required";
    throw new ConnectionException($message);
  }
}
