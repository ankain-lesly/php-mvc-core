<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */

namespace Devlee\PHPMVCCore\DB;

use Devlee\PHPMVCCore\BaseModel;
use Devlee\PHPMVCCore\Exceptions\PropertyNotFoundException;

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

    $results = $this->generateSQLParams(array_keys($data));

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
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
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
  public function findOne(array $where, array $select = [])
  {
    $select_list = " * ";
    if ($select) {
      $select_list = implode(", ", $select);
    }

    $tableName = static::tableName();
    $attr_where = array_keys($where);

    $sql_where = $this->generateSQLWhere($attr_where);
    $sql = "SELECT $select_list FROM $tableName $sql_where";

    $statement = $this->prepareStatementParams($sql, $where);
    $statement->execute();
    return $statement->fetch() || [];
  }

  /**
   * Fetch a collection of rows from the Database 
   * @method findAll
   * @return array
   */
  public function findAll(
    array $where = [],
    array $select = [],
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

    # Order By >>>
    $order_by_sql = $this->generateOrderBy($pagination['order_by'] ?? false);

    # Select Custom attributes
    $select_list = " * ";
    if ($select) {
      $select_list = implode(", ", $select);
    }

    #` Handling where clause
    $attr_where = array_keys($where);
    $sql_where = $this->generateSQLWhere($attr_where);

    $sql = "SELECT $select_list FROM $tableName $sql_where $order_by_sql $pagination_sql";

    $statement = $this->prepareStatementParams($sql, $where);
    $statement->execute();

    $result = array();
    while ($row = $statement->fetch()) {
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
  public function findOneJoin(
    array $models,
    array $relations,
    array $where = [],
    array $select = []
  ) {
    $mainTablename = static::tableName();

    $attributes = '';
    if ($select && is_string($select[0])) {
      foreach ($select as $key => $attr) {
        if ($key !== 0)
          $attributes .= ', ';

        if (!property_exists($this, $attr))
          throw new PropertyNotFoundException($attr, static::class);
        $attributes .= $mainTablename . '.' . $attr;
      }
    } else if ($select && is_array($select[0])) {
      foreach ($select as $modelKey => $attrs) {
        foreach ($attrs as $key => $attr) {
          if ($key !== 0 || $modelKey !== 0)
            $attributes .= ', ';
          $model = null;
          if ($modelKey !== 0) {
            $model = $models[$modelKey - 1];
          } else {
            $model = $this;
          }
          if (!property_exists($model, $attr))
            throw new PropertyNotFoundException($attr, static::class);
          $attributes .= $model::tableName() . '.' . $attr;
        }
      }
    } else {
      $attributes = $mainTablename . '.* ';
      foreach ($models as $key => $model) {
        $modelPrefix = $model->tableName() . '.* ';
        $attributes .= ", " . $modelPrefix;
      }
    }

    // Handling where clause
    $join_where = array();
    foreach (array_keys($where) as $attr) {
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
      $join_where[] = $mainTablename . '.' . $attr . ' = :' . $attr;
    }
    $attr_where = $join_where ? " WHERE " . implode(" AND ", $join_where) : '';

    // SQL
    $sql = "SELECT $attributes FROM " . $mainTablename;

    foreach ($models as $key => $model) {
      $sql .= " INNER JOIN " . $model->tableName();
      if ($key === 0) {
        $sql .= " ON " . $mainTablename . "." . $relations[$key] . " = "
          . $model->tableName() . "." . $relations[$key];
      } else {
        $sql .= " ON " . $models[$key - 1]->tableName() . "." . $relations[$key] . " = "
          . $models[$key]->tableName() . "." . $relations[$key];
      }
    }
    $sql .= $attr_where;

    $statement = $this->prepareStatementParams($sql, $where);
    $statement->execute();
    return $statement->fetch();
  }

  /**
   * Fetch data from multiple tables 
   * @method findAllJoin
   * @return array
   */
  public function findAllJoin(
    array $models,
    array $relations,
    array $where = [],
    array $select = [],
    array $pagination = []
  ) {
    $mainTablename = static::tableName();

    $attributes = '';
    if ($select && is_string($select[0])) {
      foreach ($select as $key => $attr) {
        if ($key !== 0)
          $attributes .= ', ';

        if (!property_exists($this, $attr))
          throw new PropertyNotFoundException($attr, static::class);
        $attributes .= $mainTablename . '.' . $attr;
      }
    } else if ($select && is_array($select[0])) {
      foreach ($select as $modelKey => $attrs) {
        foreach ($attrs as $key => $attr) {
          if ($key !== 0 || $modelKey !== 0)
            $attributes .= ', ';
          $model = null;
          if ($modelKey !== 0) {
            $model = $models[$modelKey - 1];
          } else {
            $model = $this;
          }
          if (!property_exists($model, $attr))
            throw new PropertyNotFoundException($attr, static::class);
          $attributes .= $model::tableName() . '.' . $attr;
        }
      }
    } else {
      $attributes = $mainTablename . '.* ';

      foreach ($models as $key => $model) {
        $modelPrefix = $model->tableName() . '.* ';
        $attributes .= ", " . $modelPrefix;
      }
    }

    // Handling where clause
    $join_where = array();
    foreach (array_keys($where) as $attr) {
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
      $join_where[] = $mainTablename . '.' . $attr . ' = :' . $attr;
    }
    $attr_where = $join_where ? " WHERE " . implode(" AND ", $join_where) : '';

    # Pagination
    $is_paginator = false;
    $pagination_sql = "";

    if (isset($pagination['cur_page']) && isset($pagination['per_page'])) {
      $is_paginator = true;
      $pagination_sql = $this->generateSQLPagination($pagination);
    }

    # Order By >>>
    $order_by_sql = $this->generateOrderBy($pagination['order_by'] ?? false, true);

    // SQL
    $sql = "SELECT $attributes FROM " . $mainTablename;

    foreach ($models as $key => $model) {
      $sql .= " INNER JOIN " . $model->tableName();
      if ($key === 0) {
        $sql .= " ON " . $mainTablename . "." . $relations[$key] . " = "
          . $model->tableName() . "." . $relations[$key];
      } else {
        $sql .= " ON " . $models[$key - 1]->tableName() . "." . $relations[$key] . " = "
          . $models[$key]->tableName() . "." . $relations[$key];
      }
    }
    $sql .= "$attr_where $order_by_sql $pagination_sql";

    $statement = $this->prepareStatementParams($sql, $where);
    $statement->execute();

    $result = array();
    while ($row = $statement->fetch()) {
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
   * Search a collection of data from multiple tables 
   * @method searchJoin
   * @return array
   */
  public function searchJoin(
    array $models,
    array $relations,
    array $search,
    array $where = [],
    array $select = [],
    array $pagination = []
  ) {
    $mainTablename = static::tableName();

    $attributes = '';
    if ($select && is_string($select[0])) {
      foreach ($select as $key => $attr) {
        if ($key !== 0)
          $attributes .= ', ';

        if (!property_exists($this, $attr))
          throw new PropertyNotFoundException($attr, static::class);
        $attributes .= $mainTablename . '.' . $attr;
      }
    } else if ($select && is_array($select[0])) {
      foreach ($select as $modelKey => $attrs) {
        foreach ($attrs as $key => $attr) {
          if ($key !== 0 || $modelKey !== 0)
            $attributes .= ', ';
          $model = null;
          if ($modelKey !== 0) {
            $model = $models[$modelKey - 1];
          } else {
            $model = $this;
          }
          if (!property_exists($model, $attr))
            throw new PropertyNotFoundException($attr, static::class);
          $attributes .= $model::tableName() . '.' . $attr;
        }
      }
    } else {
      $attributes = $mainTablename . '.* ';

      foreach ($models as $key => $model) {
        $modelPrefix = $model->tableName() . '.* ';
        $attributes .= ", " . $modelPrefix;
      }
    }

    // Handling where clause
    $join_where = array();
    foreach (array_keys($where) as $attr) {
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
      $join_where[] = $mainTablename . '.' . $attr . ' = :' . $attr;
    }
    $sql_where = $join_where ? " WHERE " . implode(" AND ", $join_where) : '';

    // Handling where clause
    $join_search = array();
    foreach (array_keys($search) as $attr) {
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
      $join_search[] = $mainTablename . '.' . $attr . ' LIKE :' . $attr;
    }

    $join_search = implode(" OR ", $join_search);
    $join_search = " ( $join_search ) ";

    $sql_where .= $join_where ? " AND " . $join_search : " WHERE " . $join_search;

    # Pagination
    $is_paginator = false;
    $pagination_sql = "";

    if (isset($pagination['cur_page']) && isset($pagination['per_page'])) {
      $is_paginator = true;
      $pagination_sql = $this->generateSQLPagination($pagination);
    }

    # Order By >>>
    $order_by_sql = "";
    if (isset($pagination['order_by'])) {
      $order_by_sql = $this->generateOrderBy($pagination['order_by'] ?? false, true);
    }
    // SQL
    $sql = "SELECT $attributes FROM " . $mainTablename;

    foreach ($models as $key => $model) {
      $sql .= " INNER JOIN " . $model->tableName();
      if ($key === 0) {
        $sql .= " ON " . $mainTablename . "." . $relations[$key] . " = "
          . $model->tableName() . "." . $relations[$key];
      } else {
        $sql .= " ON " . $models[$key - 1]->tableName() . "." . $relations[$key] . " = "
          . $models[$key]->tableName() . "." . $relations[$key];
      }
    }

    $sql .= "$sql_where $order_by_sql $pagination_sql";
    $sql_fetch_count = "SELECT COUNT(*) AS count FROM $mainTablename $sql_where";

    $statementCount = $this->PDO->prepare($sql_fetch_count);
    $statement = $this->PDO->prepare($sql);

    foreach ($where as $attr => $value) {
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
      $statement->bindValue(":$attr", $value);
      $statementCount->bindValue(":$attr", $value);
    }

    foreach ($search as $attr => $value) {
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
      $statement->bindValue(":$attr", "%$value%");
      $statementCount->bindValue(":$attr", "%$value%");
    }

    $statement->execute();

    $result = array();
    while ($row = $statement->fetch()) {
      $result['data'][] = $row;
    }

    # Setting up Pagination Data 
    if ($result &&  $is_paginator) {
      $statementCount->execute();
      $total = $statementCount->fetch()['count'];

      $result['paginator'] = $this->generatePaginator(
        (int) $total,
        (int) $pagination['cur_page'],
        (int) $pagination['per_page'],
      );
    }
    return $result;
  }

  /**
   * Search a single row of data
   * @method search
   * @return array
   */
  public function search(
    array $search,
    array $where = [],
    array $select = [],
    array $pagination = []
  ) {
    $tableName = static::tableName();

    $select_list = " * ";
    if ($select) {
      $select_list = implode(", ", $select);
    }

    # Pagination
    $is_paginator = false;
    $pagination_sql = "";

    if (isset($pagination['cur_page']) && isset($pagination['per_page'])) {
      $is_paginator = true;
      $pagination_sql = $this->generateSQLPagination($pagination);
    }

    # Order By >>>
    $order_by_sql = "";
    if (isset($pagination['order_by'])) {
      $order_by_sql = $this->generateOrderBy($pagination['order_by'] ?? false, true);
    }

    // Handling where clause
    $attr_where = array_keys($where);
    $attr_search = array_keys($search);

    $sql_where = $this->generateSQLWhere($attr_where);

    $search_term = $this->generateSQLWhere($attr_search, "OR", "LIKE");
    $search_term = " WHERE ( " . str_replace(' WHERE ', '', $search_term) . " ) ";

    $sql_where .= $sql_where ? str_replace(' WHERE ', ' AND ', $search_term) : $search_term;

    $sql = "SELECT $select_list FROM " . $tableName;
    $sql .= " $sql_where $order_by_sql $pagination_sql";

    $sql_fetch_count = "SELECT COUNT(*) AS count FROM $tableName $sql_where";

    $statementCount = $this->PDO->prepare($sql_fetch_count);
    $statement = $this->PDO->prepare($sql);

    foreach ($where as $attr => $value) {
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
      $statement->bindValue(":$attr", $value);
      $statementCount->bindValue(":$attr", $value);
    }

    foreach ($search as $attr => $value) {
      if (!property_exists($this, $attr))
        throw new PropertyNotFoundException($attr, static::class);
      $statement->bindValue(":$attr", "%$value%");
      $statementCount->bindValue(":$attr", "%$value%");
    }

    $statement->execute();

    $result = array();
    while ($row = $statement->fetch()) {
      $result['data'][] = $row;
    }

    # Setting up Pagination Data 
    if ($result &&  $is_paginator) {
      $statementCount->execute();
      $total = $statementCount->fetch()['count'];

      $result['paginator'] = $this->generatePaginator(
        (int) $total,
        (int) $pagination['cur_page'],
        (int) $pagination['per_page'],
      );
    }
    return $result;
  }

  /**
   * @method Create custom SQL Query
   */

  // Fetch Custom Query

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
    return $statement->fetch();
  }

  /**
   * @method SQL Helper Generate sql params
   * @return array
   */
  private function generateSQLParams(array $data)
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
  private function generateSQLWhere(array $attr_where, string $clause = 'AND', string $selector = " = ")
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

  private function generateOrderBy($order_by_data, bool $isJoin = false)
  {
    // TODO: 
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
