<?php

namespace Categories;

/**
 * Class Model
 * @package Categories
 *
 * @method Table table()
 * @method getCategoryId()
 * @method getName()
 * @method $this setName($name)
 *
 * @method \Articles\Model[] getArticles()
 * @method attachArticles()
 * @method detachArticles()
 */

class Model extends \Jam\Db\Model {

  protected $primaryName = 'category_id';
  protected $fields = [
    'category_id' => null,
    'name' => null
  ];

  public function save() {
    parent::save();
  }

  public function delete() {
    parent::delete();
  }

}