<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */

namespace Devlee\PHPMVCCore\DB;

use PDO;

use Devlee\PHPMVCCore\BaseModel;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\DB\DBModel
 * 
 */

abstract class Model extends BaseModel
{
  abstract public static function tableName(): string;

  /**
   * Insert data into the Database Table
   * @method create
   * @return int
   */
  public function create(array $data)
  {
    $tableName = $this->tableName();
    // $attributes = $this->attributes();

    $results = $this->generateSQLParams($data);

    $sql = "INSERT INTO $tableName (" . $results['attributes'] . ")
            VALUES (" . $results['params'] . ")";

    $statement = $this->prepareStatementParams($sql, $data);

    $statement->execute();
    return $this->PDO->lastInsertId();
  }

  /**
   * Update data in a Database Table 
   * @method update
   * @return bool
   */
  public function update(array $data, array $where)
  {
    $tableName = $this->tableName();

    $sql_where = $this->generateSQLWhere(array_keys($where));

    $params = [];

    foreach (array_keys($data) as $attr) {
      if (property_exists($this, $attr))
        $params[] =  "$attr = :$attr";
    }

    $sql = "UPDATE $tableName SET " . implode(",", $params) . $sql_where;

    $statement = $this->prepareStatementParams($sql, $data);
    $statement = $this->bindStatementParams($statement, $where);

    return $statement->execute();
  }

  /**
   * Delete rows from the Database table 
   * @method delete
   * @return bool
   */
  public function delete(array $where)
  {
    $tableName = static::tableName();
    $attr_where = array_keys($where);

    $sql_where = $this->generateSQLWhere($attr_where);

    $sql = "DELETE FROM $tableName $sql_where";

    $statement = $this->prepareStatementParams($sql, $where);

    $statement->execute();
    return $statement->rowCount();
  }

  /**
   * Fetch a single rows from the Database 
   * @method findOne
   * @return array
   */
  public function findOne(array $where, array $columns = [])
  {
    $select_list = " * ";
    if ($columns) {
      $select_list = implode(", ", $columns);
    }

    $tableName = static::tableName();
    $attr_where = array_keys($where);

    $sql_where = $this->generateSQLWhere($attr_where);
    $sql = "SELECT $select_list FROM $tableName $sql_where";

    $statement = $this->prepareStatementParams($sql, $where);
    $statement->execute();
    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Fetch a collection of rows from the Database 
   * @method findAll
   * @return array
   */
  public function findAll(
    array $where = [],
    array $columns = null,
    array $pagination = []
  ) {
    $tableName = static::tableName();

    // Working with Pagination
    $is_paginator = false;
    $pagination_sql = "";

    if (isset($pagination['cur_page']) && isset($pagination['per_page'])) {
      $is_paginator = true;
      $pagination_sql = $this->generateSQLPagination($pagination);
    }

    // ORDER BY CLAUSE
    // $order_by = $pagination['order_by'] ?? false;
    // $order_by_sql = $order_by ? "ORDER BY " . $this->tableName() . '.' . $order_by . " DESC" : '';
    $order_by_sql = '';

    # Select Custom attributes
    $select_list = " * ";
    if ($columns && is_array($columns)) {
      $select_list = implode(", ", $columns);
    }

    #` Handling where clause
    $attr_where = array_keys($where);
    $sql_where = $this->generateSQLWhere($attr_where);

    $sql = "SELECT $select_list FROM $tableName $sql_where $order_by_sql $pagination_sql";

    $statement = $this->prepareStatementParams($sql, $where);
    $statement->execute();

    $result = array();
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
      $result['data'][] = $row;
    }

    # Setting up Pagination Data 
    if ($result &&  $is_paginator) {
      $total = $this->findCount($where)['count']  - ($pagination['start_at'] ?? 0);

      $result['paginator'] = $this->generatePaginator(
        (int) $total,
        (int) $pagination['cur_page'],
        (int) $pagination['per_page'],
      );
    }
    return $result;
  }


  /**
   * Fetch a single row of data from multiple tables 
   * @method findOneJoin
   * @return array
   */
  // Find An Object Join 

  /**
   * Fetch data from multiple tables 
   * @method findAllJoin
   * @return array
   */
  // Find A Collection Join


  /**
   * Search a collection of data from multiple tables 
   * @method findAllJoin
   * @return array
   */
  // Search A Collection Join

