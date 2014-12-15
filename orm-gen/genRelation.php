<?php

  require_once '../Jam/AutoLoader.php';

  $autoLoader = new Jam\AutoLoader();
  $errorHandler = new Jam\ErrorHandler(true);

  Jam\Db\Connections::addConnectionData(
    'mysql:host=127.0.0.1;dbname=orm;charset=utf8',
    'root',
    '3364752'
  );

  $adapter = Jam\Db\Connections::getConnection();

  $tables = [];
  $tableTpl = [
    'namespace' => '',
    'class' => '',
    'tableName' => '',
    'primaryName' => [],
    'columns' => [],
  ];
  $intermediateTables = [];

  $relationTableNames = $adapter->fetchColumn('SHOW TABLES');

  foreach ($relationTableNames as $relationTableName) {
    $relationTable = $tableTpl;
    $relationTable['tableName'] = $relationTableName;
    $relationTable['namespace'] = str_replace(
      ' ', '\\', mb_convert_case(
        str_replace('_', ' ', $relationTableName),
        MB_CASE_TITLE
      )
    );
    $relationTable['class'] = $relationTable['namespace'] . '\\Table';
    $tableColumns = $adapter->describe($relationTableName);
    foreach ($tableColumns as $column) {
      $relationTable['columns'][$column['Field']] = $column['Field'];
      if (in_array($column['Key'], ['PRI'])) {
        $relationTable['primaryName'][] = $column['Field'];
      }
    }

    if (2 == count($relationTable['primaryName']) && $relationTable['primaryName'] === array_values($relationTable['columns'])) {
      $intermediateTables[] = $relationTableName;
    } elseif (1 == count($relationTable['primaryName'])) {
      $relationTable['primaryName'] = $relationTable['primaryName'][0];
    }
    $tables[$relationTableName] = $relationTable;
  }

  foreach ($intermediateTables as $relationTableName) {
    $manyToMany = [];
    $relationTable = $tables[$relationTableName];
    foreach ($relationTable['primaryName'] as $primary) {
      foreach ($tables as $tblName => $tblInfo) {

        $class = str_replace(
          ' ', '\\', mb_convert_case(
            str_replace('_', ' ', $tblName),
            MB_CASE_TITLE
          )
        ) . '\\Table' ;
        $relationTable['class'] = $relationTable['namespace'] . '\\Table';

        if ($primary == $tblInfo['primaryName']) {
          $manyToMany[] = [
            'class' => $class,
            'tableName' => $tblInfo['tableName'],
            'primaryName' => $tblInfo['primaryName']
          ];
        }
      }
    }
    $table1 = $manyToMany[0];
    $table2 = $manyToMany[1];

    $tableTable = '$' . $table2['tableName'] . 'Table';
    $relationCode = [];
    $relationCode[] = '    ' . $tableTable . ' = \\' . $table2['class'] . '::instance();';
    $relationCode[] = '    $' . $table2['tableName'] . ' = $this->manyToMany();';
    $relationCode[] = '    $' . $table2['tableName'] . '->setRelatedTable(' . $tableTable . ')';
    $relationCode[] = '      ->setRelatedTableField(' . $tableTable . '->' . camelize($table2['primaryName']) . ')';
    $relationCode[] = '      ->setViaTable(\'' . $relationTableName . '\')';
    $relationCode[] = '      ->setRelatedTableFieldInVia(' . $tableTable . '->' . camelize($table2['primaryName']) . ')';
    $relationCode[] = '      ->setMyFieldInVia($this->' . camelize($table1['primaryName']) . ')';
    $relationCode[] = '      ->setMyField($this->' . camelize($table1['primaryName']) . ')';
    $relationCode[] = '    ;';

    $tables[$table1['tableName']]['relations'][$table2['tableName']] = [
      'attach-detach' => 1,
      'class' => '\\' . str_replace('Table', 'Model', $table2['class']) . '[]',
      'code' => $relationCode
    ];


    $tableTable = '$' . $table1['tableName'] . 'Table';
    $relationCode = [];
    $relationCode[] = '    ' . $tableTable . ' = \\' . $table1['class'] . '::instance();';
    $relationCode[] = '    $' . $table1['tableName'] . ' = $this->manyToMany();';
    $relationCode[] = '    $' . $table1['tableName'] . '->setRelatedTable(' . $tableTable . ')';
    $relationCode[] = '      ->setRelatedTableField(' . $tableTable . '->' . camelize($table1['primaryName']) . ')';
    $relationCode[] = '      ->setViaTable(\'' . $relationTableName . '\')';
    $relationCode[] = '      ->setRelatedTableFieldInVia(' . $tableTable . '->' . camelize($table1['primaryName']) . ')';
    $relationCode[] = '      ->setMyFieldInVia($this->' . camelize($table2['primaryName']) . ')';
    $relationCode[] = '      ->setMyField($this->' . camelize($table2['primaryName']) . ')';
    $relationCode[] = '    ;';

    $tables[$table2['tableName']]['relations'][$table1['tableName']] = [
      'attach-detach' => 1,
      'class' => '\\' . str_replace('Table', 'Model', $table1['class']) . '[]',
      'code' => $relationCode
    ];
    unset($tables[$relationTableName]);
  }

  foreach ($tables as $relationTableName => $tableInfo) {
    $tmpTables = $tables;
    unset($tmpTables[$relationTableName]);
    foreach ($tmpTables as $tmpName => $tmpInfo) {
      if (in_array($tableInfo['primaryName'], $tmpInfo['columns'])) {
        if (!isset($tableInfo['relations'])) {
          $tableInfo['relations'] = [];
        }

        $tableTable = '$' . $tmpInfo['tableName'] . 'Table';
        $relationCode = [];
        $relationCode[] = '    ' . $tableTable . ' = \\' . $tmpInfo['class'] . '::instance();';
        $relationCode[] = '    $' . $tmpInfo['tableName'] . ' = $this->hasMany();';
        $relationCode[] = '    $' . $tmpInfo['tableName'] . '->setRelatedTable(' . $tableTable . ')';
        $relationCode[] = '      ->setRelatedTableField(' . $tableTable . '->' . camelize($tableInfo['primaryName']) . ')';
        $relationCode[] = '      ->setMyField($this->' . camelize($tableInfo['primaryName']) . ')';
        $relationCode[] = '    ;';

        $tables[$tableInfo['tableName']]['relations'][$tmpInfo['tableName']] = [
          'class' => '\\' . str_replace('Table', 'Model', $tmpInfo['class']) . '[]',
          'code' => $relationCode
        ];

        if (!isset($tables[$tmpInfo['tableName']]['relations'])) {
          $tables[$tmpInfo['tableName']]['relations'] = [];
        }

        $tableTable = '$' . $tableInfo['tableName'] . 'Table';
        $singular = getSingular($tableInfo['tableName']);

        $relationCode = [];
        $relationCode[] = '    ' . $tableTable . ' = \\' . $tableInfo['class'] . '::instance();';
        $relationCode[] = '    $' . $singular . ' = $this->hasOne();';
        $relationCode[] = '    $' . $singular . '->setRelatedTable(' . $tableTable . ')';
        $relationCode[] = '      ->setRelatedTableField(' . $tableTable . '->' . camelize($tableInfo['primaryName']) . ')';
        $relationCode[] = '      ->setMyField($this->' . camelize($tableInfo['primaryName']) . ')';
        $relationCode[] = '    ;';

        $tables[$tmpInfo['tableName']]['relations'][$singular] = [
          'class' => '\\' . str_replace('Table', 'Model', $tableInfo['class']),
          'code' => $relationCode
        ];
      }
    }
  }

  return $tables;