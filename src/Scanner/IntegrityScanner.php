<?php
namespace SecurityWP\Scanner;

use SecurityWP\Util\Logger;
use SecurityWP\Util\Notifier;

final class IntegrityScanner {
  public const CRON_HOOK = 'securitywp_integrity_scan';
  private const BASELINE_KEY = 'securitywp_integrity_baseline';

  public function hooks(): void {
    add_action(self::CRON_HOOK, [$this, 'run_scheduled']);

    // Ensure schedule exists.
    add_action('init', function () {
      if (!wp_next_scheduled(self::CRON_HOOK)) {
        wp_schedule_event(time() + 300, 'daily', self::CRON_HOOK);
      }
    });

    // AJAX tools (for UI progress).
    add_action('wp_ajax_securitywp_baseline_ajax', [$this, 'ajax_baseline']);
    add_action('wp_ajax_securitywp_scan_ajax', [$this, 'ajax_scan']);
  }

  public function ajax_baseline(): void {
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Forbidden', 'securitywp')], 403);
    }
    check_ajax_referer('securitywp_admin');

    $t0 = microtime(true);
    $baseline = $this->build_snapshot();
    update_option(self::BASELINE_KEY, $baseline, false);

    Logger::info('Integrity baseline generated', ['files' => count($baseline['files'] ?? [])]);

    $secs = microtime(true) - $t0;
    wp_send_json_success([
      'summary' => sprintf(__('Files hashed: %d', 'securitywp'), count($baseline['files'] ?? [])),
      'seconds' => $secs,
    ]);
  }

  public function ajax_scan(): void {
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Forbidden', 'securitywp')], 403);
    }
    check_ajax_referer('securitywp_admin');

    $t0 = microtime(true);
    $result = $this->scan_and_alert('manual');
    $secs = microtime(true) - $t0;

    $summary = sprintf(
      __('Changed: %d, Missing: %d, New: %d', 'securitywp'),
      (int)($result['changed'] ?? 0),
      (int)($result['missing'] ?? 0),
      (int)($result['new'] ?? 0)
    );

    wp_send_json_success([
      'summary' => $summary,
      'seconds' => $secs,
    ]);
  }

  public function run_scheduled(): void {
    $this->scan_and_alert('scheduled');
  }

  /**
   * @return array{changed:int,missing:int,new:int}
   */
  private function scan_and_alert(string $mode): array {
    $s = get_option('securitywp_settings', []);
    if (empty($s['integrity_scanner'])) {
      return ['changed' => 0, 'missing' => 0, 'new' => 0];
    }

    $baseline = get_option(self::BASELINE_KEY, null);
    if (empty($baseline) || !is_array($baseline)) {
      // No baseline yet; create one silently on first run.
      $baseline = $this->build_snapshot();
      update_option(self::BASELINE_KEY, $baseline, false);
      Logger::info('Integrity baseline auto-created', ['mode' => $mode]);
      return ['changed' => 0, 'missing' => 0, 'new' => 0];
    }

    $current = $this->build_snapshot();

    $diff = $this->diff($baseline, $current);
    $counts = [
      'changed' => count($diff['changed']),
      'missing' => count($diff['missing']),
      'new' => count($diff['new']),
    ];

    if ($counts['changed'] === 0 && $counts['missing'] === 0 && $counts['new'] === 0) {
      return $counts;
    }

    Logger::warning('Integrity changes detected', [
      'mode' => $mode,
      'changed' => $counts['changed'],
      'missing' => $counts['missing'],
      'new' => $counts['new'],
    ]);

    $msg = sprintf(__("SecurityWP detected file integrity changes (%s).\n\n", 'securitywp'), $mode);
    $msg .= sprintf(__("Changed: %d\nMissing: %d\nNew: %d\n\n", 'securitywp'), $counts['changed'], $counts['missing'], $counts['new']);

    $limit = 30;
    if (!empty($diff['changed'])) {
      $msg .= sprintf(__("CHANGED (first %d):\n", 'securitywp'), $limit) . implode("\n", array_slice($diff['changed'], 0, $limit)) . "\n\n";
    }
    if (!empty($diff['missing'])) {
      $msg .= sprintf(__("MISSING (first %d):\n", 'securitywp'), $limit) . implode("\n", array_slice($diff['missing'], 0, $limit)) . "\n\n";
    }
    if (!empty($diff['new'])) {
      $msg .= sprintf(__("NEW (first %d):\n", 'securitywp'), $limit) . implode("\n", array_slice($diff['new'], 0, $limit)) . "\n\n";
    }

    Notifier::notify_admin(__('Integrity changes detected', 'securitywp'), $msg);

    // Update baseline automatically if configured.
    if (!empty($s['integrity_auto_update_baseline'])) {
      update_option(self::BASELINE_KEY, $current, false);
      Logger::info('Integrity baseline updated automatically', ['mode' => $mode]);
    }

    return $counts;
  }

  private function build_snapshot(): array {
    $files = [];

    // Active plugins directory.
    $pluginsDir = WP_PLUGIN_DIR;
    $this->hash_dir($pluginsDir, $files, $pluginsDir);

    // Themes directory.
    $themesDir = get_theme_root();
    if (is_string($themesDir) && !empty($themesDir)) {
      $this->hash_dir($themesDir, $files, $themesDir);
    }

    // A small subset of core files (fast + useful).
    $coreFiles = [
      ABSPATH . 'wp-config.php',
      ABSPATH . 'wp-settings.php',
      ABSPATH . 'wp-includes/version.php',
    ];
    foreach ($coreFiles as $path) {
      if (is_file($path) && is_readable($path)) {
        $rel = $this->rel($path);
        $files[$rel] = hash_file('sha256', $path);
      }
    }

    ksort($files);

    return [
      'generated_at' => time(),
      'wp_version' => get_bloginfo('version'),
      'files' => $files,
    ];
  }

  private function hash_dir(string $dir, array &$files, string $base): void {
    if (!is_dir($dir)) {
      return;
    }

    $it = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
    );

    foreach ($it as $file) {
      /** @var \SplFileInfo $file */
      if (!$file->isFile()) {
        continue;
      }
      $path = $file->getPathname();
      if (!is_readable($path)) {
        continue;
      }
      // Skip big files.
      if ($file->getSize() > 5 * 1024 * 1024) {
        continue;
      }
      $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
      if (!in_array($ext, ['php', 'js', 'css', 'json', 'txt', 'md', 'html', 'htm', 'svg'], true)) {
        continue;
      }
      $rel = ltrim(str_replace($base, '', $path), '/');
      $files[$base . '/' . $rel] = hash_file('sha256', $path);
    }
  }

  private function diff(array $baseline, array $current): array {
    $b = $baseline['files'] ?? [];
    $c = $current['files'] ?? [];

    $changed = [];
    $missing = [];
    $new = [];

    foreach ($b as $path => $hash) {
      if (!isset($c[$path])) {
        $missing[] = $path;
      } elseif ($c[$path] !== $hash) {
        $changed[] = $path;
      }
    }

    foreach ($c as $path => $_hash) {
      if (!isset($b[$path])) {
        $new[] = $path;
      }
    }

    sort($changed);
    sort($missing);
    sort($new);

    return compact('changed', 'missing', 'new');
  }

  private function rel(string $path): string {
    $p = wp_normalize_path($path);
    $a = wp_normalize_path(ABSPATH);
    if (str_starts_with($p, $a)) {
      return 'ABSPATH/' . ltrim(substr($p, strlen($a)), '/');
    }
    return $p;
  }
}
