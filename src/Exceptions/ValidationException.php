<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 9:30 PM
 * Updated: 10/06/2023 - Time: 10:00 AM
 */

namespace Devlee\WakerORM\Exceptions;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Waker-ORM
 */

class ValidationException extends _ModuleBaseException
{
  public function __construct(string $message, array $context = [])
  {
    parent::__construct($message, 'Validation Error', 422);
    HandleModuleExceptions::setup($this, $context);
  }
}
