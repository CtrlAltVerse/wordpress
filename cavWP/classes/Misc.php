<?php

namespace cavWP;

use WP_Error;
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

      if (\get_option('cav-theme-add_supports')) {
         \add_action('after_setup_theme', [$this, 'add_supports']);
      }

      if (get_option('cav-theme-title_tag')) {
         \add_action('after_setup_theme', [$this, 'add_title_tag']);
      }

      if (\get_option('cav-caches-css_ver_timestamp')) {
         \add_filter('style_loader_src', [$this, 'set_assets_version']);
      }

      if (get_option('cav-caches-js_ver_timestamp')) {
         add_filter('script_loader_src', [$this, 'set_assets_version']);
      }

      if (\get_option('cav-dashboard-hide_bar')) {
         \add_action('after_setup_theme', [$this, 'hide_admin_bar']);
      }

      if (\get_option('cav-theme-remove_tags')) {
         \add_action('after_setup_theme', [$this, 'remove_hooks']);
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

   public function add_supports(): void
   {
      add_theme_support('custom-background');
      add_theme_support('custom-logo');
      add_theme_support('dark-editor-style');
      add_theme_support('post-thumbnails');
      add_theme_support('responsive-embed');
      add_theme_support('html5', [
         'comment-form',
         'comment-list',
         'search-form',
         'gallery',
         'caption',
         'script',
         'style',
      ]);
   }

   public function add_title_tag(): void
   {
      add_theme_support('title-tag');
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
         esc_html__('From', 'cavwp'),
         get_home_url(),
         wp_get_environment_type(),
      );

      $output[] = sprintf(
         '%s: %s',
         esc_html__('Role', 'cavwp'),
         $user->roles[0],
      );

      if (0 !== $creator->ID) {
         $output[] = sprintf(
            '%s: %s (%s)',
            esc_html__('Created by', 'cavwp'),
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

   public function hide_admin_bar(): void
   {
      show_admin_bar(false);
   }

   public function remove_hooks(): void
   {
      /*
       * Removes the link to the Really Simple Discovery service endpoint.
       * <link rel="EditURI" type="application/rsd+xml" title="RSD" href="xmlrpc.php?rsd">
       */
      remove_action('wp_head', 'rsd_link');

      /*
       * Removes the XHTML generator that is generated on the wp_head hook.
       * <meta name="generator" content="WordPress X.XX">
       */
      remove_action('wp_head', 'wp_generator');

      /*
       * Remove the REST API link tag into page header.
       * <link rel="https://api.w.org/" href="*">
       * <link rel="alternate" type="application/json" href="*">
       */
      remove_action('wp_head', 'rest_output_link_wp_head');

      /*
       * Removes the links to the general feeds (RSS).
       * <link rel="alternate" type="*" title="*" href="*">
       */
      remove_action('wp_head', 'feed_links', 2);

      /*
       * Removes the links to the extra feeds (RSS) such as category feeds.
       * <link rel="alternate" type="*" title="*" href="*">
       */
      remove_action('wp_head', 'feed_links_extra', 3);

      /*
       * Removes the link to the Windows Live Writer manifest file.
       * <link rel="wlwmanifest" type="application/wlwmanifest+xml" href="wlwmanifest.xml">
       */
      remove_action('wp_head', 'wlwmanifest_link');

      // // remove the next and previous post links
      remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
      remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

      /*
       * Removes rel=shortlink from the head if a shortlink is defined for the current page.
       * <link rel='shortlink' href='*'>
       */
      remove_action('wp_head', 'wp_shortlink_wp_head');

      /*
       * Removes the inline Emoji detection script.
       * <script>*</script>
       */
      remove_action('wp_head', 'print_emoji_detection_script', 7);
      remove_action('admin_print_scripts', 'print_emoji_detection_script');
      remove_action('embed_head', 'print_emoji_detection_script');

      /*
       * Remove the emoji-related styles.
       * <style>*</style>
       */
      remove_action('wp_print_styles', 'print_emoji_styles');
      remove_action('admin_print_styles', 'print_emoji_styles');

      // Remove emoji to img convertor in feed, comments and e-mail.
      remove_filter('the_content_feed', 'wp_staticize_emoji');
      remove_filter('comment_text_rss', 'wp_staticize_emoji');
      remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

      /*
       * Removes oEmbed discovery links in the website.
       * <link rel="alternate" type="application/json+oembed" href="*">
       * <link rel="alternate" type="text/xml+oembed" href="*">
       */
      remove_action('wp_head', 'wp_oembed_add_discovery_links');

      /*
       * Removes the necessary JavaScript to communicate with the embedded iframe.
       * Remove oEmbed-specific JavaScript from the front-end and back-end.
       */
      remove_action('wp_head', 'wp_oembed_add_host_js');

      // Turn off inspect the given URL for discoverable link tags.
      add_filter('embed_oembed_discover', '__return_false');

      // Disables JSONP for the REST API.
      add_filter('rest_jsonp_enabled', '__return_false');

      // Disables XMLRPC
      add_filter('xmlrpc_enabled', '__return_false');
      add_filter('xmlrpc_methods', '__return_empty_array');

      // Disable Link header for the REST API.
      remove_action('template_redirect', 'rest_output_link_header', 11, 0);

      /*
       * Disable alias redirects to /wp-admin and /wp-login.
       *
       * /admin != /wp-admin
       * /dashboard != /wp-admin
       * /login != /wp-login.php
       */
      remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
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
         empty(get_option('cav-smtp-host'))
         || empty(get_option('cav-smtp-port'))
         || empty(get_option('cav-smtp-user'))
         || empty(get_option('cav-smtp-password'))
         || empty(get_option('cav-smtp-secure'))) {
         return;
      }

      $mailer->isSMTP();
      $mailer->isSendmail();
      $mailer->SMTPAuth   = true;
      $mailer->CharSet    = 'utf-8';
      $mailer->SMTPDebug  = true;
      $mailer->Host       = get_option('cav-smtp-host');
      $mailer->Port       = get_option('cav-smtp-port');
      $mailer->Username   = get_option('cav-smtp-user');
      $mailer->Password   = get_option('cav-smtp-password');
      $mailer->SMTPSecure = get_option('cav-smtp-secure');
      $mailer->From       = get_option('admin_email');
      $mailer->FromName   = get_option('blogname');
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

      return "{$label}<br>(<em>key: {$field['_name']}</em>)";
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
