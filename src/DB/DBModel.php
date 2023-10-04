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
 */

abstract class DBModel extends BaseModel
{
  // abstract public function attributes(): array;
  abstract public static function tableName(): string;
  // abstract public function getDisplayName(): string;

  // public static function primaryKey(): string
  // {
  //   return 'id';
  // }

  // Insert Data
  public function insert()
  {
    // $this->loadData($data);

    // if (!$this->validate()) {
    //   return ["errors" => $this->getErrors()];
    // }

    $tableName = $this->tableName();
    $attributes = $this->attributes();

    // $params = array_map(fn ($attr) => ":$attr", $attributes);
    $params = [];
    foreach ($attributes as  $attr) {
      if (property_exists($this, $attr) && isset($this->{$attr}))
        $params[] =  $attr;
    }
    $sql = "INSERT INTO $tableName (" . implode(",", $params) . ") 
                VALUES (" . implode(",", array_map(fn ($attr) => ":$attr", $params)) . ")";

    $statement = $this->PDO->prepare($sql);

    foreach ($params as $attr) {
      if (property_exists($this, $attr))
        $statement->bindValue(":$attr", $this->{$attr});
    }

    return $statement->execute();
  }

  // Update Data
  public function update(array $data, array $where)
  {
    $tableName = $this->tableName();

    // $attributes = array_keys($data);
    $attr_data = array_keys($data);
    $attr_where = array_keys($where);

    $where_sql = [];
    foreach ($attr_where as $attr) {
      if (property_exists($this, $attr))
        $where_sql[] =  "$attr = :$attr";
    }

    $params = [];
    foreach ($attr_data as $attr) {
      if (property_exists($this, $attr))
        $params[] =  "$attr = :$attr";
    }

    $sql = "UPDATE $tableName SET " . implode(",", $params) . "
            WHERE " . implode(" AND ", $where_sql);
    $statement = $this->PDO->prepare($sql);

    foreach ($attr_data as $attr) {
      if (property_exists($this, $attr))
        $statement->bindValue(":$attr", $data[$attr]);
    }
    foreach ($attr_where as $attr) {
      if (property_exists($this, $attr))
        $statement->bindValue(":$attr", $where[$attr]);
    }

    return $statement->execute();
  }
  // Delete Data
  public function delete(array $where)
  {

    $tableName = static::tableName();
    $attributes = array_keys($where);

    $sql_where = implode(" AND ", array_map(fn ($attr) => "$attr = :$attr", $attributes));
    $sql = "DELETE FROM $tableName WHERE $sql_where";

    $statement = $this->PDO->prepare($sql);
    foreach ($where as $key => $item) {
      $statement->bindValue(":$key", $item);
    }

    $statement->execute();
    return $statement->rowCount();
  }

  // Find Single Object 
  public function findOne(array $where, array $columns = [], string $selector = 'AND')
  {
    $select_list = " * ";
    if ($columns && is_array($columns)) {
      $select_list = implode(", ", $columns);
    }

    $tableName = static::tableName();
    $attributes = array_keys($where);

    $sql_where = implode(" $selector ", array_map(fn ($attr) => "$attr = :$attr", $attributes));

    $sql = "SELECT $select_list FROM $tableName WHERE $sql_where";

    $statement = $this->PDO->prepare($sql);
    foreach ($where as $key => $value) {
      if (property_exists($this, $key))  $statement->bindValue(":$key", $value);
    }
    $statement->execute();
    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  // Find a Collection of objects
  public function findAll(
    array $where = [],
    array $columns = null,
    array $pagination = []
  ) {

    // Working with Pagination
    $pagination_sql = '';
    $current_page = $pagination['current_page'] ?? false;
    $page_limit = $pagination['page_limit'] ?? false;
    $start_at = $pagination['start_at'] ?? 0;
    if ($current_page !== false && $page_limit) {
      $base = $current_page === 0 ? $current_page : ($current_page - 1) * $page_limit;
      $pagination_sql = "LIMIT " . ($base + $start_at) . ", " . $page_limit;
    }

    // ORDER BY CLAUSE
    $order_by = $pagination['order_by'] ?? false;
    $order_sql = $order_by ? "ORDER BY " . $this->tableName() . '.' . $order_by . " DESC" : '';

    // Select Custom attributes
    $select_list = " * ";
    if ($columns && is_array($columns)) {
      $select_list = implode(
        ", ",
        $columns
      );
    }

    // Getting Table name
    $tableName = static::tableName();

    // Handling where clause
    $attributes = array_keys($where);

    $sql_where = implode(
      " AND ",
      array_map(fn ($attr) => "$attr = :$attr", $attributes)
    );

    $sql_where = $sql_where ? "WHERE $sql_where" : '';

    $sql = "SELECT $select_list FROM $tableName $sql_where $order_sql $pagination_sql";

    $statement = $this->PDO->prepare($sql);
    foreach ($where as $key => $item) {
      $statement->bindValue(":$key", $item);
    }
    $statement->execute();

    $result = array('data' => []);
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
      $result['data'][] = $row;
    }

    // Sending Pagination Data 
    if ($result && $current_page !== false && $page_limit) {
      $total_rows = $this->findCount($where)['count']  - $start_at;

      $pages = round($total_rows / ($page_limit));
      $result['paginator'] = [
        "current_page" => $current_page === 0 ? 1 : $current_page,
        "total_pages" =>  $pages ? $pages : 1,
        "page_limit" => $page_limit,
        "order_by" => $order_by,
        "total_rows" => $total_rows,
      ];
    }
    return $result;
  }

