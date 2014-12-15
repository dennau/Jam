<?php

namespace Jam\Db\Relations;


class HasOne extends Relation {

  protected $type = 'has-one';

  public function get($myFieldValue) {
    $select = $this->relatedTable->select();
    return $select->is($this->relatedTableField, $myFieldValue)->findOne();
  }

}