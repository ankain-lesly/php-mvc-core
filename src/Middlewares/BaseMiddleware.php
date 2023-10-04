<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */

namespace Devlee\PHPMVCCore;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\BaseMiddleware
 */

abstract class BaseMiddleware
{
  public function __construct()
  {
  }

  // methods to manage activity

  // middleware concepts
  abstract public function caseName(): string;
}
