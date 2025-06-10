<?php

namespace cavWP;

/*
 * Plugin Name: CAV WP Plugin
 * Plugin URI: https://plugins.altvers.net/wp/
 * Description: All-in-one tool with resources for SEO and performance enhancement and more.
 * Version: 1.0.0
 * Author: CtrlAltVerso
 * Author URI: https://ctrl.altvers.net/
 */

/**
 * @ignore
 */
define('CAV_WP_FILE', __FILE__);

/**
 * @ignore
 */
define('CAV_WP_DIR', plugin_dir_path(CAV_WP_FILE));

/**
 * @ignore
 */
define('CAV_WP_URL', plugin_dir_url(CAV_WP_FILE));

require 'functions.php';

$AutoLoader = \cav_autoloader();
$AutoLoader->add_namespace('cavWP', implode(DIRECTORY_SEPARATOR, [CAV_WP_DIR, 'classes']));

/**
 * @ignore
 */
final class cavWP
{
   public function __construct()
   {
      add_action('init', [$this, 'init_hook'], 9);

      $this->load_classes();
   }

   public function init_hook(): void
   {
      add_rewrite_tag('%cav%', '([a-z-]{12})');
      add_image_size('cav_favicon', 64, 64, true);
   }

   public function load_classes(): void
   {
      new Register_Assets();
      new Admin_Page();
      new Reorder_Head_Hooks();
      new Misc();
      new Menu();

      if (get_option('cav-metatags')) {
         new Metatags();
      }

      if (get_option('cav-sitemaps-google_news')) {
         new Sitemap_Google_News();
      }

      if (get_option('cav-opensearch')) {
         new OpenSearch();
      }

      if (get_option('cav-caches-get_requests') && !is_admin()) {
         new Cache_Requests();
      }

      if (get_option('cav-autoshare')) {
         new Social_Share();
      }

      if (get_option('cav-links')) {
         new LinksPage();
      }

      $ads_txt = get_option('cav-sitemaps-adds_ads_txt');

      if (!empty($ads_txt)) {
         new Ads_txt($ads_txt);
      }
   }
}

new cavWP();
