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

class PropertyNotFoundException extends BaseException
{
  public function __construct(string $property, string $class)
  {
    $message = "Property <mark><b>$property</b></mark> was not not found in your Application Model <mark><b>$class</b></mark>";
    parent::__construct($message, 404, 'Property Not Found');
    HandleExceptionError::DisplayError($this);
  }
}
