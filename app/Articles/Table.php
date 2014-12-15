<?php

namespace Articles;

/**
 * Class Table
 * @package Articles
 *
 * @method static Table instance()
 * @method Select select()
 *
 * @property $articleId
 * @property $title
 * @property $authorId
 */

class Table extends \Jam\Db\Table {

  protected $tableName = 'articles';
  protected $primaryName = 'article_id';
  protected $columns = [
    'article_id', 'title', 'author_id'
  ];
  protected $connectionName = 'default';

  public function relations() {
    $categoriesTable = \Categories\Table::instance();
    $categories = $this->manyToMany();
    $categories->setRelatedTable($categoriesTable)
      ->setRelatedTableField($categoriesTable->categoryId)
      ->setViaTable('articles_categories')
      ->setRelatedTableFieldInVia($categoriesTable->categoryId)
      ->setMyFieldInVia($this->articleId)
      ->setMyField($this->articleId)
    ;
    $authorsTable = \Authors\Table::instance();
    $author = $this->hasOne();
    $author->setRelatedTable($authorsTable)
      ->setRelatedTableField($authorsTable->authorId)
      ->setMyField($this->authorId)
    ;
    return [
      'categories' => $categories,
      'author' => $author,
    ];
  }

}