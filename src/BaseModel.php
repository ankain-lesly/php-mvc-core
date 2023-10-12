<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 9:30 PM
 * Updated: 10/06/2023 - Time: 10:00 AM
 */


namespace Devlee\PHPMVCCore;

use Devlee\PHPMVCCore\DB\Database;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\BaseModel
 */

class BaseModel
{
  protected \PDO $PDO;

  /**
   * A Validation and rule definition object
   * @property $schema
   */

  public ObjectSchema $schema;

  public function __construct()
  {
    $DB = new Database();
    $this->PDO = $DB->connect();
  }

  // public function loadData($data)
  // {
  //   foreach ($data as $key => $value) {
  //     if (property_exists($this, $key)) {
  //       $this->{$key} = $value;
  //     }
  //   }
  // }

  // public function attributes()
  // {
  //   return [];
  // }

  public function rules(): array
  {
    return [];
  }

  /**
   * A Validation layer
   * @method loadSchema
   */
  public function loadObjectSchema(ObjectSchema $object)
  {
    $this->schema = $object;
  }
  /**
   * Returns an encrypted  string
   * @method $hashString
   * 
   */
  public static function hashString(string $string): string
  {
    return password_hash($string, PASSWORD_DEFAULT);
  }

  /**
   * Compares an encrypted context with a provided string
   * @method $verifyHashed
   * 
   */
  public static function verifyHashed(string $string, string $hashedString): bool
  {
    return password_verify($string, $hashedString);
  }
}
