<?php

namespace cavWP;

/**
 * @ignore
 */
final class ManifestJson
{
   public function __construct()
   {
      \add_action('init', [$this, 'add_rewrite']);
      \add_action('template_redirect', [$this, 'prints_content']);
      \add_action('wp_head', [$this, 'add_manifest_link']);

      \add_filter('redirect_canonical', [$this, 'remove_trailingslashit']);
   }

   public function add_manifest_link()
   {
      echo '<link rel="manifest" href="' . home_url('manifest.json') . '">';
   }

   public function add_rewrite(): void
   {
      add_rewrite_rule('^manifest\.json/?$', 'index.php?cav=manifest', 'top');
   }

   public function prints_content(): void
   {
      $cav_template = get_query_var('cav', false);

      if ('manifest' !== $cav_template) {
         return;
      }

      header('Content-Type: application/json; charset=utf-8');

      $manifest = [
         '$schema'      => 'https://json.schemastore.org/web-manifest-combined.json',
         'name'         => get_bloginfo('name'),
         'short_name'   => get_bloginfo('name'),
         'description'  => get_bloginfo('description'),
         'start_url'    => '/',
         'display'      => 'standalone',
         'url_handlers' => [
            [
               'origin' => home_url(),
            ],
         ],
      ];

      if ($color = get_option('cav-metatags-theme_color')) {
         $manifest['background_color'] = $color;
      }

      if (has_site_icon()) {
         $site_icon_id = (int) get_option('site_icon');
         $mime_type    = get_post_mime_type($site_icon_id);

         $icon_32 = get_site_icon_url(32);

         if ($icon_32) {
            $manifest['icons'][] = [
               'src'   => esc_url($icon_32),
               'sizes' => '32x32',
               'type'  => $mime_type,
            ];
         }
         $icon_192 = get_site_icon_url(192);

         if ($icon_192) {
            $manifest['icons'][] = [
               'src'   => esc_url($icon_192),
               'sizes' => '192x192',
               'type'  => $mime_type,
            ];
         }
         $icon_180 = get_site_icon_url(180);

         if ($icon_180) {
            $manifest['icons'][] = [
               'src'   => esc_url($icon_180),
               'sizes' => '180x180',
               'type'  => $mime_type,
            ];
         }
         $icon_270 = get_site_icon_url(270);

         if ($icon_270) {
            $manifest['icons'][] = [
               'src'   => esc_url($icon_270),
               'sizes' => '270x270',
               'type'  => $mime_type,
            ];
         }
      }

      echo json_encode($manifest);

      exit;
   }

   public function remove_trailingslashit($redirect_url)
   {
      $cav_template = get_query_var('cav', false);

      if ('manifest' !== $cav_template) {
         return $redirect_url;
      }

      return rtrim($redirect_url, '/');
   }
}
