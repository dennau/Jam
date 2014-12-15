<?php

namespace Jam\Db\QueryParts;


class Having {

  protected $data = [
    'column' => null,
    'operator' => null,
    'value' => null
  ];

  public function add($column, $operator, $value) {
    $this->data = [
      'column' => $column,
      'operator' => $operator,
      'value' => $value
    ];
  }

  public function query() {
    $query = '';
    if (!empty($this->data['column']) && !empty($this->data['operator']) && !empty($this->data['value'])) {
      $query = ' HAVING ' . $this->data['column'] . ' ' .
        $this->data['operator'] . ' ' .
        $this->data['value']
      ;
    }
    return $query;
  }

}