  // Find A Collection Join
  public function findOneJoin(
    array $models,
    array $common_column,
    array $where = [],
    array $columns = []
  ) {
    // Getting Table names
    $Attributes = array();

    $mainTablename = static::tableName();
    $MainPrefixes = substr($this->tableName(), 3, 1) . "_";

    // Creating Select attributes
    $Attributes = implode(",", array_map(fn ($attr) => $mainTablename . ".$attr AS " . $MainPrefixes . $attr, $this->attributes()));

    foreach ($models as $key => $model) {
      $modelPrefix = substr($model->tableName(), 3, 1) . "_";
      $Attributes .= ", ";
      $Attributes .= implode(",", array_map(fn ($attr) => $model->tableName() . ".$attr AS " . $modelPrefix . $attr, $model->attributes()));
    }

    // Handling where clause
    $attributes = array_keys($where);

    $sql_where = implode(
      " AND ",
      array_map(fn ($attr) => $mainTablename . '.' . $attr . " = :$attr", $attributes)
    );

    $sql_where = $sql_where ? "WHERE $sql_where" : '';

    $sql = "SELECT $Attributes FROM " . $mainTablename;
    foreach ($models as $key => $model) {
      $sql .= " INNER JOIN " . $model->tableName();
      if ($key === 0) {
        $sql .= " ON " . $mainTablename . "." . $common_column[$key] . " = " . $model->tableName() . "." . $common_column[$key];
      } else {
        $sql .= " ON " . $models[$key - 1]->tableName() . "." . $common_column[$key] . " = " . $models[$key]->tableName() . "." . $common_column[$key];
      }
    }
    $sql .= " $sql_where";

    $statement = $this->PDO->prepare($sql);
    foreach ($where as $key => $item) {
      $statement->bindValue(":$key", $item);
    }
    $statement->execute();
    return $statement->fetch(PDO::FETCH_ASSOC);
  }
  // Find A Collection Join
  public function findAllJoin(
    array $models,
    array $common_column,
    array $where = [],
    array $columns = [],
    array $pagination = []
  ) {
    // Getting Table names
    $Attributes = array();

    $mainTablename = static::tableName();
    $MainPrefixes = substr($this->tableName(), 3, 1) . "_";

    // Creating Select attributes
    $Attributes = implode(",", array_map(fn ($attr) => $mainTablename . ".$attr AS " . $MainPrefixes . $attr, $this->attributes()));

    foreach ($models as $key => $model) {
      $modelPrefix = substr($model->tableName(), 3, 1) . "_";
      $Attributes .= ", ";
      $Attributes .= implode(",", array_map(fn ($attr) => $model->tableName() . ".$attr AS " . $modelPrefix . $attr, $model->attributes()));
    }

    // Working with Pagination
    $pagination_sql = '';
    $current_page = $pagination['current_page'] ?? false;
    $page_limit = $pagination['page_limit'] ?? false;
    $start_at = $pagination['start_at'] ?? 0;
    if ($current_page !== false && $page_limit) {
      $base = $current_page === 0 ? $current_page : ($current_page - 1) * $page_limit;
      $pagination_sql = "LIMIT " . ($base + $start_at) . ", " . $page_limit;
    }

    // ORDER BY CLAUSE
    // $order_by = $pagination['order_by'] ?? '';
    $order_by = $pagination['order_by'] ?? false;
    $order_sql = $order_by ? "ORDER BY " . $mainTablename . '.' . $order_by . " DESC" : '';

    // Handling where clause
    $attributes = array_keys($where);

    $sql_where = implode(
      " AND ",
      array_map(fn ($attr) => $mainTablename . '.' . $attr . " = :$attr", $attributes)
    );

    $sql_where = $sql_where ? "WHERE $sql_where" : '';

    $sql = "SELECT $Attributes FROM " . $mainTablename;
    foreach ($models as $key => $model) {
      $sql .= " INNER JOIN " . $model->tableName();
      if ($key === 0) {
        $sql .= " ON " . $mainTablename . "." . $common_column[$key] . " = " . $model->tableName() . "." . $common_column[$key];
      } else {
        $sql .= " ON " . $models[$key - 1]->tableName() . "." . $common_column[$key] . " = " . $models[$key]->tableName() . "." . $common_column[$key];
      }
    }
    $sql .= " $sql_where $order_sql $pagination_sql";
    $statement = $this->PDO->prepare($sql);
    foreach ($where as $key => $item) {
      $statement->bindValue(":$key", $item);
    }
    $statement->execute();

    $result = array();
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
      $result['data'][] = $row;
    }

