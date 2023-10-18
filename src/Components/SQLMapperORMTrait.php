<?php

/**
 * User: Dev_Lee
 * Date: 10/18/2023 - Time: 6:00 AM
 */

namespace Devlee\PHPMVCCore\Components;

use Devlee\PHPMVCCore\Exceptions\PropertyNotFoundException;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\DB\DBModel
 * 
 */

trait SQLMapperORMTrait
{
  /**
   * Insert data into the Database Table
   * @method create
   * @return int
   */
  public function create(array $data)
  {
    $tableName = $this->getTableName();

    $results = $this->generateSQLParams(array_keys($data));

    $sql = "INSERT INTO $tableName (" . $results['attributes'] . ")
            VALUES (" . $results['params'] . ")";

    $statement = $this->prepareStatementParams($sql, $data);

    $statement->execute();
    return $this->conn->lastInsertId();
  }

  /**
   * Update data in a Database Table 
   * @method update
   * @return bool
   */
  public function update(array $data, array $where)
  {
    $tableName = $this->getTableName();

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
    $tableName = $this->getTableName();
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

    $tableName = $this->getTableName();
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
    array $options = []
  ) {
    $tableName = $this->getTableName();

    // Working with Pagination
    $is_paginator = false;
    $pagination_sql = "";

    if (isset($options['cur_page']) && isset($options['per_page'])) {
      $is_paginator = true;
      $pagination_sql = $this->generateSQLPagination($options);
    }

    # Order By >>>
    $order_by_sql = $this->generateOrderBy($options['order_by'] ?? false);

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
      $total = $this->findCount($where)['count']  - ($options['start_at'] ?? 0);

      $result['paginator'] = $this->generatePaginator(
        (int) $total,
        (int) $options['cur_page'],
        (int) $options['per_page'],
      );
    }
    return $result;
  }


  /**
   * Fetch a single row of data from multiple tables 
   * 
   * @method findOneJoin
   * @param Model[] $models
   * @return ?array
   */
  public function findOneJoin(
    array $models,
    array $relations,
    array $where = [],
    array $select = []
  ) {
    $mainTablename = $this->getTableName();

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
          $attributes .= $model->getTableName() . '.' . $attr;
        }
      }
    } else {
      $attributes = $mainTablename . '.* ';
      foreach ($models as $key => $model) {
        $modelPrefix = $model->getTableName() . '.* ';
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
      $sql .= " INNER JOIN " . $model->getTableName();
      if ($key === 0) {
        $sql .= " ON " . $mainTablename . "." . $relations[$key] . " = "
          . $model->getTableName() . "." . $relations[$key];
      } else {
        $sql .= " ON " . $models[$key - 1]->getTableName() . "." . $relations[$key] . " = "
          . $models[$key]->getTableName() . "." . $relations[$key];
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
   * @param Model[] $models
   * @return array
   */
  public function findAllJoin(
    array $models,
    array $relations,
    array $where = [],
    array $select = [],
    array $options = []
  ) {
    $mainTablename = $this->getTableName();

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
          $attributes .= $model->getTableName() . '.' . $attr;
        }
      }
    } else {
      $attributes = $mainTablename . '.* ';

      foreach ($models as $key => $model) {
        $modelPrefix = $model->getTableName() . '.* ';
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

    if (isset($options['cur_page']) && isset($options['per_page'])) {
      $is_paginator = true;
      $pagination_sql = $this->generateSQLPagination($options);
    }

    # Order By >>>
    $order_by_sql = $this->generateOrderBy($options['order_by'] ?? false, true);

    // SQL
    $sql = "SELECT $attributes FROM " . $mainTablename;

    foreach ($models as $key => $model) {
      $sql .= " INNER JOIN " . $model->getTableName();
      if ($key === 0) {
        $sql .= " ON " . $mainTablename . "." . $relations[$key] . " = "
          . $model->getTableName() . "." . $relations[$key];
      } else {
        $sql .= " ON " . $models[$key - 1]->getTableName() . "." . $relations[$key] . " = "
          . $models[$key]->getTableName() . "." . $relations[$key];
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
      $total = $this->findCount($where)['count']  - ($options['start_at'] ?? 0);

      $result['paginator'] = $this->generatePaginator(
        (int) $total,
        (int) $options['cur_page'],
        (int) $options['per_page'],
      );
    }
    return $result;
  }

  /**
   * Search a collection of data from multiple tables 
   * @method searchJoin
   * @param Model[] $models
   * @return array
   */
  public function searchJoin(
    array $models,
    array $relations,
    array $search,
    array $where = [],
    array $select = [],
    array $options = []
  ) {
    $mainTablename = $this->getTableName();

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
          $attributes .= $model->getTableName() . '.' . $attr;
        }
      }
    } else {
      $attributes = $mainTablename . '.* ';

      foreach ($models as $key => $model) {
        $modelPrefix = $model->getTableName() . '.* ';
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

    if (isset($options['cur_page']) && isset($options['per_page'])) {
      $is_paginator = true;
      $pagination_sql = $this->generateSQLPagination($options);
    }

    # Order By >>>
    $order_by_sql = "";
    if (isset($options['order_by'])) {
      $order_by_sql = $this->generateOrderBy($options['order_by'] ?? false, true);
    }
    // SQL
    $sql = "SELECT $attributes FROM " . $mainTablename;

    foreach ($models as $key => $model) {
      $sql .= " INNER JOIN " . $model->getTableName();
      if ($key === 0) {
        $sql .= " ON " . $mainTablename . "." . $relations[$key] . " = "
          . $model->getTableName() . "." . $relations[$key];
      } else {
        $sql .= " ON " . $models[$key - 1]->getTableName() . "." . $relations[$key] . " = "
          . $models[$key]->getTableName() . "." . $relations[$key];
      }
    }

    $sql .= "$sql_where $order_by_sql $pagination_sql";
    $sql_fetch_count = "SELECT COUNT(*) AS count FROM $mainTablename $sql_where";

    $statementCount = $this->conn->prepare($sql_fetch_count);
    $statement = $this->conn->prepare($sql);

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
        (int) $options['cur_page'],
        (int) $options['per_page'],
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
    array $options = []
  ) {
    $tableName = $this->getTableName();

    $select_list = " * ";
    if ($select) {
      $select_list = implode(", ", $select);
    }

    # Pagination
    $is_paginator = false;
    $pagination_sql = "";

    if (isset($options['cur_page']) && isset($options['per_page'])) {
      $is_paginator = true;
      $pagination_sql = $this->generateSQLPagination($options);
    }

    # Order By >>>
    $order_by_sql = "";
    if (isset($options['order_by'])) {
      $order_by_sql = $this->generateOrderBy($options['order_by'] ?? false, true);
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

    $statementCount = $this->conn->prepare($sql_fetch_count);
    $statement = $this->conn->prepare($sql);

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
        (int) $options['cur_page'],
        (int) $options['per_page'],
      );
    }
    return $result;
  }
  /**
   * @method Fetch Data count
   * @return array
   */
  public function findCount(array $where = [])
  {
    $tableName = $this->getTableName();

    $attr_where = array_keys($where);

    $sql_where = $this->generateSQLWhere($attr_where);

    $sql = "SELECT COUNT(*) AS count FROM $tableName $sql_where";

    $statement = $this->prepareStatementParams($sql, $where);
    $statement->execute();
    return $statement->fetch();
  }
}
