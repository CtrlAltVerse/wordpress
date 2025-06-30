<?php

namespace cavWP\Activity_Log;

/**
 * Logs default and custom WordPress events, with detailed information.
 */
final class Register
{
   public function __construct()
   {
      \add_action('init', [$this, 'create_table']);

      if (\get_option('cav-activity_log-block_fail_logins')
      && \get_option('cav-activity_log-block_fail_logins_interval')
      && \get_option('cav-activity_log-block_fail_logins_attempts')) {
         \add_filter('login_errors', [$this, 'blocks_login'], 1);
      }

      $events         = Utils::get_activity_log_events();
      $default_events = new Default_Events();

      foreach ($events as $event) {
         $method = $event['trigger']['action_hook'];

         if (empty($method) || !method_exists($default_events, $method)) {
            continue;
         }

         \add_action(
            $method,
            [$default_events, $method],
            15,
            $event['trigger']['num_args'] ?? 1,
         );
      }
   }

   public function blocks_login($errors)
   {
      $Logger = new Logger();

      $interval = \get_option('cav-activity_log-block_fail_logins_interval');
      $count    = \get_option('cav-activity_log-block_fail_logins_attempts');

      $login_fails = $Logger->get('user_login_failed', [[
         'key'   => 'current_IP',
         'value' => CURRENT_IP,
      ], [
         'key'     => 'activity_time_gmt',
         'compare' => 'BETWEEN',
         'raw'     => true,
         'value'   => [
            "UTC_TIMESTAMP() - INTERVAL {$interval} MINUTE",
            'UTC_TIMESTAMP()',
         ],
      ]], [
         'activity_type'   => false,
         'entity_details'  => false,
         'entity_ID'       => false,
         'current_user_ID' => false,
      ]);

      if (count($login_fails) >= $count) {
         return __('<strong>Error:</strong> Too many failed login attempts.', 'cavwp');
      }

      return $errors;
   }

   public function create_table(): void
   {
      if ((bool) \get_option('cav-activity_logs-table_created')) {
         return;
      }

      if (!function_exists('dbDelta')) {
         require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      }

      global $wpdb;

      $table_name      = Utils::get_table_name($wpdb->prefix);
      $charset_collate = $wpdb->get_charset_collate();

      $sql_query = <<<SQL
      CREATE TABLE IF NOT EXISTS {$table_name} (
         `activity_ID` BIGINT(30) NOT NULL AUTO_INCREMENT,
         `activity_type` VARCHAR(191) NOT NULL,
         `entity_ID` BIGINT(20),
         `entity_details` LONGTEXT NULL,
         `activity_time_gmt` DATETIME NOT NULL,
         `current_user_ID` INT DEFAULT '0',
         `current_IP` VARCHAR(39),
         `current_ua` VARCHAR(255),
         PRIMARY KEY (activity_ID),
         INDEX (activity_ID, activity_type, entity_ID)
      ) {$charset_collate} ENGINE=InnoDB;
      SQL;

      \dbDelta($sql_query);

      if (empty($wpdb->last_error)) {
         \update_option('cav-activity_logs-table_created', current_time('mysql', true), 'yes');
      }
   }
}
