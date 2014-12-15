<?php

namespace Jam\Db\Relations;


class HasMany extends Relation {

  protected $type = 'has-many';

  public function get($myFieldValue) {
    $select = $this->relatedTable->select();
    return $select->is($this->relatedTableField, $myFieldValue)->find();
  }

}