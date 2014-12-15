<?php

namespace Jam\Db;


class Connections {

  protected static $data = [];

  /** @var Adapter[] */
  protected static $adapters = [];

  public static function addConnectionData($dsn, $user, $password, $options = [], $name = 'default') {
    $driverOptions = [
      \PDO::ATTR_ERRMODE  => \PDO::ERRMODE_EXCEPTION
    ];

    if (!empty($options)) {
      foreach ($options as $key => $value) {
        $driverOptions[$key] = $value;
      }
    }
    static::$data[$name] = [
      'dsn' => $dsn,
      'user' => $user,
      'password' => $password,
      'options' => $driverOptions
    ];
  }

  public static function getConnection($connectionName = 'default') {
    if (empty(static::$data[$connectionName])) {
      throw new \InvalidArgumentException(
        sprintf('%s : Unknown connection name %s', get_called_class(), $connectionName)
      );
    }
    if (empty(static::$adapters[$connectionName])) {
      $pdo = new \PDO(
          static::$data[$connectionName]['dsn'],
          static::$data[$connectionName]['user'],
          static::$data[$connectionName]['password'],
          static::$data[$connectionName]['options']
      );
      static::$adapters[$connectionName] = new Adapter($pdo);
    }
    return static::$adapters[$connectionName];
  }

}