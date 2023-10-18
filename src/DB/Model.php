<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 * Updated: 10/18/2023 - Time: 6:00 AM
 */

namespace Devlee\PHPMVCCore\DB;

use Devlee\PHPMVCCore\BaseModel;
use Devlee\PHPMVCCore\Components\AuthHashing;
use Devlee\PHPMVCCore\Components\SQLBuilder;
use Devlee\PHPMVCCore\Components\SQLMapperORMTrait;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\DB\DBModel
 * 
 */

abstract class Model extends BaseModel
{
  /**
   * Using an SQL Mapper
   */
  use SQLMapperORMTrait, AuthHashing;

  /**
   * Using an SQL Builder: Build custom Queries
   * 
   * @property SQLBuilder $build
   */
  public ?SQLBuilder $build = null;

  public function __construct()
  {
    $this->build = new SQLBuilder();

    parent::__construct();
  }
}
