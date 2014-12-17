<?php

namespace Jam\Db;


abstract class Table {

  private static $instances = [];
  protected $tableName;
  protected $primaryName;
  protected $columns = [];
  protected $connectionName = 'default';

  final protected static function getInstance() {
    $class = get_called_class();
    if (empty(self::$instances[$class])) {
      self::$instances[$class] = new static($class);
    }
    return self::$instances[$class];
  }

  public static function __callStatic($method, $args) {
    if ('instance' == $method) {
      return self::getInstance();
    }
    throw new \BadMethodCallException(
      sprintf('Unknown static method %s::%s()', get_called_class(), $method)
    );
  }

  public function __get($propertyName) {
    $column = StringsHelper::getUnderscore($propertyName);
    if (in_array($column, $this->columns)) {
      return $this->tableName . '.' . $column;
    }
    throw new \BadMethodCallException(
      sprintf('%s: unknown property %s::%s', get_class($this), get_class($this), $propertyName)
    );
  }

  final private function __construct() {
    if (empty($this->tableName)) {
      throw new \RuntimeException(
        sprintf('Empty required property %s::tableName', get_class($this))
      );
    }
  }

  final private function __clone() {}

  public function getName() {
    return $this->tableName;
  }

  public function getAdapter() {
    return Connections::getConnection($this->connectionName);
  }

  /**
   * @return Select
   */
  public function select() {
    $class = str_replace('Table', 'Select', get_class($this));
    try {
      if (class_exists($class)) {
        return new $class($this);
      }
    } catch(\Exception $e) {}
    return new Select($this);
  }

  public function save(array $data) {
    $data = array_intersect_key($data, array_flip($this->columns));
    return $this->getAdapter()->save($this->tableName, $data);
  }

  public function delete(array $data) {
    $data = array_intersect_key($data, array_flip($this->columns));
    if (!empty($this->primaryName) && !empty($data[$this->primaryName])) {
      $data = [
        $this->primaryName => $data[$this->primaryName]
      ];
    }
    return $this->getAdapter()->delete($this->tableName, $data);
  }

  /**
   * @return array|Relations\Relation[]
   */
  public function relations() {
    return [];
  }

  /**
   * @param $relationName
   * @return null|Relations\Relation
   */
  public function getRelation($relationName) {
    $relations = $this->relations();
    if (array_key_exists($relationName, $relations)) {
      return $relations[$relationName];
    }
    return null;
  }

  /**
   * @return Relations\HasOne
   */
  public function hasOne() {
    return new Relations\HasOne();
  }

  /**
   * @return Relations\HasMany
   */
  public function hasMany() {
    return new Relations\HasMany();
  }

  /**
   * @return Relations\ManyToMany
   */
  public function manyToMany() {
    return new Relations\ManyToMany();
  }

  public function attach(Relations\ManyToMany $relation, Model $nativeModel, Model $relatedModel) {
    $table = $relation->getViaTable();
    $data = $this->prepareManyToManyData($relation, $nativeModel, $relatedModel);
    if (2 != count($data) || array_search(null, $data)) {
      throw new \LogicException(
        sprintf('%s: save both models first', get_class($this))
      );
    }
    return $this->getAdapter()->save($table, $data);
  }

  public function detach(Relations\ManyToMany $relation, Model $nativeModel, Model $relatedModel = null) {
    $table = $relation->getViaTable();
    $data = $this->prepareManyToManyData($relation, $nativeModel, $relatedModel);
    return $this->getAdapter()->delete($table, $data);
  }

  protected function prepareManyToManyData(Relations\ManyToMany $relation, Model $nativeModel, Model $relatedModel = null) {
    if ('many-to-many' != $relation->getType()) {
      throw new \LogicException(
        sprintf('Invalid relation type for %s', get_class($this))
      );
    }
    $table = $relation->getViaTable();
    $nativeModelField = str_replace($table . '.', '', $relation->getMyFieldInVia());
    $nativeMethod = StringsHelper::getCamelCase('get_' . $nativeModelField);
    $data = [
      $nativeModelField => $nativeModel->$nativeMethod(),
    ];
    if (null !== $relatedModel) {
      $relatedModelField = str_replace($table . '.', '', $relation->getRelatedTableFieldInVia());
      $relatedMethod = StringsHelper::getCamelCase('get_' . $relatedModelField);
      $data[$relatedModelField] = $relatedModel->$relatedMethod();
    }
    return $data;
  }

}