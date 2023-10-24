<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 * Updated: 10/18/2023 - Time: 6:00 AM
 */

namespace Devlee\WakerORM\DB;

use Devlee\WakerORM\BaseModel;
use Devlee\WakerORM\Components\AuthHashing;
use Devlee\WakerORM\Components\SQLBuilder;
use Devlee\WakerORM\Components\SQLMapperORMTrait;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Waker-ORM
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
