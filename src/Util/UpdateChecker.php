<?php
namespace SecurityWP\Util;

final class UpdateChecker {
  /**
   * Configure update checks against GitHub Releases.
   *
   * @param string $pluginFile Full path to main plugin file.
   * @param string $repoSlug   owner/repo
   * @param string $currentVersion
   */
  public static function boot(string $pluginFile, string $repoSlug, string $currentVersion): void {
    add_filter('pre_set_site_transient_update_plugins', function ($transient) use ($pluginFile, $repoSlug, $currentVersion) {
      if (!is_object($transient)) {
        return $transient;
      }

      $pluginBasename = plugin_basename($pluginFile);

      // Only check occasionally.
      $cacheKey = 'securitywp_update_' . md5($repoSlug);
      $cached = get_transient($cacheKey);
      if (is_array($cached)) {
        if (!empty($cached['update'])) {
          $transient->response[$pluginBasename] = (object)$cached['update'];
        }
        return $transient;
      }

      $apiUrl = 'https://api.github.com/repos/' . $repoSlug . '/releases/latest';
      $resp = wp_remote_get($apiUrl, [
        'timeout' => 10,
        'headers' => [
          'Accept' => 'application/vnd.github+json',
          'User-Agent' => 'SecurityWP/' . $currentVersion,
        ],
      ]);

      if (is_wp_error($resp)) {
        set_transient($cacheKey, ['update' => null], 6 * HOUR_IN_SECONDS);
        return $transient;
      }

      $code = (int) wp_remote_retrieve_response_code($resp);
      $body = (string) wp_remote_retrieve_body($resp);
      if ($code < 200 || $code >= 300) {
        set_transient($cacheKey, ['update' => null], 6 * HOUR_IN_SECONDS);
        return $transient;
      }

      $json = json_decode($body, true);
      if (!is_array($json)) {
        set_transient($cacheKey, ['update' => null], 6 * HOUR_IN_SECONDS);
        return $transient;
      }

      $tag = isset($json['tag_name']) ? (string)$json['tag_name'] : '';
      $latest = ltrim($tag, "vV");
      if (!$latest || version_compare($latest, $currentVersion, '<=')) {
        set_transient($cacheKey, ['update' => null], 6 * HOUR_IN_SECONDS);
        return $transient;
      }

      $zip = isset($json['zipball_url']) ? (string)$json['zipball_url'] : '';
      // WP expects a public zip URL. For private repos, this won't work without auth.

      $update = [
        'slug' => dirname($pluginBasename),
        'plugin' => $pluginBasename,
        'new_version' => $latest,
        'url' => $json['html_url'] ?? '',
        'package' => $zip,
      ];

      $transient->response[$pluginBasename] = (object)$update;
      set_transient($cacheKey, ['update' => $update], 6 * HOUR_IN_SECONDS);

      return $transient;
    });
  }
}
