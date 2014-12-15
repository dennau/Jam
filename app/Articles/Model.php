<?php

namespace Articles;

/**
 * Class Model
 * @package Articles
 *
 * @method Table table()
 * @method getArticleId()
 * @method getTitle()
 * @method $this setTitle($title)
 * @method getAuthorId()
 * @method $this setAuthorId($author_id)
 *
 * @method \Categories\Model[] getCategories()
 * @method attachCategories()
 * @method detachCategories()
 *
 * @method \Authors\Model getAuthor()
 */

class Model extends \Jam\Db\Model {

  protected $primaryName = 'article_id';
  protected $fields = [
    'article_id' => null,
    'title' => null,
    'author_id' => null
  ];

  public function save() {
    parent::save();
  }

  public function delete() {
    parent::delete();
  }

}