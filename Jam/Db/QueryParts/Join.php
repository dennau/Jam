<?php

namespace Jam\Db\QueryParts;


class Join {

  protected $data = [];

  public function add($table, $column1, $column2, $type) {
    $join = [
      'type' => mb_strtoupper($type),
      'table' => $table,
      'column1' => $column1,
      'column2' => $column2
    ];
    if (!in_array($join, $this->data)) {
      $this->data[] = $join;
    }
  }

  public function query() {
    $query = '';
    if (!empty($this->data)) {
      foreach ($this->data as $join) {
        $type = $join['type'];
        $table = $join['table'];
        $column1 = $join['column1'];
        $column2 = $join['column2'];
        $query .= ' ' . $type . ' ' . $table . ' ON ' . $column1 . ' = ' . $column2;
      }
    }
    return $query;
  }

}