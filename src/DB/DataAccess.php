<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */

namespace Devlee\PHPMVCCore\DB;

use PDO;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Devlee\PHPMVCCore\DB\DataAccess
 */

class DataAccess extends Database
{

  private PDO $conn;

  public function __construct()
  {
    $this->conn = $this->connect();
  }
  // Query the Database
  public function insert(string $query, array $params)
  {
    $stmt = $this->conn->prepare($query);
    $stmt->execute($params);

    $id = $this->conn->lastInsertId();
    // $this->conn = null;
    return $id;
  }

  // Query Data
  public function query(string $query, array $params = [])
  {
    $stmt = $this->conn->prepare($query);
    $stmt->execute($params);

    return $stmt->rowCount();
  }

  // Find Single Object 
  public function findOne(string $query, array $params = [])
  {
    $stmt = $this->conn->prepare($query);
    $stmt->execute($params);

    // $this->conn = null;
    if ($stmt->rowCount() > 0) {
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return false;
  }

  // Fetch Custom Data Array
  public function findAll(string $query, array $params = [])
  {
    $stmt = $this->conn->prepare($query);
    $stmt->execute($params);

    if ($stmt->rowCount() <= 0) return false;

    $data = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }
  // Fetch Custom Query
  public function fetch(string $query)
  {
    $stmt = $this->conn->query($query);

    if ($stmt->rowCount() <= 0) return false;

    $data = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  // Fetch Data count
  public function findCount(string $table_name, array $where = [])
  {
    $attributes = array_keys($where);

    $sql_where = implode(
      " AND ",
      array_map(fn ($attr) => "$attr = :$attr", $attributes)
    );

    $sql_where = $sql_where ? "WHERE $sql_where" : '';
    $sql = "SELECT COUNT(*) AS count FROM $table_name $sql_where";

    $statement = $this->conn->prepare($sql);
    foreach ($where as $key => $item) {
      $statement->bindValue(":$key", $item);
    }

    $statement->execute();
    return $statement->fetch(PDO::FETCH_ASSOC);
  }
}
