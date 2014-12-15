<?php

namespace {{namespace}};

/**
 * Class Model
 * @package {{namespace}}
 *
 * @method Table table()
{{modelMethods}}
 */

class Model extends \Jam\Db\Model {

  protected $primaryName = '{{primaryName}}';
  protected $fields = {{fieldsArray}};

  public function save() {
    parent::save();
  }

  public function delete() {
    parent::delete();
  }

}