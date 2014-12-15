<?php

namespace {{namespace}};

/**
 * Class Table
 * @package {{namespace}}
 *
 * @method static Table instance()
 * @method Select select()
 *
{{properties}}
 */

class Table extends \Jam\Db\Table {

  protected $tableName = '{{tableName}}';
  protected $primaryName = '{{primaryName}}';
  protected $columns = {{columnsArray}};
  protected $connectionName = 'default';

  {{relations}}

}