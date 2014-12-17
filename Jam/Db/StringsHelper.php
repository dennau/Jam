<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 12/17/14
 * Time: 4:50 PM
 */

namespace Jam\Db;


class StringsHelper {

  public static function getUnderscore($word) {
    $word = preg_split('!([A-Z]{1}[^A-Z]*)!', $word, -1, PREG_SPLIT_DELIM_CAPTURE^PREG_SPLIT_NO_EMPTY);
    $word = mb_convert_case(implode('_', $word), MB_CASE_LOWER);
    return $word;
  }

  public static function getCamelCase($word) {
    $word = str_replace('_', ' ', $word);
    $word = mb_convert_case($word, MB_CASE_TITLE);
    $word = lcfirst(str_replace(' ', '', $word));
    return $word;
  }

}