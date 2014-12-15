<?php

  function camelize($word) {
    $word = str_replace('_', ' ', $word);
    $word = mb_convert_case($word, MB_CASE_TITLE);
    $word = lcfirst(str_replace(' ', '', $word));
    return $word;
  }

  function getSingular($word) {
    $singular = [
      '!(quiz)zes$!i' => '\1',
      '!(matr)ices$!i' => '\1ix',
      '!(vert|ind)ices$!i' => '\1ex',
      '!^(ox)en!i' => '\1',
      '!(alias|status)es$!i' => '\1',
      '!([octop|vir])i$!i' => '\1us',
      '!(cris|ax|test)es$!i' => '\1is',
      '!(shoe)s$!i' => '\1',
      '!(o)es$!i' => '\1',
      '!(bus)es$!i' => '\1',
      '!([m|l])ice$!i' => '\1ouse',
      '!(x|ch|ss|sh)es$!i' => '\1',
      '!(m)ovies$!i' => '\1ovie',
      '!(s)eries$!i' => '\1eries',
      '!([^aeiouy]|qu)ies$!i' => '\1y',
      '!([lr])ves$!i' => '\1f',
      '!(tive)s$!i' => '\1',
      '!(hive)s$!i' => '\1',
      '!([^f])ves$!i' => '\1fe',
      '!(^analy)ses$!i' => '\1sis',
      '!((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$!i' => '\1\2sis',
      '!([ti])a$!i' => '\1um',
      '!(n)ews$!i' => '\1ews',
      '!s$!i' => ''
    ];
    $uncountableException = ['equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep'];
    $irregularException = [
      'person' => 'people',
      'man' => 'men',
      'child' => 'children',
      'sex' => 'sexes',
      'move' => 'moves',
      'woman' => 'women'
    ];
    $lcWords = strtolower($word);
    foreach ($uncountableException as $lngException) {
      if(substr($lcWords, (-1*strlen($lngException))) == $lngException) {
        return $word;
      }
    }
    foreach ($irregularException as $pluralException => $singularException) {
      if (preg_match('!(' . $singularException . ')$!i', $word, $match)) {
        return preg_replace(
          '!(' . $singularException . ')$!i',
          substr($match[0], 0, 1) . substr($pluralException,1),
          $word
        );
      }
    }
    foreach ($singular as $rule => $replacement) {
      if (preg_match($rule, $word)) {
        return preg_replace($rule, $replacement, $word);
      }
    }
    return $word;
  }

  function template($tpl, $data) {
    $code = $tpl;
    foreach ($data as $key => $value) {
      if (strpos($code, '{{' . $key . '}}')) {
        $code = str_replace('{{' . $key . '}}' , $value, $code);
      }
    }
    return $code;
  }