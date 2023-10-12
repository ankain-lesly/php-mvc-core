<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 9:30 PM
 * Updated: 10/06/2023 - Time: 10:00 AM
 */


namespace Devlee\PHPMVCCore\Exceptions;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  php-mvc-core
 */

abstract class MVCBaseException extends \Exception
{
  private const VALIDATION_ERROR = 400;
  private const UNAUTHORIZED = 401;
  private const FORBIDDEN = 403;
  private const NOT_FOUND = 404;
  private const SERVER_ERROR = 500;
  private const ROUTER_DIR_ERROR = 444;

  // protected $message;
  // protected $code;
  // protected string $title = "";

  public function __construct(string $message, int $code = 0, protected string $title = '')
  {
    $this->message = $message;
    if (!$title) {
      $this->title = self::getExceptionTitleCode($code);
    }
    $this->code = $code;
  }

  public static function getExceptionTitleCode($code)
  {
    $objectMessages = array(
      self::VALIDATION_ERROR => [
        "code" => self::VALIDATION_ERROR,
        "title" => "Validation Failed",
      ],
      self::ROUTER_DIR_ERROR => [
        "code" => self::ROUTER_DIR_ERROR,
        "title" => "Error Getting File",
      ],
      self::NOT_FOUND => [
        "code" => self::NOT_FOUND,
        // "title" => "Resource Not Found",
        "title" => "Error Getting File",
      ],
      self::FORBIDDEN => [
        "code" => self::FORBIDDEN,
        "title" => "Forbidden",
      ],
      self::SERVER_ERROR => [
        "code" => self::SERVER_ERROR,
        "title" => "Server Error",
      ],
      self::UNAUTHORIZED => [
        "code" => self::UNAUTHORIZED,
        "title" => "Unauthorized",
      ],
    );

    $message = $code ? $objectMessages[$code] : "Uncaught Error";
    return $message['title'] ?? "Uncaught Error";
  }

  public function getTitle()
  {
    return $this->title;
  }
}
