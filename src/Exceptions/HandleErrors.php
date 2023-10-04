<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */


namespace Devlee\PHPMVCCore\Exceptions;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\Exceptions\RouterException
 */

class HandleErrors
{
  public static function DisplayErrorMessage(\Throwable $e)
  {
    $message = "<br><b>Title:</b> " . $e->getTitle();
    $message .= "<br><b>Message:</b> " . $e->getMessage();
    $message .= "<br><b>Status code:</b> " . $e->getCode();

    echo ($message);
    exit;
  }
}
