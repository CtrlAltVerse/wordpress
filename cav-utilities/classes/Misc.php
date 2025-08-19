<?php

namespace cavWP;

use WP_Error;
use WP_REST_Response;
use WP_User;

/**
 * @ignore
 */
final class Misc
{
   private $custom_robots_txt = false;
   private $page_templates    = [
      '404',
      'archive',
      'attachment',
      'author',
      'category',
      'date',
      'embed',
      'frontpage',
      'home',
      'index',
      'page',
      'paged',
      'privacypolicy',
      'search',
      'single',
      'singular',
      'tag',
      'taxonomy',
   ];

   public function __construct()
   {
      \add_action('admin_init', [$this, 'adds_nav_menu_item_post_type']);

      \add_filter('authenticate', [$this, 'sets_generic_login_error'], 21);
      \add_filter('nav_menu_items_nav_menu_item_recent', [$this, 'filters_nav_menu_items']);
      \add_filter('nav_menu_items_nav_menu_item', [$this, 'filters_nav_menu_items']);

      $this->custom_robots_txt = get_option('cav-sitemaps-adds_robots_txt');

      if (!empty($this->custom_robots_txt)) {
         \add_filter('robots_txt', [$this, 'add_custom_robots'], 15);
      }

      if (get_option('cav-dashboard-show_image_sizes')) {
         \add_filter('image_size_names_choose', [$this, 'show_images_all_sizes']);
      }

      if (get_option('cav-theme-pages_template')) {
         foreach ($this->page_templates as $template) {
            \add_filter("{$template}_template_hierarchy", [$this, 'add_pages_folder']);
         }
      }

      if (\get_option('cav-caches-css_ver_timestamp')) {
         \add_filter('style_loader_src', [$this, 'set_assets_version']);
      }

      if (get_option('cav-caches-js_ver_timestamp')) {
         add_filter('script_loader_src', [$this, 'set_assets_version']);
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

      if (\get_option('cav-smtp')) {
         \add_action('phpmailer_init', [$this, 'sets_mailer_config']);
      }

      if (\get_option('cav-caches-defer_css') && wp_is_mobile()) {
         \add_filter('style_loader_tag', [$this, 'adds_style_async'], 10, 4);
      }

      if (\get_option('cav-dashboard-rest_api_logged')) {
         \add_filter('rest_authentication_errors', [$this, 'rest_api_only_logged']);
      }

      add_action('rest_api_init', function(): void {
         add_filter('rest_post_dispatch', [$this, 'rest_api_parse_errors'], 10, 3);
      });
   }

   public function add_custom_robots($robots_txt)
   {
      $robots_txt .= "\n" . $this->custom_robots_txt . "\n";

      return $robots_txt;
   }

   public function add_pages_folder(array $templates)
   {
      if (str_contains($templates[0], 'pages/')) {
         return $templates;
      }

      foreach ($templates as $template) {
         $index           = str_replace('.php', '/_index.php', $template);
         $new_templates[] = "pages/{$index}";
         $new_templates[] = "pages/{$template}";
      }

      return array_merge($new_templates, $templates);
   }

   public function adds_nav_menu_item_post_type(): void
   {
      global $wp_post_types;

      $wp_post_types['nav_menu_item']->show_in_nav_menus = true;
   }

   public function adds_style_async($tag, $handle, $_href = '', $media = 'all')
   {
      return str_replace(["media='{$media}'", 'media="' . $media . '"'], 'media="print" onload="document.getElementById(\'' . $handle . '-css\').media=\'' . $media . '\'"', $tag);
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

   public function filters_nav_menu_items($posts)
   {
      return array_filter($posts, fn($post) => 'custom' === get_post_meta($post->ID, '_menu_item_object', true));
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

   public function rest_api_parse_errors($response, $_server, $request)
   {
      $attributes = $request->get_attributes();

      if (empty($attributes['parse_errors']) || !$response->is_error()) {
         return $response;
      }

      $code = $response->get_data()['code'];

      $errors = [];

      if ('rest_missing_callback_param' === $code) {
         $errors = $response->get_data()['message'];
      } else {
         $errors = $response->get_data()['data']['params'] ?? $response->get_data()['message'];
      }

      if (empty($errors)) {
         return $response;
      }

      if (!is_array($errors)) {
         $errors = [$errors];
      }

      foreach ($errors as $message) {
         $errors_result[] = [
            'action'  => 'toast',
            'content' => strip_tags($message),
         ];
      }

      return new WP_REST_Response($errors_result, $response->get_status());
   }

   public function set_assets_version($url)
   {
      if (stripos($url, get_template_directory_uri()) === false) {
         return $url;
      }

      if (stripos($url, '?ver=') === false) {
         return $url;
      }

      $file = Utils::url_to_path($url);

      if (false === $file) {
         return $url;
      }

      $ver = filemtime($file);

      if (empty($ver)) {
         return $url;
      }

      return add_query_arg('ver', $ver, $url);
   }

   public function sets_generic_login_error($maybe_error)
   {
      if (!is_wp_error($maybe_error)) {
         return $maybe_error;
      }

      if (in_array($maybe_error->get_error_code(), ['invalid_email', 'invalid_username', 'incorrect_password', 'invalidcombo'])) {
         return new WP_Error(
            'authentication_failed',
            __('<strong>Error:</strong> Invalid username, email address or incorrect password.'),
         );
      }

      return $maybe_error;
   }

   public function sets_mailer_config($mailer): void
   {
      if (
         empty(get_option('cav-smtp-host')) || empty(get_option('cav-smtp-port')) || empty(get_option('cav-smtp-user')) || empty(get_option('cav-smtp-password')) || empty(get_option('cav-smtp-secure'))) {
         return;
      }

      $mailer->isSMTP();
      $mailer->SMTPAuth    = true;
      $mailer->CharSet     = 'utf-8';
      $mailer->Host        = get_option('cav-smtp-host');
      $mailer->Port        = (int) get_option('cav-smtp-port');
      $mailer->Username    = get_option('cav-smtp-user');
      $mailer->Password    = get_option('cav-smtp-password');
      $mailer->SMTPSecure  = get_option('cav-smtp-secure');
      $mailer->From        = get_option('admin_email');
      $mailer->FromName    = get_option('blogname');
      $mailer->SMTPDebug   = WP_DEBUG ? 1 : 0;
      $mailer->Debugoutput = 'debug';
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

      if (!isset($field['_name'])) {
         return $label;
      }

      return "{$label} <em>({$field['_name']})</em>";
   }

   public function show_images_all_sizes($images_names)
   {
      $images_names['medium_large'] = __('Intermediate');

      $images_sizes = wp_get_registered_image_subsizes();

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
