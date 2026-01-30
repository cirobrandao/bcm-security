<?php
namespace SecurityWP\Util;

final class Logger {
  public static function info(string $message, array $context = []): void {
    self::log('INFO', $message, $context);
  }

  public static function warning(string $message, array $context = []): void {
    self::log('WARN', $message, $context);
  }

  public static function error(string $message, array $context = []): void {
    self::log('ERROR', $message, $context);
  }

  private static function log(string $level, string $message, array $context): void {
    $ctx = '';
    if (!empty($context)) {
      $ctx = ' ' . wp_json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    error_log(sprintf('[SecurityWP][%s] %s%s', $level, $message, $ctx));

    $optKey = 'securitywp_log';
    $log = get_option($optKey, []);
    if (!is_array($log)) {
      $log = [];
    }
    array_unshift($log, [
      't' => time(),
      'level' => $level,
      'msg' => $message,
      'ctx' => $context,
    ]);
    $log = array_slice($log, 0, 200);
    update_option($optKey, $log, false);
  }
}
