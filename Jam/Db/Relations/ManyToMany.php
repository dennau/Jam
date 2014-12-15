<?php

namespace Jam\Db\Relations;


class ManyToMany extends Relation {

  protected $type = 'many-to-many';
  protected $viaTable;
  protected $relatedTableFieldInVia = '';
  protected $myFieldInVia = '';

  public function get($myFieldValue) {
    $select = $this->relatedTable->select();
    $select->join($this->viaTable, $this->relatedTableFieldInVia, $this->relatedTableField)
      ->is($this->myFieldInVia, $myFieldValue);
    return $select->find();
  }

  public function setViaTable($viaTable) {
    $this->viaTable = $viaTable;
    return $this;
  }

  public function setRelatedTableFieldInVia($relatedTableFieldInVia) {
    $this->relatedTableFieldInVia = preg_replace(
      '!^([^\.]+\.)?!',
      $this->viaTable . '.',
      $relatedTableFieldInVia
    );
    return $this;
  }

  public function setMyFieldInVia($myFieldInVia) {
    $this->myFieldInVia = preg_replace(
      '!^([^\.]+\.)?!',
      $this->viaTable . '.',
      $myFieldInVia
    );
    return $this;
  }

  public function getViaTable() {
    return $this->viaTable;
  }

  public function getRelatedTableFieldInVia() {
    return $this->relatedTableFieldInVia;
  }

  public function getMyFieldInVia() {
    return $this->myFieldInVia;
  }

  public function getJoin() {
    return [
      'via' => [
        'table' => $this->viaTable,
        'column1' => $this->myFieldInVia,
        'column2' => $this->myField
      ],
      'related' => [
        'table' => $this->relatedTable->getName(),
        'column1' => $this->relatedTableFieldInVia,
        'column2' => $this->relatedTableField
      ]
    ];
  }

}