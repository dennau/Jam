<?php

namespace Jam\Db\Relations;

use \Jam\Db\Table;

abstract class Relation {

  protected $type;

  /** @var \Jam\Db\Table */
  protected $relatedTable;
  protected $relatedTableField;
  protected $myField;

  abstract public function get($myFieldValue);

  public function getType() {
    return $this->type;
  }

  public function setRelatedTable(Table $table) {
    $this->relatedTable = $table;
    return $this;
  }

  public function setRelatedTableField($relatedTableField) {
    $this->relatedTableField = $relatedTableField;
    return $this;
  }

  public function setMyField($myField) {
    $this->myField = $myField;
    return $this;
  }

  public function getMyField() {
    return $this->myField;
  }

  public function getJoin() {
    return [
      'related' => [
        'table' => $this->relatedTable->getName(),
        'column1' => $this->relatedTableField,
        'column2' => $this->myField
      ]
    ];
  }

}