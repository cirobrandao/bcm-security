<?php
namespace SecurityWP\Admin;

final class SettingsPage {
  private const OPTION_KEY = 'securitywp_settings';

  private const SEC_LOGIN = 'securitywp_section_login';
  private const SEC_XMLRPC = 'securitywp_section_xmlrpc';
  private const SEC_REST = 'securitywp_section_rest';
  private const SEC_INTEGRITY = 'securitywp_section_integrity';
  private const SEC_ALERTS = 'securitywp_section_alerts';

  public function hooks(): void {
    add_action('admin_init', [$this, 'register_settings']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
  }

  public function enqueue_assets(string $hook): void {
    if (!in_array($hook, ['toplevel_page_securitywp-settings', 'securitywp_page_securitywp-settings'], true)) {
      return;
    }

    wp_enqueue_style('securitywp-admin', plugins_url('assets/admin.css', SECURITYWP_PLUGIN_FILE), [], SECURITYWP_VERSION);
    wp_enqueue_script('securitywp-admin', plugins_url('assets/admin.js', SECURITYWP_PLUGIN_FILE), [], SECURITYWP_VERSION, true);

    wp_localize_script('securitywp-admin', 'SecurityWPAdmin', [
      'nonce' => wp_create_nonce('securitywp_admin'),
      'i18n' => [
        'baseline_running' => __('Generating baseline… this can take a moment.', 'securitywp'),
        'baseline_done' => __('Baseline generated in %s seconds.', 'securitywp'),
        'scan_running' => __('Running scan… this can take a moment.', 'securitywp'),
        'scan_done' => __('Scan finished in %s seconds.', 'securitywp'),
      ],
    ]);
  }

  public function register_settings(): void {
    register_setting('securitywp', self::OPTION_KEY, [
      'type' => 'array',
      'sanitize_callback' => [$this, 'sanitize'],
      'default' => [
        // Login protection
        'login_rate_limit' => 1,
        'login_allowlist_ips' => '',
        'login_max_attempts' => 5,
        'login_window_sec' => 600,
        'login_block_sec' => 900,

        // Login URL customization
        'login_slug' => '',

        // Admin access restriction
        'admin_allowlist_ips' => '',

        'disable_xmlrpc' => 1,

        'rest_hardening' => 1,
        'rest_block_users' => 1,
        'rest_hide_users' => 1,

        'integrity_scanner' => 1,
        'integrity_auto_update_baseline' => 0,

        'email_alerts' => 1,
        'alert_email' => '',
      ],
    ]);

    // LOGIN TAB
    add_settings_section(self::SEC_LOGIN, '', function () {
      echo '<p style="color:#646970">' . esc_html__('Login protection, admin access restriction, and (optional) custom login URL.', 'securitywp') . '</p>';
      echo '<p style="color:#b32d2e">' . esc_html__('Important: the custom login URL requires pretty permalinks (Settings → Permalinks).', 'securitywp') . '</p>';
    }, 'securitywp_login');

    // Protection / rate limit
    $this->add_checkbox('login_rate_limit', __('Enable login rate limiting', 'securitywp'), 'securitywp_login', self::SEC_LOGIN, null, __('Rate limiting', 'securitywp'), __('Slows down brute-force login attacks by blocking repeated failed attempts.', 'securitywp'), [__('Prevents password guessing bots.', 'securitywp'), __('Reduces login endpoint abuse.', 'securitywp')]);

    // Keep IP allowlist textarea but it’s only used when rate limit is enabled.
    $this->add_textarea('login_allowlist_ips', __('Allowlist IPs for rate limit (one per line)', 'securitywp'), 'securitywp_login', self::SEC_LOGIN, __('Allowlist', 'securitywp'), __('If your IP is listed here, the rate limit will not apply to you.', 'securitywp'), [__('Use this for your own office/home IP.', 'securitywp'), __('Do not add broad ranges.', 'securitywp')]);

    // Compact numeric fields: small-text already; keep them.
    $this->add_number('login_max_attempts', __('Max attempts', 'securitywp'), 0, 50, 'securitywp_login', self::SEC_LOGIN, 'login_rate_limit', __('Threshold', 'securitywp'), __('How many failed attempts are allowed before a temporary block.', 'securitywp'));
    $this->add_number('login_window_sec', __('Window (sec)', 'securitywp'), 0, 86400, 'securitywp_login', self::SEC_LOGIN, 'login_rate_limit', __('Window', 'securitywp'), __('Time window used to count failed attempts.', 'securitywp'));
    $this->add_number('login_block_sec', __('Block (sec)', 'securitywp'), 0, 86400, 'securitywp_login', self::SEC_LOGIN, 'login_rate_limit', __('Block duration', 'securitywp'), __('How long the IP is blocked after exceeding the limit.', 'securitywp'));

    // Admin allowlist (wp-admin)
    $this->add_textarea('admin_allowlist_ips', __('Allowlist IPs for wp-admin (one per line)', 'securitywp'), 'securitywp_login', self::SEC_LOGIN, __('Admin access', 'securitywp'), __('Restricts access to /wp-admin for non-allowlisted IPs.', 'securitywp'), [__('Prevents unauthorized admin access.', 'securitywp'), __('Reduces attack surface.', 'securitywp')]);

    // Custom login slug (single slug for both admin + users)
    $this->add_text_with_placeholder(
      'login_slug',
      __('Custom login slug (hides wp-login.php)', 'securitywp'),
      'securitywp_login',
      self::SEC_LOGIN,
      __('Examples: painel, login-seguro, entrar-xyz', 'securitywp')
    );

    // XMLRPC
    add_settings_section(self::SEC_XMLRPC, '', function () {
      echo '<p style="color:#646970">' . esc_html__('Disable legacy XML-RPC to reduce attack surface.', 'securitywp') . '</p>';
    }, 'securitywp_xmlrpc');
    $this->add_checkbox('disable_xmlrpc', __('Disable XML-RPC', 'securitywp'), 'securitywp_xmlrpc', self::SEC_XMLRPC, null, __('XML-RPC', 'securitywp'), __('XML-RPC is a legacy interface frequently abused by bots. Disabling reduces attack surface.', 'securitywp'));

    // REST
    add_settings_section(self::SEC_REST, '', function () {
      echo '<p style="color:#646970">' . esc_html__('Restrict sensitive REST API endpoints for anonymous users.', 'securitywp') . '</p>';
    }, 'securitywp_rest');
    $this->add_checkbox('rest_hardening', __('Enable REST hardening', 'securitywp'), 'securitywp_rest', self::SEC_REST, null, __('REST hardening', 'securitywp'), __('Reduces information leakage and blocks anonymous enumeration endpoints.', 'securitywp'));
    $this->add_checkbox('rest_block_users', __('Block anonymous access to /wp/v2/users', 'securitywp'), 'securitywp_rest', self::SEC_REST, 'rest_hardening', __('User enumeration', 'securitywp'), __('Prevents bots from listing users via the REST API.', 'securitywp'));
    $this->add_checkbox('rest_hide_users', __('Hide /wp/v2/users from endpoint discovery', 'securitywp'), 'securitywp_rest', self::SEC_REST, 'rest_hardening', __('Endpoint discovery', 'securitywp'), __('Removes the users endpoint from REST index discovery for anonymous visitors.', 'securitywp'));

    // Integrity
    add_settings_section(self::SEC_INTEGRITY, '', function () {
      echo '<p style="color:#646970">' . esc_html__('Detect unexpected file changes. Requires a baseline.', 'securitywp') . '</p>';
    }, 'securitywp_integrity');
    $this->add_checkbox('integrity_scanner', __('Enable integrity scanner', 'securitywp'), 'securitywp_integrity', self::SEC_INTEGRITY, null, __('Integrity scanner', 'securitywp'), __('Detects unexpected file changes that may indicate malware or a compromised site.', 'securitywp'));
    $this->add_checkbox('integrity_auto_update_baseline', __('Auto-update baseline after alert (use with caution)', 'securitywp'), 'securitywp_integrity', self::SEC_INTEGRITY, 'integrity_scanner', __('Auto baseline', 'securitywp'), __('If enabled, the baseline is updated automatically after an alert. Use only if you trust your deployment pipeline.', 'securitywp'));

    // Alerts
    add_settings_section(self::SEC_ALERTS, '', function () {
      echo '<p style="color:#646970">' . esc_html__('Email notifications for important events.', 'securitywp') . '</p>';
    }, 'securitywp_alerts');
    $this->add_checkbox('email_alerts', __('Enable email alerts', 'securitywp'), 'securitywp_alerts', self::SEC_ALERTS, null, __('Notifications', 'securitywp'), __('Sends email notifications when important security events happen.', 'securitywp'));
    $this->add_text('alert_email', __('Alert email (optional)', 'securitywp'), 'securitywp_alerts', self::SEC_ALERTS, 'email_alerts', __('Recipient', 'securitywp'), __('Where alerts will be sent. Leave empty to use the site admin email.', 'securitywp'));
  }

    private static function render_field_help(?string $title, ?string $body, array $bullets): void {
    if (!$title && !$body && !$bullets) {
      return;
    }

    echo '<div class="securitywp-field__help">';
    if ($title) {
      echo '<h3>' . esc_html($title) . '</h3>';
    }
    if ($body) {
      echo '<p>' . esc_html($body) . '</p>';
    }
    if ($bullets) {
      echo '<ul>';
      foreach ($bullets as $b) {
        echo '<li>' . esc_html($b) . '</li>';
      }
      echo '</ul>';
    }
    echo '</div>';
  }

private function add_checkbox(string $key, string $label, string $page, string $section, ?string $dependsOnKey = null, ?string $helpTitle = null, ?string $helpBody = null, array $helpBullets = []): void {
    add_settings_field(
      'securitywp_' . $key,
      $label,
      function () use ($key, $dependsOnKey, $helpTitle, $helpBody, $helpBullets) {
        $opts = get_option(self::OPTION_KEY, []);
        $val = !empty($opts[$key]) ? 1 : 0;

        $depAttr = '';
        if ($dependsOnKey) {
          $depAttr = ' data-securitywp-depends-on="' . esc_attr($dependsOnKey) . '"';
        }

        echo '<div class="securitywp-field">';
        echo '<div class="securitywp-field__control">';
        printf(
          '<label><input type="checkbox" name="%s[%s]" value="1" %s%s></label>',
          esc_attr(self::OPTION_KEY),
          esc_attr($key),
          checked(1, $val, false),
          $depAttr
        );
        echo '</div>';
        self::render_field_help($helpTitle, $helpBody, $helpBullets);
        echo '</div>';
      },
      $page,
      $section
    );
  }

  private function add_number(string $key, string $label, int $min, int $max, string $page, string $section, ?string $dependsOnKey = null, ?string $helpTitle = null, ?string $helpBody = null, array $helpBullets = []): void {
    add_settings_field(
      'securitywp_' . $key,
      $label,
      function () use ($key, $min, $max, $dependsOnKey, $helpTitle, $helpBody, $helpBullets) {
        $opts = get_option(self::OPTION_KEY, []);
        $val = isset($opts[$key]) ? (string)$opts[$key] : '';

        $depAttr = '';
        if ($dependsOnKey) {
          $depAttr = ' data-securitywp-depends-on="' . esc_attr($dependsOnKey) . '"';
        }

        echo '<div class="securitywp-field">';
        echo '<div class="securitywp-field__control">';
        printf(
          '<input type="number" name="%s[%s]" value="%s" min="%d" max="%d" class="small-text"%s>',
          esc_attr(self::OPTION_KEY),
          esc_attr($key),
          esc_attr($val),
          $min,
          $max,
          $depAttr
        );
        echo '</div>';
        self::render_field_help($helpTitle, $helpBody, $helpBullets);
        echo '</div>';
      },
      $page,
      $section
    );
  }

  private function add_text(string $key, string $label, string $page, string $section, ?string $dependsOnKey = null, ?string $helpTitle = null, ?string $helpBody = null, array $helpBullets = []): void {
    add_settings_field(
      'securitywp_' . $key,
      $label,
      function () use ($key, $dependsOnKey, $helpTitle, $helpBody, $helpBullets) {
        $opts = get_option(self::OPTION_KEY, []);
        $val = isset($opts[$key]) ? (string)$opts[$key] : '';

        $depAttr = '';
        if ($dependsOnKey) {
          $depAttr = ' data-securitywp-depends-on="' . esc_attr($dependsOnKey) . '"';
        }

        echo '<div class="securitywp-field">';
        echo '<div class="securitywp-field__control">';
        printf(
          '<input type="text" name="%s[%s]" value="%s" class="regular-text"%s>',
          esc_attr(self::OPTION_KEY),
          esc_attr($key),
          esc_attr($val),
          $depAttr
        );
        echo '</div>';
        self::render_field_help($helpTitle, $helpBody, $helpBullets);
        echo '</div>';
      },
      $page,
      $section
    );
  }

  private function add_text_with_placeholder(string $key, string $label, string $page, string $section, string $placeholder): void {
    add_settings_field(
      'securitywp_' . $key,
      $label,
      function () use ($key, $placeholder) {
        $opts = get_option(self::OPTION_KEY, []);
        $val = isset($opts[$key]) ? (string)$opts[$key] : '';

        printf(
          '<input type="text" name="%s[%s]" value="%s" class="regular-text" placeholder="%s">',
          esc_attr(self::OPTION_KEY),
          esc_attr($key),
          esc_attr($val),
          esc_attr($placeholder)
        );

        echo '<p class="description">' . esc_html__('After saving, go to Settings → Permalinks and click Save (once) if the slug does not work immediately.', 'securitywp') . '</p>';
      },
      $page,
      $section
    );
  }

  private function add_textarea(string $key, string $label, string $page, string $section, ?string $helpTitle = null, ?string $helpBody = null, array $helpBullets = []): void {
    add_settings_field(
      'securitywp_' . $key,
      $label,
      function () use ($key, $helpTitle, $helpBody, $helpBullets) {
        $opts = get_option(self::OPTION_KEY, []);
        $val = isset($opts[$key]) ? (string)$opts[$key] : '';

        echo '<div class="securitywp-field">';
        echo '<div class="securitywp-field__control">';
        printf(
          '<textarea name="%s[%s]" rows="4" class="large-text code" placeholder="%s">%s</textarea><p class="description">%s</p>',
          esc_attr(self::OPTION_KEY),
          esc_attr($key),
          esc_attr("203.0.113.10\n2001:db8::1"),
          esc_textarea($val),
          esc_html__('One IP per line. Only exact IPs are supported for now.', 'securitywp')
        );
        echo '</div>';
        self::render_field_help($helpTitle, $helpBody, $helpBullets);
        echo '</div>';
      },
      $page,
      $section
    );
  }

  public function sanitize($input): array {
    $input = is_array($input) ? $input : [];
    $prev = get_option(self::OPTION_KEY, []);
    $out = is_array($prev) ? $prev : [];

    $tab = isset($input['_tab']) ? sanitize_key((string)$input['_tab']) : 'all';
    unset($out['_tab']);
    unset($input['_tab']);

    $set_checkbox = function (string $key) use (&$out, $input) {
      $out[$key] = !empty($input[$key]) ? 1 : 0;
    };

    if ($tab === 'login' || $tab === 'all') {
      $set_checkbox('login_rate_limit');

      if (array_key_exists('login_allowlist_ips', $input)) {
        $out['login_allowlist_ips'] = sanitize_textarea_field((string)$input['login_allowlist_ips']);
      }

      if (!empty($out['login_rate_limit'])) {
        if (array_key_exists('login_max_attempts', $input)) {
          $out['login_max_attempts'] = max(1, (int)$input['login_max_attempts']);
        }
        if (array_key_exists('login_window_sec', $input)) {
          $out['login_window_sec'] = max(60, (int)$input['login_window_sec']);
        }
        if (array_key_exists('login_block_sec', $input)) {
          $out['login_block_sec'] = max(60, (int)$input['login_block_sec']);
        }
      }

      if (array_key_exists('admin_allowlist_ips', $input)) {
        $out['admin_allowlist_ips'] = sanitize_textarea_field((string)$input['admin_allowlist_ips']);
      }

      if (array_key_exists('login_slug', $input)) {
        $out['login_slug'] = !empty($input['login_slug']) ? sanitize_title((string)$input['login_slug']) : '';
      }
    }

    if ($tab === 'xmlrpc' || $tab === 'all') {
      $set_checkbox('disable_xmlrpc');
    }

    if ($tab === 'rest' || $tab === 'all') {
      $set_checkbox('rest_hardening');
      $out['rest_block_users'] = (!empty($out['rest_hardening']) && !empty($input['rest_block_users'])) ? 1 : 0;
      $out['rest_hide_users']  = (!empty($out['rest_hardening']) && !empty($input['rest_hide_users'])) ? 1 : 0;
    }

    if ($tab === 'integrity' || $tab === 'all') {
      $set_checkbox('integrity_scanner');
      $out['integrity_auto_update_baseline'] = (!empty($out['integrity_scanner']) && !empty($input['integrity_auto_update_baseline'])) ? 1 : 0;
    }

    if ($tab === 'alerts' || $tab === 'all') {
      $set_checkbox('email_alerts');
      if (!empty($out['email_alerts']) && array_key_exists('alert_email', $input)) {
        $out['alert_email'] = sanitize_email((string)$input['alert_email']);
      } elseif ($tab === 'alerts') {
        $out['alert_email'] = '';
      }
    }

    $out['hide_wp_version'] = 1;

    return $out;
  }
  private static function render_help_card(string $tab): void {
    echo '<div class="securitywp-card">';

    if ($tab === 'login') {
      echo '<h2>' . esc_html__('What this prevents', 'securitywp') . '</h2>';
      echo '<ul style="list-style:disc; padding-left:18px">';
      echo '<li>' . esc_html__('Brute-force password guessing by limiting repeated login attempts.', 'securitywp') . '</li>';
      echo '<li>' . esc_html__('Unauthorized wp-admin access by restricting admin area to allowlisted IPs.', 'securitywp') . '</li>';
      echo '<li>' . esc_html__('Direct scanning of wp-login.php by optionally using a custom login URL.', 'securitywp') . '</li>';
      echo '</ul>';
    } elseif ($tab === 'xmlrpc') {
      echo '<h2>' . esc_html__('Why disable XML-RPC?', 'securitywp') . '</h2>';
      echo '<p style="color:#646970">' . esc_html__('XML-RPC is a legacy interface often abused for brute force and amplification. Disabling it reduces attack surface.', 'securitywp') . '</p>';
    } elseif ($tab === 'rest') {
      echo '<h2>' . esc_html__('REST hardening', 'securitywp') . '</h2>';
      echo '<p style="color:#646970">' . esc_html__('Prevents anonymous enumeration of users and reduces information leakage via REST endpoints.', 'securitywp') . '</p>';
    } elseif ($tab === 'integrity') {
      echo '<h2>' . esc_html__('Integrity scanner', 'securitywp') . '</h2>';
      echo '<p style="color:#646970">' . esc_html__('Detects unexpected file changes that may indicate malware or compromised deployments. Requires a baseline.', 'securitywp') . '</p>';
    } elseif ($tab === 'alerts') {
      echo '<h2>' . esc_html__('Alerts', 'securitywp') . '</h2>';
      echo '<p style="color:#646970">' . esc_html__('Sends email notifications when important security events happen so you can react quickly.', 'securitywp') . '</p>';
    }

    echo '</div>';
  }

  public static function render_page(): void {
    if (!current_user_can('manage_options')) {
      return;
    }

    $tab = isset($_GET['tab']) ? sanitize_key((string)$_GET['tab']) : 'login';
    $tabs = [
      'login' => __('Login', 'securitywp'),
      'xmlrpc' => __('XML-RPC', 'securitywp'),
      'rest' => __('REST', 'securitywp'),
      'integrity' => __('Integrity', 'securitywp'),
      'alerts' => __('Alerts', 'securitywp'),
      'tools' => __('Tools', 'securitywp'),
      'updates' => __('Updates', 'securitywp'),
    ];
    if (!isset($tabs[$tab])) {
      $tab = 'login';
    }

    echo '<div class="wrap securitywp-wrap">';
    echo '<h1>' . esc_html__('BCM Security Settings', 'securitywp') . '</h1>';

    echo '<nav class="nav-tab-wrapper" style="margin-bottom:12px">';
    foreach ($tabs as $k => $label) {
      $url = admin_url('admin.php?page=securitywp-settings&tab=' . $k);
      $cls = 'nav-tab' . ($k === $tab ? ' nav-tab-active' : '');
      echo '<a class="' . esc_attr($cls) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }
    echo '</nav>';

    echo '<form method="post" action="options.php">';
    settings_fields('securitywp');

    // Track current tab to avoid resetting other tabs on save.
    echo '<input type="hidden" name="securitywp_settings[_tab]" value="' . esc_attr($tab) . '">';

    // Two-column layout for settings tabs
    if ($tab !== 'tools' && $tab !== 'updates') {
      echo '<div class="securitywp-grid">';
      echo '<div class="securitywp-card">';
    } else {
      echo '<div class="securitywp-card">';
    }

    if ($tab === 'login') {
      do_settings_sections('securitywp_login');
    } elseif ($tab === 'xmlrpc') {
      do_settings_sections('securitywp_xmlrpc');
    } elseif ($tab === 'rest') {
      do_settings_sections('securitywp_rest');
    } elseif ($tab === 'integrity') {
      do_settings_sections('securitywp_integrity');
    } elseif ($tab === 'alerts') {
      do_settings_sections('securitywp_alerts');
    } elseif ($tab === 'tools') {
      echo '<p style="color:#646970">' . esc_html__('Integrity tools: generate a baseline and run on-demand scans.', 'securitywp') . '</p>';

      $baselineExists = is_array(get_option('securitywp_integrity_baseline', null));

      echo '<table class="widefat striped securitywp-tools-table"><thead><tr><th>' . esc_html__('Action', 'securitywp') . '</th><th>' . esc_html__('Status', 'securitywp') . '</th><th>' . esc_html__('Run', 'securitywp') . '</th></tr></thead><tbody>';

      echo '<tr>';
      echo '<td><strong>' . esc_html__('Generate baseline', 'securitywp') . '</strong><br><span style="color:#646970">' . esc_html__('Creates a snapshot of current files for future comparisons.', 'securitywp') . '</span></td>';
      echo '<td><span class="securitywp-status" id="securitywp-baseline-status">' . esc_html($baselineExists ? __('Baseline exists', 'securitywp') : __('No baseline yet', 'securitywp')) . '</span></td>';
      echo '<td><a href="#" class="button button-secondary" id="securitywp-baseline-btn">' . esc_html__('Run', 'securitywp') . '</a></td>';
      echo '</tr>';

      echo '<tr>';
      echo '<td><strong>' . esc_html__('Run scan now', 'securitywp') . '</strong><br><span style="color:#646970">' . esc_html__('Compares current files with baseline and reports changes.', 'securitywp') . '</span></td>';
      echo '<td><span class="securitywp-status" id="securitywp-scan-status">' . esc_html__('Idle', 'securitywp') . '</span></td>';
      echo '<td><a href="#" class="button button-secondary" id="securitywp-scan-btn">' . esc_html__('Run', 'securitywp') . '</a></td>';
      echo '</tr>';

      echo '</tbody></table>';

      echo '<div style="margin-top:12px" class="securitywp-card">';
      echo '<h2>' . esc_html__('What this prevents', 'securitywp') . '</h2>';
      echo '<p style="color:#646970">' . esc_html__('Integrity scanning helps detect suspicious file changes (malware, backdoors, unexpected edits).', 'securitywp') . '</p>';
      echo '</div>';
    } elseif ($tab === 'updates') {
      echo '<p style="color:#646970">' . esc_html__('Check for updates, view current version, and learn how to contribute.', 'securitywp') . '</p>';

      $repo = defined('SECURITYWP_GITHUB_URL') ? SECURITYWP_GITHUB_URL : '#';
      echo '<div class="securitywp-grid">';
      echo '<div class="securitywp-card">';
      echo '<h2>' . esc_html__('Current Version', 'securitywp') . '</h2>';
      echo '<p><strong>' . esc_html__('Installed version:', 'securitywp') . '</strong> ' . esc_html(defined('SECURITYWP_VERSION') ? SECURITYWP_VERSION : '-') . '</p>';
      echo '<p><a class="button button-secondary" href="' . esc_url($repo) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open GitHub', 'securitywp') . '</a></p>';      echo '</div>';

      echo '<div class="securitywp-card">';
      echo '<h2>' . esc_html__('How updates work', 'securitywp') . '</h2>';
      echo '<p style="color:#646970">' . esc_html__('BCM Security checks updates via GitHub Releases. Publish a new Release with a semantic version tag (example: 0.5.3).', 'securitywp') . '</p>';
      echo '<p style="color:#646970">' . esc_html__('Then WordPress will show an update when it detects the newer version.', 'securitywp') . '</p>';
      echo '</div>';
      echo '</div>';
    }

    echo '</div>';

    if ($tab !== 'tools' && $tab !== 'updates') {
      self::render_help_card($tab);
      echo '</div>';
    }

    if ($tab !== 'tools' && $tab !== 'updates') {
      submit_button(__('Save changes', 'securitywp'));
    }

    echo '</form>';

    echo '<p style="margin-top:12px">';
    echo '<a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=securitywp')) . '">' . esc_html__('Back to Dashboard', 'securitywp') . '</a> ';
    echo '<a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=securitywp&tab=events')) . '">' . esc_html__('Security Events', 'securitywp') . '</a> ';
    echo '<a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=securitywp&tab=about')) . '">' . esc_html__('About', 'securitywp') . '</a>';
    echo '</p>';

    echo '</div>';
  }
}
