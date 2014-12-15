<?php

namespace Authors;

/**
 * Class Model
 * @package Authors
 *
 * @method Table table()
 * @method getAuthorId()
 * @method getName()
 * @method $this setName($name)
 *
 * @method \Articles\Model[] getArticles()
 */

class Model extends \Jam\Db\Model {

  protected $primaryName = 'author_id';
  protected $fields = [
    'author_id' => null,
    'name' => null
  ];

  public function save() {
    parent::save();
  }

  public function delete() {
    parent::delete();
  }

}