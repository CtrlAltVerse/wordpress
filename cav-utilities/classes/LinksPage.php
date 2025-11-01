<?php

namespace cavWP;

final class LinksPage
{
   public function __construct()
   {
      \add_action('init', [$this, 'add_rewrite']);
      \add_action('template_redirect', [$this, 'prints_content']);
      \add_filter('redirect_canonical', [$this, 'remove_trailingslashit']);

      \add_action('links_head', '_cav_prints_meta_charset', 1);
      \add_action('links_head', '_wp_render_title_tag', 9);
      \add_action('links_head', [$this, 'prints_metas'], 10);
      \add_action('links_head', [$this, 'adds_assets'], 20);
      \add_action('links_head', '_custom_background_cb', 50);
      \add_action('links_head', 'wp_site_icon', 99);

      \add_filter('walker_nav_menu_start_el', [$this, 'adds_description'], 10, 2);
   }

   public function add_rewrite(): void
   {
      add_rewrite_rule('^links/?$', 'index.php?cav=links', 'top');
   }

   public function adds_assets(): void
   {
      do_action('wp_enqueue_scripts');

      $assets = ['fontawesome', 'links_page'];

      foreach ($assets as $handle) {
         if (!wp_style_is($handle, 'registered')) {
            continue;
         }

         global $wp_styles;

         $url = $wp_styles->registered[$handle]->src;
         $ver = $wp_styles->registered[$handle]->ver;

         $href = apply_filters('style_loader_src', add_query_arg('ver', $ver, $url), $handle);

         $tag = '<link rel="stylesheet" id="' . $handle . '-css" href="' . esc_url($href) . '" type="text/css" media="all" />';

         echo apply_filters('style_loader_tag', $tag, $handle);
      }
   }

   public function adds_description($output, $menu_item)
   {
      $cav_template = get_query_var('cav', false);

      if ('links' !== $cav_template || empty($menu_item->description)) {
         return $output;
      }

      return str_replace('</a>', '<span class="menu-item-description">' . $menu_item->description . '</span></a>', $output);
   }

   public function prints_content(): void
   {
      $cav_template = get_query_var('cav', false);

      if ('links' !== $cav_template) {
         return;
      }

      latte_plugin('links_page', [
         'name'        => get_bloginfo('name'),
         'site'        => get_bloginfo('url'),
         'domain'      => Utils::clean_domain(get_bloginfo('url')),
         'home'        => esc_html__('Homepage'),
         'description' => get_bloginfo('description'),
         'avatar'      => get_avatar(get_option('admin_email')),
         'menu'        => get_option('cav-links-menu'),
      ]);
      exit;
   }

   public function prints_metas(): void
   {
      do_action('cav_head_metas');
   }

   public function remove_trailingslashit($redirect_url)
   {
      $cav_template = get_query_var('cav', false);

      if ('links' !== $cav_template) {
         return $redirect_url;
      }

      return rtrim($redirect_url, '/');
   }
}
