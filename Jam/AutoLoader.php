<?php

  namespace Jam;


  class AutoLoader {

    protected $dirs = [];

    public function __construct() {
      $this->addDirectory(realpath(dirname(__DIR__)));
      spl_autoload_register([$this, 'load']);
    }

    /**
     * @param $directory
     */
    public function addDirectory($directory) {
      $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
      if (!is_dir($directory)) {
        throw new \RuntimeException(
          sprintf('%s: can not find directory %s', get_class($this), $directory)
        );
      }
      if (!is_readable($directory)) {
        throw new \RuntimeException(
          sprintf('%s: can not read directory %s', get_class($this), $directory)
        );
      }
      array_unshift($this->dirs, $directory);
    }

    public function getDirectories() {
      return $this->dirs;
    }

    public function load($className) {
      $classFileName = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
      foreach ($this->dirs as $dir) {
        if (file_exists($dir . $classFileName)) {
          include_once $dir . $classFileName;
          return true;
        }
      }
      throw new \RuntimeException(
        sprintf('%s: can not find class %s', get_class($this), $className)
      );
    }

  }