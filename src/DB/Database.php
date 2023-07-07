<?php

/**
 * User: Dev_Lee
 * Date: 6/29/2023
 * Time: 6:00 AM
 */

namespace Devlee\mvccore\DB;

/**
 * Class Database
 *
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package Devlee\mvccore\DB
 */

class Database
{
  public static string $DB_HOST = '';
  public static string $DB_NAME = '';
  public static string $DB_USER = '';
  public static string $DB_PASSWORD = '';

  public static \PDO $PDO;

  public function __construct($dbConfig = [])
  {
    self::$DB_HOST = $dbConfig['host'] ?? '';
    self::$DB_NAME = $dbConfig['name'] ?? '';
    self::$DB_USER = $dbConfig['user'] ?? '';
    self::$DB_PASSWORD = $dbConfig['password'] ?? '';
  }

  public function connect(): \PDO
  {
    $host =  empty(self::$DB_HOST) ? die("DB_HOST is required for Database connection use the DBModel::SetDatabaseDetails(DB_CONFIG_Object)") : self::$DB_HOST;
    $name =  empty(self::$DB_NAME) ? die("DB_NAME is required for Database connection use the DBModel::SetDatabaseDetails(DB_CONFIG_Object)") : self::$DB_NAME;
    $username =  empty(self::$DB_USER) ? die("DB_USER is required for Database connection use the DBModel::SetDatabaseDetails(DB_CONFIG_Object)") : self::$DB_USER;
    $password =  self::$DB_PASSWORD;

    $dns = 'mysql:host=' . $host . ';dbname=' . $name;
    $pdo = new \PDO($dns, $username, $password);

    self::$PDO = $pdo;
    self::$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    return self::$PDO;
  }
}