  /**
   * Search a single row of data
   * @method search
   * @return array
   */
  public function search(
    array $where = [],
    array $search = [],
    array $columns = [],
    array $pagination = []
  ) {
    $tableName = static::tableName();

    $select_list = " * ";
    if ($columns) {
      $select_list = implode(", ", $columns);
    }

    # Pagination
    $is_paginator = false;
    $pagination_sql = "";

    if (isset($pagination['cur_page']) && isset($pagination['per_page'])) {
      $is_paginator = true;
      $pagination_sql = $this->generateSQLPagination($pagination);
    }

    // ORDER BY CLAUSE
    // $order_by = $pagination['order_by'] ?? '';
    // $order_by = $pagination['order_by'] ?? false;
    // $order_by_sql = $order_by ? "ORDER BY "  . $order_by . " DESC" : '';
    $order_by_sql = "";

    // Handling where clause
    $attr_where = array_keys($where);
    $attr_search = array_keys($search);

    $sql_where = $this->generateSQLWhere($attr_where);

    $search_term = $this->generateSQLWhere($attr_search, "OR", "LIKE");
    $sql_where .= $sql_where ?
      str_replace(' WHERE ', ' AND (', $search_term) . ")" :
      $search_term;

    $sql = "SELECT $select_list FROM " . $tableName;
    $sql .= " $sql_where $order_by_sql $pagination_sql";

    $sql_fetch_count = "SELECT COUNT(*) AS count FROM $tableName $sql_where";

    $statementCount = $this->PDO->prepare($sql_fetch_count);
    $statement = $this->PDO->prepare($sql);

    foreach ($where as $attr => $value) {
      if (property_exists($this, $attr)) {
        $statement->bindValue(":$attr", $value);
        $statementCount->bindValue(":$attr", $value);
      }
    }

    foreach ($search as $attr => $value) {
      if (property_exists($this, $attr)) {
        $statement->bindValue(":$attr", "%$value%");
        $statementCount->bindValue(":$attr", "%$value%");
      }
    }

    $statement->execute();

    $result = array();
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
      $result['data'][] = $row;
    }

    # Setting up Pagination Data 
    if ($result &&  $is_paginator) {
      $statementCount->execute();
      $total = $statementCount->fetch(PDO::FETCH_ASSOC)['count'];

      $result['paginator'] = $this->generatePaginator(
        (int) $total,
        (int) $pagination['cur_page'],
        (int) $pagination['per_page'],
      );
    }
    return $result;
  }
  // Fetch Custom Query
  # ---

  /**
   * @method Fetch Data count
   * @return array
   */
  public function findCount(array $where = [])
  {
    $tableName = static::tableName();

    $attr_where = array_keys($where);

    $sql_where = $this->generateSQLWhere($attr_where);

    $sql = "SELECT COUNT(*) AS count FROM $tableName $sql_where";

    $statement = $this->prepareStatementParams($sql, $where);
    $statement->execute();
    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * @method SQL Helper Generate sql params
   * @return array
   */
  private function generateSQLParams(array $dataObject)
  {
    $params = [];
    foreach (array_keys($dataObject) as  $attr) {
      if (property_exists($this, $attr) && isset($this->{$attr}))
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
  private function generateSQLWhere(array $attr_where, string $clause = 'AND', string $selector = " = ")
  {
    $sql_where = [];
    foreach ($attr_where as $attr) {
      if (property_exists($this, $attr))
        $sql_where[] =  "$attr $selector :$attr";
    }
    return $sql_where ? " WHERE " . implode(" $clause ", $sql_where) : '';
  }

  /**
   * @method Prepare sql and binds params to the prepared statement
   * @return object
   */
  private function prepareStatementParams(string $sql, array $data)
  {
    $stmt = $this->PDO->prepare($sql);
    return $this->bindStatementParams($stmt, $data);
  }


  /**
   * @method binds a sql params to a prepared statement
   * @return object
   */
  private function bindStatementParams(\PDOStatement $stmt, array $data)
  {
    foreach ($data as $attr => $value) {
      if (property_exists($this, $attr))
        $stmt->bindValue(":$attr", $value);
    }
    return $stmt;
  }

  /**
   * @method binds a sql params to a prepared statement
   * @return object
   */
  private function generateSQLPagination(array $pagination)
  {
    $pagination_sql = '';

    $index = $pagination['cur_page'] ?? false;
    $per_page = $pagination['per_page'] ?? false;
    $start_at = $pagination['start_at'] ?? 0;

    if ($index !== false && $per_page) {
      $base = $index === 0 ? $index : ($index - 1) * $per_page;
      $pagination_sql = "LIMIT " . ($base + $start_at) . ", " . $per_page;
    }

    return $pagination_sql;
  }
  private function generatePaginator(int $total, int $cur_page, int $per_page)
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
}
