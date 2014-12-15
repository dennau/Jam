<?php

namespace Jam\Db\QueryParts;


class Order {

  protected $data = [];

  public function add($column, $direction) {
    $direction = 'DESC' == $direction ? 'DESC' : 'ASC';
    $this->data[] = $column . ' ' . $direction;
  }

  public function query() {
    $query = '';
    if (!empty($this->data)) {
      $query = ' ORDER BY ' . implode(', ', $this->data);
    }
    return $query;
  }

}