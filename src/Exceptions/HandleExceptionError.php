<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 9:30 PM
 * Updated: 10/06/2023 - Time: 10:00 AM
 */


namespace Devlee\PHPMVCCore\Exceptions;

use Throwable;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  php-mvc-core
 */

class HandleExceptionError
{
  public static function ErrorFromData(string $title, string $message, int $code)
  {
    // http_response_code($e->getCode());
    $responseView = "<br><b>Title:</b> " . $title;
    $responseView .= "<br><b>Message:</b> " . $message;
    $responseView .= "<br><b>Status code:</b> " . $code;

    echo ($responseView);
    exit;
  }
  /**
   * @param BaseException|Throwable $e getTitle
   */
  public static function DisplayError(Throwable $e)
  {
    http_response_code($e->getCode());
    $responseView = "<br><b>Title:</b> " . $e?->getTitle() ?? "";
    $responseView .= "<br><b>Message:</b> " . $e->getMessage();
    $responseView .= "<br><b>Status code:</b> " . $e->getCode();

    echo ($responseView);
    exit;
  }
  public static function FormatError(BaseException|Throwable $e)
  {
    http_response_code($e->getCode());

    $responseView = "<br><b>Title:</b> " . $e->getTitle();
    $responseView .= "<br><b>Message:</b> " . $e->getMessage();
    $responseView .= "<br><b>Status code:</b> " . $e->getCode();

    echo ($responseView);
    exit;
  }
}
