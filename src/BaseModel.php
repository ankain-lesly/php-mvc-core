<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 9:30 PM
 * Updated: 10/18/2023 - Time: 6:00 AM
 */


namespace Devlee\PHPMVCCore;

use Devlee\PHPMVCCore\DB\Database;
use Devlee\PHPMVCCore\Exceptions\PropertyNotFoundException;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\BaseModel
 */

class BaseModel
{
  protected \PDO $conn;

  /**
   * A Validation and rule definition object
   * @property $schema
   */

  public ObjectSchema $schema;

  public function __construct()
  {
    $DB = new Database();
    $this->conn = $DB->connect();
  }

  public function rules(): array
  {
    return [];
  }

  /**
   * A Validation layer
   * @method loadSchema
   */
  public function loadObjectSchema(ObjectSchema $object)
  {
    $this->schema = $object;
  }
  /**
   * Override default class name mapper
   * Set a custom tablename for this model
   * 
   * @method tableName
   * 
   * @return string
   */
  public static function tableName(): string
  {
    return '';
  }

  /**
   * Generates a tablename|collection based on class name
   * Or use custom value parse from the tablename method
   * 
   * @method getTableName
   * 
   */
  public function getTableName(): string
  {
    echo '<pre>';

    $tableName = $this->tablename();
    if ($tableName !== '') return $tableName;

    $classArray = explode("\\", static::class);
    $className = @end($classArray);

    print_r($className);

    /**
     * generating base table|collection name
     */

    exit($tableName);
    return $tableName;
  }

  #>>>>>>>>>>>>>>>>><<<<<<<<<<<<<<<<<#
  #>>>>>>>>    GENERATORS <<<<<<<<<<<#
  #>>>>>>>>>>>>>>>>><<<<<<<<<<<<<<<<<#

  /**
   * @method SQL Helper Generate sql params
   * @return array
   */
  protected function generateSQLParams(array $data)
  {
    $params = [];
    foreach ($data as  $attr) {
      // if (property_exists($this, $attr) && isset($this->{$attr}))
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
      $params[] =  $attr;
    }
    return [
      'attributes' => implode(",", $params),
      'params' => implode(",", array_map(fn ($attr) => ":$attr", $params)),
    ];
  }

  /**
   * @method SQL Helper Generate sql params
   * @return array
   */
  protected function generateSQLWhere(array $attr_where, string $clause = 'AND', string $selector = " = ")
  {
    $sql_where = [];
    foreach ($attr_where as $attr) {
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
      $sql_where[] =  "$attr $selector :$attr";
    }
    return $sql_where ? " WHERE " . implode(" $clause ", $sql_where) : '';
  }

  /**
   * @method Prepare sql and binds params to the prepared statement
   * @return object
   */
  protected function prepareStatementParams(string $sql, array $data)
  {
    $stmt = $this->conn->prepare($sql);
    return $this->bindStatementParams($stmt, $data);
  }


  /**
   * @method binds a sql params to a prepared statement
   * @return object
   */
  protected function bindStatementParams(\PDOStatement $stmt, array $data)
  {
    foreach ($data as $attr => $value) {
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
      $stmt->bindValue(":$attr", $value);
    }
    return $stmt;
  }

  /**
   * @method binds a sql params to a prepared statement
   * @return object
   */
  protected function generateSQLPagination(array $pagination)
  {
    $pagination_sql = '';

    $index = $options['cur_page'] ?? false;
    $per_page = $options['per_page'] ?? false;
    $start_at = $options['start_at'] ?? 0;

    if ($index !== false && $per_page) {
      $base = $index === 0 ? $index : ($index - 1) * $per_page;
      $pagination_sql = "LIMIT " . ($base + $start_at) . ", " . $per_page;
    }

    return $pagination_sql;
  }
  protected function generatePaginator(int $total, int $cur_page, int $per_page)
  {
    $total_pages = ceil($total / $per_page);

    $pages = $total_pages ? $total_pages : 1;
    $cur_page = $cur_page <= 0 ? 1 : $cur_page;

    return [
      "cur_page" => $cur_page,
      "pages" =>  $pages,
      "per_page" => $per_page,
      "total" => $total,
      "has_prev" => $cur_page > 1,
      "has_next" => $cur_page < $pages,
    ];
  }

  protected function generateOrderBy($order_by_data, bool $isJoin = false)
  {
    // TODO-Done: 
    $order_by = $this->{"order_by"} ?? "";
    $direction = $this->{"direction"} ?? false;
    $direction = $order_by ? ($direction ? $direction : "DESC") : "";

    if (is_array($order_by_data)) {
      $order_by = $order_by_data[0];
      $direction = $order_by_data[1];
    } elseif (is_string($order_by_data)) {
      $order_by = $order_by_data;
    }

    if ($isJoin)
      return $order_by ? "ORDER BY " . $this->{'tableName'}() . ".$order_by $direction" : '';
    else
      return $order_by ? "ORDER BY $order_by $direction" : '';
  }
}
