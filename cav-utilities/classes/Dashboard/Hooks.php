<?php

namespace cavWP\Dashboard;

use WP_Error;
use WP_User;

final class Hooks
{
   public function __construct()
   {
      if (\get_option('cav-dashboard-hide_bar')) {
         \add_action('after_setup_theme', [$this, 'hide_admin_bar']);
      }

      if (\get_option('cav-dashboard-deactivate_threshold')) {
         \add_filter('big_image_size_threshold', [$this, 'remove_threshold']);
      }

      if (\get_option('cav-dashboard-show_image_sizes')) {
         \add_filter('image_size_names_choose', [$this, 'show_images_all_sizes']);
      }

      if (\get_option('cav-dashboard-acf_show_key')) {
         \add_action('acf/get_field_label', [$this, 'show_acf_field_key'], 5, 2);
      }

      if (\get_option('cav-dashboard-change_schema_colors') && 'production' !== wp_get_environment_type()) {
         \add_filter('get_user_option_admin_color', [$this, 'change_admin_scheme']);
      }

      if (\get_option('cav-dashboard-new_user_mail')) {
         \add_filter('wp_new_user_notification_email_admin', [$this, 'change_new_user_mail'], 10, 2);
      }

      if (\get_option('cav-dashboard-rest_api_logged')) {
         \add_filter('rest_authentication_errors', [$this, 'rest_api_only_logged']);
      }
   }

   public function change_admin_scheme($color_scheme)
   {
      global $pagenow;

      if (in_array($pagenow, ['profile.php', 'user-edit.php'])) {
         return $color_scheme;
      }

      return match (wp_get_environment_type()) {
         'local'       => 'ocean',
         'development' => 'blue',
         'staging'     => 'ectoplasm',
         default       => $color_scheme,
      };
   }

   public function change_new_user_mail(array $email, WP_User $user)
   {
      $creator = wp_get_current_user();

      $output[] = sprintf(
         '%s: %s (%s)',
         esc_html__('From', 'cav-utilities'),
         get_home_url(),
         wp_get_environment_type(),
      );

      $output[] = sprintf(
         '%s: %s',
         esc_html__('Role', 'cav-utilities'),
         $user->roles[0],
      );

      if (0 !== $creator->ID) {
         $output[] = sprintf(
            '%s: %s (%s)',
            esc_html__('Created by', 'cav-utilities'),
            $creator->display_name,
            $creator->user_email,
         );
      }

      $email['message'] .= "\n" . implode("\n\n", $output);

      return $email;
   }

   public function hide_admin_bar(): void
   {
      \show_admin_bar(false);
   }

   public function remove_threshold()
   {
      return 99999;
   }

   public function rest_api_only_logged($result)
   {
      if (true === $result || is_wp_error($result)) {
         return $result;
      }

      if (!is_user_logged_in()) {
         return new WP_Error(
            'rest_not_logged_in',
            __('You are not currently logged in.'),
            ['status' => 401],
         );
      }

      return $result;
   }

   public function show_acf_field_key($label, $field)
   {
      if (!current_user_can('manage_options')) {
         return $label;
      }

      $screen = get_current_screen();

      if (!isset($screen->post_type) || 'acf-field-group' === $screen->post_type) {
         return $label;
      }

      if (empty($field['_name'])) {
         return $label;
      }

      return "{$label} <em>({$field['_name']})</em>";
   }

   public function show_images_all_sizes($images_names)
   {
      $images_names['medium_large'] = __('Intermediate');

      $images_sizes = \wp_get_registered_image_subsizes();

      foreach ($images_names as $image_name => $image_label) {
         if (!isset($images_sizes[$image_name])) {
            continue;
         }

         $image_size = $images_sizes[$image_name];

         $new_images_names[$image_name] = "{$image_label} ({$image_size['width']}x{$image_size['height']})";
      }

      return $new_images_names;
   }
}
