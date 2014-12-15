<?php

namespace Jam\Db\QueryParts;


class Group {

  protected $data = [];

  public function add($column) {
    $this->data[] = $column;
  }

  public function query() {
    $query = '';
    if (!empty($this->data)) {
      $query = ' GROUP BY ' . implode(', ', $this->data);
    }
    return $query;
  }

}