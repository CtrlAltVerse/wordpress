<?php

namespace cavWP;

use WP_Error;
use WP_REST_Response;

/**
 * @ignore
 */
final class Misc
{
   private $custom_robots_txt = false;

   public function __construct()
   {
      \add_action('init', [$this, 'add_page_support']);

      \add_action('admin_init', [$this, 'adds_nav_menu_item_post_type']);
      \add_action('admin_init', [$this, 'add_admin_color_scheme']);

      \add_filter('authenticate', [$this, 'sets_generic_login_error'], 21);
      \add_filter('nav_menu_items_nav_menu_item_recent', [$this, 'filters_nav_menu_items']);
      \add_filter('nav_menu_items_nav_menu_item', [$this, 'filters_nav_menu_items']);

      $this->custom_robots_txt = get_option('cav-sitemaps-adds_robots_txt');

      if (!empty($this->custom_robots_txt)) {
         \add_filter('robots_txt', [$this, 'add_custom_robots'], 15);
      }

      if (\get_option('cav-caches-css_ver_timestamp')) {
         \add_filter('style_loader_src', [$this, 'set_assets_version']);
      }

      if (get_option('cav-caches-js_ver_timestamp')) {
         add_filter('script_loader_src', [$this, 'set_assets_version']);
      }

      if (\get_option('cav-smtp')) {
         \add_action('phpmailer_init', [$this, 'sets_mailer_config']);
      }

      if (\get_option('cav-caches-defer_css') && wp_is_mobile()) {
         \add_filter('style_loader_tag', [$this, 'adds_style_async'], 10, 4);
      }

      add_action('rest_api_init', function(): void {
         add_filter('rest_post_dispatch', [$this, 'rest_api_parse_errors'], 10, 3);
      });
   }

   public function add_admin_color_scheme()
   {
      wp_admin_css_color(
         'dracula',
         'Dracula',
         CAV_WP_URL . '/assets/admin_dracula_theme.css',
         ['#44475a', '#f8f8f2', '#ff5555', '#69a8bb'],
      );
   }

   public function add_custom_robots($robots_txt)
   {
      $robots_txt .= "\n" . $this->custom_robots_txt . "\n";

      return $robots_txt;
   }

   public function add_page_support()
   {
      add_post_type_support('page', 'excerpt');
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

   public function filters_nav_menu_items($posts)
   {
      return array_filter($posts, fn($post) => 'custom' === get_post_meta($post->ID, '_menu_item_object', true));
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
      if (
         stripos($url, get_template_directory_uri()) === false && stripos($url, CAV_WP_URL) === false
      ) {
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
      $mailer->SMTPAuth   = true;
      $mailer->CharSet    = 'utf-8';
      $mailer->Host       = get_option('cav-smtp-host');
      $mailer->Port       = (int) get_option('cav-smtp-port');
      $mailer->Username   = get_option('cav-smtp-user');
      $mailer->Password   = get_option('cav-smtp-password');
      $mailer->SMTPSecure = get_option('cav-smtp-secure');
      $mailer->From       = get_option('cav-smtp-user');
      $mailer->AddReplyTo(get_option('admin_email'));
      $mailer->FromName    = get_option('blogname');
      $mailer->SMTPDebug   = WP_DEBUG ? 1 : 0;
      $mailer->Debugoutput = 'debug';
   }
}
