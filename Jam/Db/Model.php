<?php

namespace Jam\Db;


class Model {

  protected $primaryName;
  protected $fields = [];

  public function __construct($data = []) {
    if (!empty($data)) {
      foreach ($data as $field => $value) {
        if (array_key_exists($field, $this->fields)) {
          $this->fields[$field] = $value;
        }
      }
    }
  }

  public function __call($method, $args) {
    if (preg_match('!^(get|set|attach|detach)!', $method, $methodMatch)) {
      $methodMatch = $methodMatch[0];
      $field = StringsHelper::getUnderscore(str_replace($methodMatch, '', $method));
      $value = isset($args[0]) ? $args[0] : null;
      if (array_key_exists($field, $this->fields)) {
        if ('get' == $methodMatch) {
          return $this->fields[$field];
        } else {
          if ($this->primaryName == $field) {
            throw new \LogicException(
              sprintf('Can not set value for primary key: %s::%s', get_class($this), $field)
            );
          }
          $this->fields[$field] = $value;
          return $this;
        }
      } else {
        $relation = $this->table()->getRelation($field);
        if (!$relation) {
          throw new \LogicException(
            sprintf('Unknown relation %s for %s table', $field, get_class($this))
          );
        }
        if (in_array($methodMatch, ['attach', 'detach'])) {
          if (!($value instanceof Table\RowSet) && !is_array($value)) {
            $value = [$value];
          }
          $value = !empty($value) ? $value : [null];
          $success = true;
          /** @var Relations\ManyToMany $relation */
          foreach ($value as $model) {
            $success &= $this->table()->$methodMatch($relation, $this, $model);
          }
          return $success;
        } else {
          $myField = str_replace($this->table()->getName() . '.', '', $relation->getMyField());
          $myFieldValue = $this->fields[$myField];
          return $relation->get($myFieldValue);
        }
      }
    }

    throw new \BadMethodCallException(
      sprintf('Unknown method: %s::%s()', get_class($this), $method)
    );
  }

  /**
   * @return Table
   */
  public function table() {
    $class = str_replace('Model', 'Table', get_class($this));
    return $class::instance();
  }

  public function save() {
    $result = $this->table()->save($this->fields);
    if (empty($this->fields[$this->primaryName])) {
      $this->fields[$this->primaryName] = $result;
    }
  }

  public function delete() {
    if (!empty($this->fields[$this->primaryName])) {
      $relations = $this->table()->relations();
      if (!empty($relations)) {
        foreach ($relations as $relation) {
          if ('many-to-many' == $relation->getType()) {
            /** @var $relation Relations\ManyToMany */
            $this->table()->detach($relation, $this);
          }
        }
      }
      $this->table()->delete([
        $this->primaryName => $this->fields[$this->primaryName]
      ]);
    }
  }

}