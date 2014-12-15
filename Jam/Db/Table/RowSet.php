<?php

namespace Jam\Db\Table;


class RowSet implements \Iterator, \Countable {

  protected $items = [];
  protected $modelClassName;

  public function __construct(array $items, $modelClassName) {
    $this->items = (array) $items;
    $this->modelClassName = $modelClassName;
  }

  public function current() {
    $data = current($this->items);
    return new $this->modelClassName($data);
  }

  public function next() {
    next($this->items);
  }

  public function key() {
    return key($this->items);
  }

  public function valid() {
    $key = $this->key();
    return null !== $key && false !== $key;
  }

  public function rewind() {
    reset($this->items);
  }

  public function count() {
    return count($this->items);
  }

  public function __toString() {
    $string = '';
    $this->rewind();
    while ($this->valid()) {
      $string .= "\n" . print_r($this->current(), true);
      $this->next();
    }
    return $string;
  }

}