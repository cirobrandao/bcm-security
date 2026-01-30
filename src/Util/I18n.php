<?php
namespace SecurityWP\Util;

final class I18n {
  private const OPT = 'securitywp_settings';

  public static function load(): void {
    load_plugin_textdomain('securitywp', false, dirname(plugin_basename(SECURITYWP_PLUGIN_FILE)) . '/languages');

    $settings = get_option(self::OPT, []);
    $lang = is_array($settings) ? ($settings['ui_language'] ?? 'default') : 'default';
    $lang = is_string($lang) ? $lang : 'default';

    if ($lang === 'pt_BR') {
      $mofile = SECURITYWP_PLUGIN_DIR . 'languages/securitywp-pt_BR.mo';
      if (file_exists($mofile)) {
        unload_textdomain('securitywp');
        load_textdomain('securitywp', $mofile);
      }
    }
  }
}
