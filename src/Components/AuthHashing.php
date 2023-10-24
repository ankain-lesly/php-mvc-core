<?php

/**
 * User: Dev_Lee
 * Date: 10/18/2023 - Time: 6:00 AM
 */

namespace Devlee\WakerORM\Components;

use Devlee\WakerORM\Exceptions\PropertyNotFoundException;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package Waker-ORM
 * 
 */

trait AuthHashing
{
  /**
   * Compares an encrypted context with a provided string
   * @method $verifyHashed
   * 
   */
  public static function verifyHashed(string $string, string $hashedString): bool
  {
    return password_verify($string, $hashedString);
  }

  /**
   * Returns an encrypted  string
   * @method hashString
   * 
   */
  public static function hashString(string $string): string
  {
    return password_hash($string, PASSWORD_DEFAULT);
  }
}
