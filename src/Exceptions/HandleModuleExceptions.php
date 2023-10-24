<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 9:30 PM
 * Updated: 10/23/2023 - Time: 7:39 PM
 */


namespace Devlee\WakerORM\Exceptions;

use Devlee\ErrorTree\ErrorTree;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Waker-ORM
 */

class HandleModuleExceptions
{
  public static function setup(_ModuleBaseException $e, array $context = [])
  {
    $ExcData = array(
      'title' => $e->getTitle(),
      'code' => $e->getCode(),
      'message' => $e->getMessage(),
      // 'file' => $e->getTrace()[0]['file'] ?? 'No file',
      // 'line' => $e->getTrace()[0]['line'] ?? 'No Line',
    );

    if ($context)
      $ExcData = array_merge($ExcData, $context);

    ErrorTree::RenderError($ExcData);
  }
}
