<?php

namespace Authors;

/**
 * Class Table
 * @package Authors
 *
 * @method static Table instance()
 * @method Select select()
 *
 * @property $authorId
 * @property $name
 */

class Table extends \Jam\Db\Table {

  protected $tableName = 'authors';
  protected $primaryName = 'author_id';
  protected $columns = [
    'author_id', 'name'
  ];
  protected $connectionName = 'default';

  public function relations() {
    $articlesTable = \Articles\Table::instance();
    $articles = $this->hasMany();
    $articles->setRelatedTable($articlesTable)
      ->setRelatedTableField($articlesTable->authorId)
      ->setMyField($this->authorId)
    ;
    return [
      'articles' => $articles,
    ];
  }

}