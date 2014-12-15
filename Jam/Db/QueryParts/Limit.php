<?php

namespace Jam\Db\QueryParts;


class Limit {

  protected $data = [
    'limit' => null,
    'offset' => null
  ];

  public function add($limit, $offset) {
    $this->data['limit'] = intval($limit);
    $this->data['offset'] = intval($offset);
  }

  public function query() {
    $query = '';
    if (!empty($this->data['limit'])) {
      $query = ' LIMIT ' .
          (!empty($this->data['offset']) ? ' ' . $this->data['offset'] . ', ' : '') .
          $this->data['limit'];
    }
    return $query;
  }

}