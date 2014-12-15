<?php

namespace Jam;


class ErrorHandler {

  protected $debug = false;

  public function __construct($debug = false) {
    $this->debug = boolval($debug);
    error_reporting(0);
    register_shutdown_function([$this, 'fatalHandler']);
    set_error_handler([$this, 'errorHandler']);
    set_exception_handler([$this, 'exceptionHandler']);
  }

  public function errorHandler($errorNo, $errorMessage, $errorFile, $errorLine, $errorTrace = null) {
    if (false === $this->debug) {
      return null;
    }

    $errorReport = "\n" . str_repeat('=-=', 20) . "\n";
    $errorReport .= 'Error #'.$errorNo.' - '.$errorMessage."\n\n";

    if (is_file($errorFile)) {
      $errorReport .= 'Error file: '.$errorFile."\n";
      $errorReport .= 'Error line: '.$errorLine."\n";
      $errorReport .= "-----------------------------------------------\n";
      $lines = @file_get_contents($errorFile);
      $lines = explode("\n", $lines);
      if (isset($lines[$errorLine - 3])) $errorReport .= '['.($errorLine - 2).']'.$lines[$errorLine - 3]."\n";
      if (isset($lines[$errorLine - 2])) $errorReport .= '['.($errorLine - 1).']'.$lines[$errorLine - 2]."\n";
      if (isset($lines[$errorLine - 1])) $errorReport .= '['.$errorLine.']'.$lines[$errorLine - 1]."\n";
      if (isset($lines[$errorLine])) $errorReport .= '['.($errorLine + 1).']'.$lines[$errorLine]."\n";
      if (isset($lines[$errorLine + 1])) $errorReport .= '['.($errorLine + 2).']'.$lines[$errorLine + 1]."\n";
      $errorReport .= "-----------------------------------------------\n";
    }

    $errorReport .= "\nBacktrace:\n";

    $errorTrace = !empty($errorTrace[0]) ? $errorTrace : debug_backtrace();

    foreach ($errorTrace as $backtraceIndex => $backtraceItem) {
      if (!empty($backtraceItem['file']) and !empty($backtraceItem['line'])) {
        $errorReport .= "  #".$backtraceIndex." ".$backtraceItem['file']." [".$backtraceItem['line']."]\n";
      } else {
        $errorReport .= "  #".$backtraceIndex." ----\n";
      }
    }
    $errorReport .= str_repeat('=-=', 20) . "\n";
    print '<pre>';
    print_r($errorReport);
    print '</pre>';
  }

  public function fatalHandler() {
    if (!is_null($error = error_get_last())) {
      $this->errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
    }
  }

  public function exceptionHandler(\Exception $exception) {
    $this->errorHandler(
      $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTrace()
    );
  }

}