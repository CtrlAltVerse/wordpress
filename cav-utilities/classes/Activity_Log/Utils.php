<?php

namespace cavWP\Activity_Log;

final class Utils
{
   public static function get_activity_log_default_events()
   {
      $default_events = [
         'post_deleted' => [
            'name'    => __('Post deleted', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'delete_post',
               'num_args'    => 2,
            ],
            'columns_labels' => [
               'entity_ID'      => 'post_author',
               'entity_details' => 'WP_Post',
            ],
         ],
         'term_deleted' => [
            'name'    => __('Term deleted', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'delete_term',
               'num_args'    => 4,
            ],
            'columns_labels' => [
               'entity_details' => 'WP_Term',
            ],
         ],
         'user_deleted' => [
            'name'    => __('User deleted', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'delete_user',
               'num_args'    => 3,
            ],
            'columns_labels' => [
               'entity_details' => 'WP_User',
            ],
         ],
         'user_login_failed' => [
            'name'    => __('Login failed', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'wp_login_failed',
               'num_args'    => 2,
            ],
            'columns_labels' => [
               'entity_details' => 'details',
            ],
         ],
         'user_login' => [
            'name'    => __('User login', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'wp_login',
               'num_args'    => 2,
            ],
            'columns_labels' => [
               'entity_details' => 'WP_User',
            ],
         ],
         'user_logout' => [
            'name'    => __('User logout', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'wp_logout',
            ],
         ],
         'post_updated' => [
            'name'    => __('Post updated', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'post_updated',
               'num_args'    => 3,
            ],
         ],
         'comment_updated' => [
            'name'    => __('Comment updated', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'edit_comment',
               'num_args'    => 2,
            ],
         ],
         'comment_approved' => [
            'name'    => __('Comment approved', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'comment_approved_comment',
            ],
         ],
         'user_updated' => [
            'name'    => __('User updated', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'profile_update',
               'num_args'    => 3,
            ],
            'columns_labels' => [
               'entity_details' => 'changes',
            ],
         ],
         'user_registered' => [
            'name'    => __('User registered', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'user_register',
               'num_args'    => 2,
            ],
            'columns_labels' => [
               'entity_details' => 'userdata',
            ],
         ],
         'search' => [
            'name'    => __('Term searched', 'cav-utilities'),
            'trigger' => [
               'action_hook' => 'pre_get_posts',
            ],
         ],
      ];

      return apply_filters('cav_activity_log_events', $default_events);
   }

   public static function get_table_name($prefix)
   {
      return $prefix . 'cav_activity_logs';
   }
}
