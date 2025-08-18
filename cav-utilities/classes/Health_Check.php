<?php

namespace cavWP;

use WP_Debug_Data;
use WP_Error;
use WP_REST_Server;
use WP_Site_Health;

/**
 * Creates API endpoint /cav/health-check to test some resources of the site.
 *
 * @since 1.0.0
 */
class Health_Check
{
   /** @ignore */
   private $site_health;

   /**
    * @ignore
    */
   public function __construct()
   {
      if (!class_exists('WP_Site_Health')) {
         require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
      }

      if (!class_exists('WP_Debug_Data')) {
         require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
      }

      $this->site_health = WP_Site_Health::get_instance();

      add_action('rest_api_init', [$this, 'create_endpoints']);
   }

   /**
    * @ignore
    */
   public function create_endpoints(): void
   {
      register_rest_route('cav/v1', '/health-check', [
         'methods'             => WP_REST_Server::READABLE,
         'callback'            => [$this, 'health_check'],
         'permission_callback' => '__return_true',
      ]);
   }

   public function health_check()
   {
      // 0s - If reaches here, all those three is OK.
      $checks['php'] = true;
      $checks['wp']  = true;
      $checks['api'] = true;

      // 8s - api.wordpress.org is accessible
      $checks['dotorg'] = $this->check_url('https://api.wordpress.org');

      if (is_environment(['staging', 'production'])) {
         // 8s - wp-cron is accessible
         $checks['cron'] = $this->check_url(site_url('wp-cron.php'));

         // 8s - home is accessible and has content
         $https_home     = $this->check_url(home_url('/', is_ssl() ? 'https' : 'http'), ['is_local' => true, 'is_empty' => 1024]);
         $checks['home'] = $https_home;

         // 0s - http or https
         if (is_ssl()) {
            $checks['https'] = $https_home;
         } else {
            $checks['http'] = $https_home;
         }
      }

      // 0s - theme
      $target_theme = get_option('cav-health_check-theme');

      if ((bool) $target_theme) {
         $current_theme   = wp_get_theme()->get_template();
         $checks['theme'] = $target_theme === $current_theme;
      }

      // 8s - custom-url
      $custom_url = get_option('cav-health_check-custom_url');

      if ((bool) $custom_url) {
         $custom_url = parse_url($custom_url, PHP_URL_PATH);

         if ((bool) parse_url($custom_url, PHP_URL_QUERY)) {
            $custom_url .= '?' . parse_url($custom_url, PHP_URL_QUERY);
         }
         $checks['custom_url'] = $this->check_url(home_url($custom_url, is_ssl() ? 'https' : 'http'), ['is_local' => true, 'is_empty' => 1024]);
      }

      // 0s - scheduled
      $scheduled           = $this->site_health->get_test_scheduled_events();
      $checks['scheduled'] = 'good' === $scheduled['status'];

      // 1s - db table numbers
      $checks['db'] = $this->db_tables();

      // 1s - folder sizes
      $checks = array_merge($checks, $this->folder_sizes());

      $fail_checks = array_filter($checks, fn($check) => empty($check));

      if (!empty($fail_checks)) {
         $fail_checks_keys = implode(', ', array_keys($fail_checks));

         return new WP_Error('health_check_fail', 'Have fails: ' . $fail_checks_keys, ['status' => 500]);
      }

      return $checks;
   }

   /**
    * @ignore
    *
     * @param mixed $url
     * @param mixed $user_options
    */
   private function check_url($url, $user_options = [])
   {
      $defaults  = ['is_local' => false, 'is_empty' => false];
      $checks_if = array_merge($defaults, $user_options);

      $response = wp_remote_get($url, [
         'timeout'     => 8,
         'redirection' => 2,
         'sslverify'   => is_environment(['staging', 'production']),
         'cache'       => false,
         'headers'     => [
            'Cache-Control' => 'no-cache',
         ],
      ]);

      if (is_wp_error($response)) {
         debug($url, $response);

         return false;
      }

      if (200 !== wp_remote_retrieve_response_code($response)) {
         return false;
      }

      $body = wp_remote_retrieve_body($response);

      if ($checks_if['is_local']) {
         if (false === wp_is_local_html_output($body)) {
            return false;
         }
      }

      if ($checks_if['is_empty']) {
         if (strlen($body) < (int) $checks_if['is_empty']) {
            return false;
         }
      }

      return true;
   }

   /**
    * @ignore
    */
   private function db_tables()
   {
      global $wpdb;
      $rows = $wpdb->get_results('SHOW TABLES');

      return count($rows) >= 12;
   }

   /**
    * @ignore
    *
     * @param mixed $min_size
    */
   private function folder_sizes($min_size = 1024)
   {
      $folders = [];

      $sizes_data = WP_Debug_Data::get_sizes();
      unset($sizes_data['uploads_size']);

      foreach ($sizes_data as $folder => $data) {
         if (empty($data['raw'])) {
            continue;
         }

         $folders[$folder] = $data['raw'] >= $min_size;
      }

      return $folders;
   }
}
