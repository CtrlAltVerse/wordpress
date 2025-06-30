<?php

namespace cavWP\Activity_Log;

final class Utils
{
   public static function get_activity_log_events()
   {
      $default_events = [
         'post_deleted' => [
            'trigger'        => ['action_hook' => 'delete_post', 'num_args' => 2],
            'columns_labels' => ['entity_ID' => 'post_author', 'entity_details' => 'WP_Post'],
         ],
         'term_deleted' => [
            'trigger'        => ['action_hook' => 'delete_term', 'num_args' => 4],
            'columns_labels' => ['entity_details' => 'WP_Term'],
         ],
         'user_deleted' => [
            'trigger'        => ['action_hook' => 'delete_user', 'num_args' => 3],
            'columns_labels' => ['entity_details' => 'WP_User'],
         ],
         'user_login_failed' => [
            'trigger'        => ['action_hook' => 'wp_login_failed', 'num_args' => 2],
            'columns_labels' => ['entity_details' => 'details'],
         ],
         'user_login' => [
            'trigger'        => ['action_hook' => 'wp_login', 'num_args' => 2],
            'columns_labels' => ['entity_details' => 'WP_User'],
         ],
         'user_logout' => [
            'trigger' => ['action_hook' => 'wp_logout'],
         ],
         'user_updated' => [
            'trigger'        => ['action_hook' => 'profile_update', 'num_args' => 3],
            'columns_labels' => ['entity_details' => 'changes'],
         ],
         'user_registered' => [
            'trigger'        => ['action_hook' => 'user_register', 'num_args' => 2],
            'columns_labels' => ['entity_details' => 'userdata'],
         ],
      ];

      return apply_filters('cav_activity_log_events', $default_events);
   }

   public static function get_table_name($prefix)
   {
      return $prefix . 'cav_activity_logs';
   }
}
