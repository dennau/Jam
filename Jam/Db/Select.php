<?php

namespace Jam\Db;

use Jam\Db\QueryParts\Where;
use Jam\Db\QueryParts\Join;
use Jam\Db\QueryParts\Group;
use Jam\Db\QueryParts\Having;
use Jam\Db\QueryParts\Order;
use Jam\Db\QueryParts\Limit;

/**
 * Class Select
 * @package Jam\Db
 * @method $this is($field, $value = null)
 * @method $this notIs($field, $value = null)
 * @method $this like($field, $value)
 * @method $this notLike($field, $value)
 * @method $this between($field, $value)
 * @method $this notBetween($field, $value)
 * @method $this regexp($field, $value)
 * @method $this notRegexp($field, $value)
 * @method $this gt($field, $value)
 * @method $this gtEq($field, $value)
 * @method $this lt($field, $value)
 * @method $this ltEq($field, $value)
 *
 * @method $this glueAnd()
 * @method $this glueOr()
 * @method $this openBracket()
 * @method $this closeBracket()
 *
 * @method $this getCount($field, $alias = null)
 * @method $this getDistinct($field, $alias = null)
 * @method $this getAverage($field, $alias = null)
 * @method $this getMin($field, $alias = null)
 * @method $this getMax($field, $alias = null)
 * @method $this getSum($field, $alias = null)
 * @method $this getSingleColumn($field)
 */

class Select {

  /** @var Adapter */
  protected $adapter;

  /** @var Table */
  protected $table;

  protected $modelClassName;
  protected $tableName = '';
  protected $columns = [];

  /** @var Where */
  protected $where;

  /** @var Join */
  protected $join;

  /** @var Group */
  protected $group;

  /** @var Having */
  protected $having;

  /** @var Order */
  protected $order;

  /** @var Limit */
  protected $limit;

  protected $binds = [];
  protected $asArray = false;
  protected $currentFunction = null;

  public function __construct(Table $table) {
    $this->table = $table;
    $this->tableName = $table->getName();
    $this->adapter = $table->getAdapter();
    $this->modelClassName = str_replace('Table', 'Model', get_class($table));
    $this->columns = [$table->getName() . '.*'];
  }

  protected function getWhere() {
    if (!$this->where) {
      $this->where = new Where();
    }
    return $this->where;
  }

  public function __call($method, $args) {
    if (in_array($method, ['glueAnd', 'glueOr', 'openBracket', 'closeBracket'])) {
      $this->getWhere()->simpleOperator($method);
      return $this;
    } elseif (in_array($method, ['gt', 'gtEq', 'lt', 'ltEq'])) {
      $column = $this->grave($args[0]);
      $value = $args[1];
      $this->getWhere()->makeGL($column, $value, $method);
      return $this;
    } elseif (preg_match('!^(not|)(is)$!i', $method, $match)) {
      $not = 'not' == $match[1] ? true : false;
      $column = $this->grave($args[0]);
      $value = isset($args[1]) ? $args[1] : null;
      if (is_array($value)) {
        $placeholders = [];
        foreach ($value as $val) {
          $placeholders[] = $this->bind($val);
        }
        $ine = 'in';
        $value = '(' . implode(', ', $placeholders) . ')';
      } elseif (null !== $value) {
        $ine = 'equals';
        $value =  $this->bind($value);
      } else {
        $ine = 'null';
        $value = '';
      }
      $this->getWhere()->makeIne($column, $value, $not, $ine);
      return $this;
    } elseif (preg_match('!^(not|)(like|between|regexp)$!i', $method, $match)) {
      $not = 'not' == $match[1] ? true : false;
      $lbr = lcfirst($match[2]);
      $column = $this->grave($args[0]);
      $value = $args[1];
      if ('between' == $lbr) {
        $value = (array) $value;
        $value = $this->bind(min($value)) . ' AND ' . $this->bind(max($value));
      } else {
        $value = $this->bind($value);
      }
      $this->getWhere()->makeLBR($column, $value, $not, $lbr);
      return $this;
    } elseif (in_array($method, ['getCount', 'getDistinct', 'getAverage', 'getMin', 'getMax', 'getSum', 'getSingleColumn'])) {
      $alias = !empty($args[1]) ? $args[1] : null;
      $this->makeFunctions($method, $args[0], $alias);
      return $this;
    } elseif (preg_match('!^(with)(Via)?[A-Z]!', $method, $methodMatch)) {
      $methodMatch = $methodMatch[1] . (!empty($methodMatch[2]) ? $methodMatch[2] : '');
      $relationName = StringsHelper::getUnderscore(str_replace($methodMatch, '', $method));
      $relation = $this->table->getRelation($relationName);
      if (!$relation) {
        throw new \LogicException(
          sprintf('Unknown relation %s for %s table', $relationName, get_class($this))
        );
      }
      $joins = $relation->getJoin();
      if ('withVia' == $methodMatch) {
        if ('many-to-many' != $relation->getType()) {
          throw new \LogicException(
            sprintf('Unknown Via table for relation %s', $relationName)
          );
        }
        $join = $joins['via'];
        $this->join($join['table'], $join['column1'], $join['column2'], 'LEFT OUTER JOIN');
        /** @var Relations\ManyToMany $relation */
        $relatedTableFieldInVia = $relation->getRelatedTableFieldInVia();
        $value = !isset($args[1]) ? null : $args[1];
        $operator = !isset($args[0]) ? null : $args[0];
        if ($operator) {
          $this->$operator($relatedTableFieldInVia, $value);
        }
      } else {
        foreach ($joins as $join) {
          $this->join($join['table'], $join['column1'], $join['column2']);
        }
      }
      return $this;
    }
    throw new \BadMethodCallException(
      sprintf('%s: unknown method %s::%s()', get_class($this), get_class($this), $method)
    );
  }

