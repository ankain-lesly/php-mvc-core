<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 9:30 PM
 * Updated: 10/06/2023 - Time: 10:00 AM
 */

namespace Devlee\PHPMVCCore\Exceptions;

use Exception;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  php-mvc-core
 */

class ConnectionException extends BaseException
{
  public function __construct(string $message)
  {
    parent::__construct($message, 500, 'Connections Error');
    HandleExceptionError::DisplayError($this);
  }
}