    //  Pagination Data 
    if ($result && $current_page !== false && $page_limit) {
      $total_rows = $this->findCount($where)['count']  - $start_at;

      $pages = round($total_rows / ($page_limit));
      $result['paginator'] = $pagination = [
        "current_page" => $current_page === 0 ? 1 : $current_page,
        "total_pages" =>  $pages ? $pages : 1,
        "page_limit" => $page_limit,
        "order_by" => $order_by,
        "total_rows" => $total_rows,
      ];
    }
    return $result;
  }
  // Search A Collection Join
  public function searchJoin(
    array $models,
    array $common_column,
    array $where = [],
    array $search_columns = [],
    array $pagination = []
  ) {
    // Getting Table names
    $Attributes = array();

    $mainTablename = static::tableName();
    $MainPrefixes = substr($this->tableName(), 3, 1) . "_";

    // Creating Select attributes
    $Attributes = implode(",", array_map(fn ($attr) => $mainTablename . ".$attr AS " . $MainPrefixes . $attr, $this->attributes()));

    foreach ($models as $key => $model) {
      $modelPrefix = substr($model->tableName(), 3, 1) . "_";
      $Attributes .= ", ";
      $Attributes .= implode(",", array_map(fn ($attr) => $model->tableName() . ".$attr AS " . $modelPrefix . $attr, $model->attributes()));
    }

    // Working with Pagination
    $pagination_sql = '';
    $current_page = $pagination['current_page'] ?? false;
    $page_limit = $pagination['page_limit'] ?? false;
    $start_at = $pagination['start_at'] ?? 0;
    if ($current_page !== false && $page_limit) {
      $base = $current_page === 0 ? $current_page : ($current_page - 1) * $page_limit;
      $pagination_sql = "LIMIT " . ($base + $start_at) . ", " . $page_limit;
    }

    // ORDER BY CLAUSE
    // $order_by = $pagination['order_by'] ?? '';
    $order_by = $pagination['order_by'] ?? false;
    $order_sql = $order_by ? "ORDER BY " . $mainTablename . '.' . $order_by . " DESC" : '';

    // Handling where clause
    $attributes = array_keys($where);
    $sql_where = implode(
      " AND ",
      array_map(fn ($attr) => $mainTablename . '.' . $attr . " = :$attr", $attributes)
    );
    // Search Algorithm
    $sql_where .= implode(
      " OR ",
      array_map(fn ($col) => $mainTablename . '.' . $col . " LIKE :$col", array_keys($search_columns))
    );

    $sql_where = $sql_where ? "WHERE $sql_where" : '';

    $sql = "SELECT $Attributes FROM " . $mainTablename;

    foreach ($models as $key => $model) {
      $sql .= " INNER JOIN " . $model->tableName();
      if ($key === 0) {
        $sql .= " ON " . $mainTablename . "." . $common_column[$key] . " = " . $model->tableName() . "." . $common_column[$key];
      } else {
        $sql .= " ON " . $models[$key - 1]->tableName() . "." . $common_column[$key] . " = " . $models[$key]->tableName() . "." . $common_column[$key];
      }
    }
    $sql .= " $sql_where $order_sql $pagination_sql";
    $sql_fetch_count = "SELECT COUNT(*) AS count FROM $mainTablename $sql_where";

    $statement = $this->PDO->prepare($sql);
    $statementCount = $this->PDO->prepare($sql_fetch_count);

    foreach ($where as $key => $item) {
      // var_dump(property_exists($this, $key));
      $statement->bindValue(":$key", $item);
      $statementCount->bindValue(":$key", $item);
    }
    foreach ($search_columns as $key => $item) {
      // var_dump(property_exists($this, $key));
      $statement->bindValue(":$key", "%$item%");
      $statementCount->bindValue(":$key", "%$item%");
    }

    $statement->execute();

    $result = array();
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
      $result['data'][] = $row;
    }
    //  Pagination Data 
    if ($result && $current_page !== false && $page_limit) {
      $statementCount->execute();
      $total_rows = $statementCount->fetch(PDO::FETCH_ASSOC)['count'];

      $pages = round($total_rows / ($page_limit));
      $result['paginator'] = [
        "current_page" => $current_page === 0 ? 1 : $current_page,
        "total_pages" =>  $pages ? $pages : 1,
        "page_limit" => $page_limit,
        "order_by" => $order_by,
        "total_rows" => $total_rows,
      ];
    }
    return $result;
  }
  // Search A Collection Join
  public function search(
    array $where = [],
    array $search_columns = [],
    array $columns = [],
    array $pagination = []
  ) {
    $select_list = " * ";
    if ($columns && is_array($columns)) {
      $select_list = implode(", ", $columns);
    }

    // Working with Pagination
    $pagination_sql = '';
    $current_page = $pagination['current_page'] ?? false;
    $page_limit = $pagination['page_limit'] ?? false;
    $start_at = $pagination['start_at'] ?? 0;
    if ($current_page !== false && $page_limit) {
      $base = $current_page === 0 ? $current_page : ($current_page - 1) * $page_limit;
      $pagination_sql = "LIMIT " . ($base + $start_at) . ", " . $page_limit;
    }

    // ORDER BY CLAUSE
    // $order_by = $pagination['order_by'] ?? '';
    $order_by = $pagination['order_by'] ?? false;
    $order_sql = $order_by ? "ORDER BY "  . $order_by . " DESC" : '';

    // Handling where clause
    $attributes = array_keys($where);
    $sql_where = implode(
      " AND ",
      array_map(fn ($attr) => $attr . " = :$attr", $attributes)
    );
    // Search Algorithm
    $sql_where .= implode(
      " OR ",
      array_map(fn ($col) => $col . " LIKE :$col", array_keys($search_columns))
    );
    $tableName = static::tableName();

    $sql_where = $sql_where ? "WHERE $sql_where" : '';

    $sql = "SELECT $select_list FROM " . $tableName;

    $sql .= " $sql_where $order_sql $pagination_sql";
    $sql_fetch_count = "SELECT COUNT(*) AS count FROM $tableName $sql_where";

    $statement = $this->PDO->prepare($sql);
    $statementCount = $this->PDO->prepare($sql_fetch_count);

    foreach ($where as $key => $item) {
      // var_dump(property_exists($this, $key));
      $statement->bindValue(":$key", $item);
      $statementCount->bindValue(":$key", $item);
    }
    foreach ($search_columns as $key => $item) {
      // var_dump(property_exists($this, $key));
      $statement->bindValue(":$key", "%$item%");
      $statementCount->bindValue(":$key", "%$item%");
    }

    $statement->execute();

    $result = array();
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
      $result['data'][] = $row;
    }
    //  Pagination Data 
    if ($result && $current_page !== false && $page_limit) {
      $statementCount->execute();
      $total_rows = $statementCount->fetch(PDO::FETCH_ASSOC)['count'];

      $pages = round($total_rows / ($page_limit));
      $result['paginator'] = [
        "current_page" => $current_page === 0 ? 1 : $current_page,
        "total_pages" =>  $pages ? $pages : 1,
        "page_limit" => $page_limit,
        "order_by" => $order_by,
        "total_rows" => $total_rows,
      ];
    }
    return $result;
  }
  // Fetch Custom Query
  # ---
  // Fetch Data count
  public function findCount(array $where = [])
  {
    $tableName = static::tableName();

    $attributes = array_keys($where);

    $sql_where = implode(
      " AND ",
      array_map(fn ($attr) => "$attr = :$attr", $attributes)
    );

    $sql_where = $sql_where ? "WHERE $sql_where" : '';

    $sql = "SELECT COUNT(*) AS count FROM $tableName $sql_where";

    $statement = $this->PDO->prepare($sql);
    foreach ($where as $key => $item) {
      $statement->bindValue(":$key", $item);
    }

    $statement->execute();
    $data = $statement->fetch(PDO::FETCH_ASSOC);
    return $data;
  }
}
