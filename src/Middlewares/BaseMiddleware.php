<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */


namespace Devlee\PHPMVCCore;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\BaseModel
 */

abstract class BaseMiddleware
{
  public static Session $session;
  // inherit properties from the parent ware

  public function __construct()
  {
    // $this->UserObj = new User();
    self::$session = new Session();
  }
  // private Session $session;
  // public function __construct()
  // {
  //   // $this->UserObj = new User();
  //   $this->session = new Session();
  // }

  // middleware concepts
  abstract public function caseName(): string;
}
