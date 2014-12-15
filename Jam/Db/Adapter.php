<?php

namespace Jam\Db;


class Adapter {

  /** @var \PDO */
  protected $pdo;

  public function __construct(\Pdo $pdo) {
    $this->pdo = $pdo;
  }

  public function query($query, $binds = []) {
    $stmt = $this->pdo->prepare($query);
    $stmt->execute($binds);
    return $stmt;
  }

  public function fetchAll($query, $binds = []) {
    $rows = $this->query($query, $binds)->fetchAll(\PDO::FETCH_ASSOC);
    return !empty($rows) ? $rows : null;
  }

  public function fetchOne($query, $binds = []) {
    $result = $this->query($query, $binds)->fetch(\PDO::FETCH_NUM);
    return false === $result ? null : $result[0];
  }

  public function fetchRow($query, $binds = []) {
    $result = $this->query($query, $binds)->fetch(\PDO::FETCH_ASSOC);
    return false === $result ? null : $result;
  }

  public function fetchColumn($query, $binds = []) {
    $result = $this->query($query, $binds)->fetchAll(\PDO::FETCH_COLUMN, 0);
    return false === $result ? null : $result;
  }

  public function getFoundRows() {
    return $this->fetchOne('SELECT FOUND_ROWS()');
  }

  public function describe($tableName) {
    return $this->fetchAll("DESCRIBE `".$tableName."`");
  }

  public function save($tableName, array $data) {
    $prepared = $this->getPrepared($data);
    $preparedData = implode(', ', $prepared['data']);
    $query = "INSERT INTO `$tableName` SET $preparedData ON DUPLICATE KEY UPDATE $preparedData";
    $stmt = $this->query($query, $prepared['binds']);
    if (!($stmt instanceof \PDOStatement)) {
      return false;
    }
    return $this->pdo->lastInsertId() > 0
      ? $this->pdo->lastInsertId()
      : $stmt->rowCount()
    ;
  }

  public function delete($tableName, array $data) {
    $prepared = $this->getPrepared($data);
    $preparedData = implode(' AND ', $prepared['data']);
    $query = "DELETE FROM `$tableName` WHERE $preparedData";
    $stmt = $this->query($query, $prepared['binds']);
    return !($stmt instanceof \PDOStatement) ? false : $stmt->rowCount();
  }

  protected function getPrepared($data) {
    $preparedData = $binds = [];
    foreach ($data as $field => $value) {
      $preparedData[] = '`'.$field.'` = :'.$field;
      $binds[':'.$field] = $value;
    }
    return [
      'data' => $preparedData,
      'binds' => $binds
    ];
  }

}