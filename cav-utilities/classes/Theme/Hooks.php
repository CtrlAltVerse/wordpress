<?php

namespace cavWP\Theme;

final class Hooks
{
   private $page_templates = [
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
      \add_action('init', [$this, 'changes_base_rewrite']);

      if (\get_option('cav-theme-gtm_code')) {
         \add_action('cav_head_scripts', [$this, 'add_gtm_script']);
         \add_action('wp_body_open', [$this, 'add_gtm_html']);
      }

      if (\get_option('cav-theme-remove_title_prepend')) {
         \add_filter('protected_title_format', [$this, 'remove_prepend']);
         \add_filter('private_title_format', [$this, 'remove_prepend']);
      }

      if (\get_option('cav-theme-no_title')) {
         \add_filter('the_title', [$this, 'add_no_title']);
      }

      if (\get_option('cav-theme-add_supports')) {
         \add_action('after_setup_theme', [$this, 'add_supports']);
      }

      if (get_option('cav-theme-title_tag')) {
         \add_action('after_setup_theme', [$this, 'add_title_tag']);
      }

      if (\get_option('cav-theme-remove_tags')) {
         \add_action('after_setup_theme', [$this, 'remove_hooks']);
      }

      if (\get_option('cav-theme-remove_assets')) {
         \add_action('wp_enqueue_scripts', [$this, 'dequeue_styles'], 100);
      }

      if (\get_option('cav-theme-rest_url_base')) {
         \add_filter('rest_url_prefix', [$this, 'change_rest_api_base']);
      }

      if (\get_option('cav-theme-pages_template')) {
         foreach ($this->page_templates as $template) {
            \add_filter("{$template}_template_hierarchy", [$this, 'add_pages_folder']);
         }
      }
   }

   public function add_gtm_html()
   {
      $code = \get_option('cav-theme-gtm_code');

      echo <<<HTML
      <!-- Google Tag Manager (noscript) -->
      <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$code}"
      height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
      <!-- End Google Tag Manager (noscript) -->
      HTML;
   }

   public function add_gtm_script()
   {
      $code = \get_option('cav-theme-gtm_code');

      echo <<<HTML
      <!-- Google Tag Manager -->
      <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
      new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
      j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
      'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
      })(window,document,'script','dataLayer','{$code}');</script>
      <!-- End Google Tag Manager -->
      HTML;
   }

   public function add_no_title($post_title)
   {
      if ('' !== trim($post_title)) {
         return $post_title;
      }

      return esc_html__('(no title)');
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
      add_theme_support('menus');
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

   public function change_rest_api_base()
   {
      return 'api';
   }

   public function changes_base_rewrite()
   {
      global $wp_rewrite;

      if (get_option('cav-theme-author_base')) {
         $wp_rewrite->author_base = get_option('cav-theme-author_base');
      }

      if (get_option('cav-theme-search_base')) {
         $wp_rewrite->search_base = get_option('cav-theme-search_base');
      }
   }

   public function dequeue_styles(): void
   {
      wp_dequeue_style('wp-block-library');
      wp_dequeue_style('classic-theme-styles');
      wp_dequeue_style('global-styles');
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

      // Remove W3 Total Cache comment from footer
      add_filter('w3tc_can_print_comment', '__return_false');

      // Disable JSON-LD sitelinks searchbox using WordPress
      add_filter('disable_wpseo_json_ld_search', '__return_true');

      /*
       * Disable alias redirects to /wp-admin and /wp-login.
       *
       * /admin != /wp-admin
       * /dashboard != /wp-admin
       * /login != /wp-login.php
       */
      remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
   }

   public function remove_prepend()
   {
      return '%s';
   }
}