  protected function makeFunctions($function, $column, $alias = null) {
    $function = mb_convert_case(str_replace('get', '', $function), MB_CASE_UPPER);
    if ('SINGLECOLUMN' == $function) {
      $expression = $column;
    } else {
      $expression = $function . ('DISTINCT' == $function ? ' ' . $column : '(' . $column . ')' ) . (null === $alias ? '' : ' AS ' . $alias);
    }
    $this->columns = [$this->grave($expression)];
    $this->currentFunction = $function;
  }

  protected function queryTable() {
    return $this->grave($this->tableName);
  }

  protected function queryColumns() {
    $columns = [];
    foreach ($this->columns as $column) {
      $columns[] = $this->grave(trim($column));
    }
    return implode(', ', $columns);
  }

  /**
   * @param $table
   * @param $column1
   * @param $column2
   * @param string $type
   * @return $this
   */
  public function join($table, $column1, $column2, $type = 'INNER JOIN') {
    if (!$this->join) {
      $this->join = new Join();
    }
    $this->join->add(
      $this->grave($table),
      $this->grave($column1),
      $this->grave($column2),
      mb_strtoupper($type)
    );
    return $this;
  }

  /**
   * @param $column
   * @return $this
   */
  public function group($column) {
    if (!$this->group) {
      $this->group = new Group();
    }
    $this->group->add($this->grave($column));
    return $this;
  }

  /**
   * @param $column
   * @param $operator
   * @param $value
   * @return $this
   */
  public function having($column, $operator, $value) {
    if (!$this->having) {
      $this->having = new Having();
    }
    $this->having->add(
      $this->grave($column),
      $operator,
      $this->bind($value)
    );
    return $this;
  }

  /**
   * @param $column
   * @param string $direction
   * @return $this
   */
  public function order($column, $direction = 'ASC') {
    if (!$this->order) {
      $this->order = new Order();
    }
    $this->order->add($this->grave($column), $direction);
    return $this;
  }

  /**
   * @param $limit
   * @param null $offset
   * @return $this
   */
  public function limit($limit, $offset = null) {
    if (!$this->limit) {
      $this->limit = new Limit();
    }
    $this->limit->add($limit, $offset);
    return $this;
  }

  /** @param $field
   * @return mixed|string
   */
  protected function grave($field) {
    $fieldParts = explode(' ', $field);
    $graveField = '';
    foreach ($fieldParts as $part) {
      if (!preg_match('!^[\d\.]+$!u', $part)) {
        $part = str_replace('.', '`.`', $part);
      }
      if (preg_match('!COUNT\(|DISTINCT|AVG\(|MIN\(|MAX\(|SUM\(!iu', $field)) {
        $part = str_replace(['(', ')'], ['(`', '`)'], $part);
      }
      $notInArray = (!in_array($part, array('AS', 'as', '=', '*', '+', '-')));
      $notFunction = (!preg_match('!COUNT\(|DISTINCT|AVG\(|MIN\(|MAX\(|SUM\(!iu', $part));
      $notFloat = (!preg_match('!^[\d\.]+$!u', $part));
      if ($notInArray && $notFunction && $notFloat) {
        $part = '`' . $part . '`';
      }
      $graveField .= ' ' . $part;
    }
    $graveField = str_replace('`*`','*', $graveField);
    return preg_replace('!`{2,}!', '`', $graveField);
  }

  public function query() {
    $query = 'SELECT SQL_CALC_FOUND_ROWS ' . $this->queryColumns() . ' FROM ' . $this->queryTable();
    $query .= $this->join ? $this->join->query() : '';
    $query .= $this->where ? $this->where->query() : '';

    if ($this->group) {
      $query .= $this->group->query();
      $query .= $this->having ? $this->having->query() : '';
    }
    $query .= $this->order ? $this->order->query() : '';
    $query .= $this->limit ? $this->limit->query() : '';
    return preg_replace('!\s{2,}!', ' ', $query);
  }

  public function bind($value) {
    $field = ':' . uniqid();
    $this->binds[$field] = $value;
    return $field;
  }

  public function binds() {
    return $this->binds;
  }

  public function getAdapter() {
    return $this->adapter;
  }

  public function asArray() {
    $this->asArray = true;
    return $this;
  }

  public function find() {
    $query = $this->query();
    $binds = $this->binds();
    if ($this->currentFunction) {
      if (in_array($this->currentFunction, ['DISTINCT', 'SINGLECOLUMN']) || $this->group) {
        $rows = $this->getAdapter()->fetchColumn($query, $binds);
      } else {
        $rows = $this->getAdapter()->fetchOne($query, $binds);
      }
      $this->asArray();
      $this->currentFunction = null;
    } else {
      $rows = $this->getAdapter()->fetchAll($query, $binds);
    }
    if ($this->asArray) {
      $this->asArray = false;
      return $rows;
    }
    $rows = null === $rows ? [] : $rows;
    return new Table\RowSet($rows, $this->modelClassName);
  }

  public function findOne() {
    $this->limit(1);
    $query = $this->query();
    $binds = $this->binds();
    $rows = $this->getAdapter()->fetchRow($query, $binds);
    if ($this->asArray) {
      $this->asArray = false;
      return $rows;
    }
    return null === $rows
      ? null
      : new $this->modelClassName($rows)
    ;
  }

  public function found() {
    return $this->getAdapter()->getFoundRows();
  }

  public function __toString() {
    return str_replace(
      array_keys($this->binds),
      array_values($this->binds),
      $this->query()
    );
  }

}