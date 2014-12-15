<?php

namespace Categories;

/**
 * Class Table
 * @package Categories
 *
 * @method static Table instance()
 * @method Select select()
 *
 * @property $categoryId
 * @property $name
 */

class Table extends \Jam\Db\Table {

  protected $tableName = 'categories';
  protected $primaryName = 'category_id';
  protected $columns = [
    'category_id', 'name'
  ];
  protected $connectionName = 'default';

  public function relations() {
    $articlesTable = \Articles\Table::instance();
    $articles = $this->manyToMany();
    $articles->setRelatedTable($articlesTable)
      ->setRelatedTableField($articlesTable->articleId)
      ->setViaTable('articles_categories')
      ->setRelatedTableFieldInVia($articlesTable->articleId)
      ->setMyFieldInVia($this->categoryId)
      ->setMyField($this->categoryId)
    ;
    return [
      'articles' => $articles,
    ];
  }

}