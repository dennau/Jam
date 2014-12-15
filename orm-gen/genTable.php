#!/usr/bin/php -q
<?php

  if (!defined('PHP_SAPI')) {
    throw new Exception("php sapi undefined");
  }

  if (PHP_SAPI !== 'cli') {
    throw new Exception("run from console only");
  }

  if (!empty($_SERVER['argv']) and count($_SERVER['argv'])>1) {
    $get_args = array_splice($_SERVER['argv'], 1);
    $get_str = implode('&', $get_args);
    @parse_str($get_str, $_GET);
  }

  include_once 'functions.php';

  require_once '../Jam/AutoLoader.php';

  $autoLoader = new Jam\AutoLoader();
  $errorHandler = new Jam\ErrorHandler(true);

  Jam\Db\Connections::addConnectionData(
    'mysql:host=127.0.0.1;dbname=orm;charset=utf8',
    'root',
    'password'
  );

  $adapter = Jam\Db\Connections::getConnection();

  $table = [
    'namespace' => '',
    'tableName' => '',
    'primaryName' => [],
    'columns' => [],
    'columnsArray' => '',
    'methods' => [],
    'properties' => [],
    'fields' => [],
    'fieldsArray' => [],
    'modelMethods' => [],
    'selectMethods' => [],
  ];

  if (!empty($_GET['table'])) {
    $tableName = $_GET['table'];
  } else {
    die('Empty required parameter table');
  }

  $table['tableName'] = $tableName;
  $table['namespace'] = str_replace(
    ' ', '\\', mb_convert_case(
      str_replace('_', ' ', $tableName),
      MB_CASE_TITLE
    )
  );
  $tableColumns = $adapter->describe($tableName);
  foreach ($tableColumns as $column) {
    $table['columns'][$column['Field']] = $column['Field'];
    $table['fields'][] = "'" . $column['Field'] . "' => null";
    $table['methods'][] = ' * @method $this ' . camelize($column['Field']) . '()';
    $table['properties'][] = ' * @property $' . camelize($column['Field']);
    if (in_array($column['Key'], ['PRI'])) {
      $table['primaryName'][] = $column['Field'];
      $table['modelMethods'][] = ' * @method ' . camelize('get_' . $column['Field']) . '()';
    } else {
      $table['modelMethods'][] = ' * @method ' . camelize('get_' . $column['Field']) . '()';
      $table['modelMethods'][] = ' * @method $this ' . camelize('set_' . $column['Field']) . '($' . $column['Field'] . ')';
    }
  }

  $table['columnsArray'] = "[\n    '" . implode("', '", $table['columns']) . "'\n  ]";
  $table['fieldsArray'] = "[\n    " . implode(",\n    ", $table['fields']) . "\n  ]";
  $table['methods'] = implode("\n", $table['methods']);
  $table['properties'] = implode("\n", $table['properties']);

  if (2 == count($table['primaryName']) && $table['primaryName'] === array_values($table['columns'])) {
    $intermediateTables[] = $tableName;
  } elseif (1 == count($table['primaryName'])) {
    $table['primaryName'] = $table['primaryName'][0];
  }

  $relations = include_once 'genRelation.php';

  $relationsCode = [];
  if (array_key_exists($tableName, $relations) && !empty($relations[$tableName]['relations'])) {
    $tableRelations = $relations[$tableName]['relations'];
    $relationsCode[] = 'public function relations() {';
    $return = ['    return ['];
    foreach ($tableRelations as $name => $info) {
      $table['modelMethods'][] = ' *';
      $table['modelMethods'][] = ' * @method ' . $info['class'] . ' ' . camelize('get_' . $name) . '()';
      $table['selectMethods'][] = ' *';
      $table['selectMethods'][] = ' * @method $this ' . camelize('with_' . $name) . '()';
      if (!empty($info['attach-detach'])) {
        $table['modelMethods'][] = ' * @method ' . camelize('attach_' . $name) . '()';
        $table['modelMethods'][] = ' * @method ' . camelize('detach_' . $name) . '()';
        $table['selectMethods'][] = ' * @method $this ' . camelize('with_via_' . $name) . '($whereOperator = null, $relatedTableFieldInViaValue = null)';
      }
      $relationsCode[] = implode("\n", $info['code']);
      $return[] = '      \'' . $name . '\' => $' . $name . ',';
    }
    $return[] = '    ];';
    $relationsCode[] = implode("\n", $return);
    $relationsCode[] = '  }';
  } else {
    $relationsCode[] = 'public function relations() {';
    $relationsCode[] = '    return [];';
    $relationsCode[] = '  }';
  }

  $table['modelMethods'] = implode("\n", $table['modelMethods']);
  $table['selectMethods'] = implode("\n", $table['selectMethods']);
  $table['relations'] = implode("\n", $relationsCode);

  $tableTpl = file_get_contents('table.tpl');
  $modelTpl = file_get_contents('model.tpl');
  $selectTpl = file_get_contents('select.tpl');

  /** @var string $dir */
  $dir = 'generated' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $table['namespace']);
  if (!is_dir($dir)) {
    mkdir($dir);
  }
  $tableCode = template($tableTpl, $table);
  file_put_contents($dir . DIRECTORY_SEPARATOR . 'Table.php', $tableCode);
  $modelCode = template($modelTpl, $table);
  file_put_contents($dir . DIRECTORY_SEPARATOR . 'Model.php', $modelCode);
  $selectCode = template($selectTpl, $table);
  file_put_contents($dir . DIRECTORY_SEPARATOR . 'Select.php', $selectCode);