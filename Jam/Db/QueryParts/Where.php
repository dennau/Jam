<?php

namespace Jam\Db\QueryParts;


class Where {

  protected $data = [];

  public function add($whereCondition){
    $this->data[] = $whereCondition;
  }

  public function makeIne($field, $value = null, $not = false, $ine) {
    $operators = ['in' => ' IN ', 'null' => ' IS NULL', 'equals' => ' = '];
    if (true === $not) {
      $operators = ['in' => ' NOT IN ', 'null' => ' IS NOT NULL', 'equals' => ' != '];
    }
    $this->data[] = $field . $operators[$ine] . $value;
  }

  public function makeLBR($field, $value, $not = false, $lbr) {
    $operators = ['like' => ' LIKE ', 'between' => ' BETWEEN ', 'regexp' => ' REGEXP '];
    if (true === $not) {
      $operators = ['like' => ' NOT LIKE ', 'between' => ' NOT BETWEEN ', 'regexp' => ' NOT REGEXP '];
    }
    $this->data[] = $field . $operators[$lbr] . $value;
  }

  public function makeGL($column, $value, $operator) {
    $operators = [
        'gt' => ' > ',
        'gtEq' => ' >= ',
        'lt' => ' < ',
        'ltEq' => ' <= '
    ];
    if (array_key_exists($operator, $operators)) {
      $this->data[] = $column . $operators[$operator] . $value;
    }
  }

  public function simpleOperator($operator) {
    $operators = [
        'glueAnd' => 'AND',
        'glueOr' => 'OR',
        'openBracket' => '(',
        'closeBracket' => ')',
    ];
    if (array_key_exists($operator, $operators)) {
      $this->data[] = $operators[$operator];
    }
  }

  public function query() {
    $query = '';
    if (!empty($this->data)) {
      $query = ' WHERE ' . implode(' ', $this->data);
      $query = preg_replace('!\s{2,}!us', ' ', $query);
      $query = str_replace(['WHERE AND', 'WHERE OR', '( AND', '( OR'], ['WHERE', 'WHERE', '(', '('], $query);
    }
    return $query;
  }

}