<?php

  require_once 'Jam/AutoLoader.php';

  $autoLoader = new Jam\AutoLoader();
  $errorHandler = new Jam\ErrorHandler(true);
  $autoLoader->addDirectory('app');

  Jam\Db\Connections::addConnectionData(
    'mysql:host=127.0.0.1;dbname=orm;charset=utf8',
    'root',
    '3364752'
  );

  print '<pre>';



  $articlesTable = Articles\Table::instance();
  $articlesSelect = $articlesTable->select();

  $articles = $articlesSelect->withViaCategories('is', 5)
      ->find();
  print $articlesSelect;
  print '<br>';
  print_r($articles);


