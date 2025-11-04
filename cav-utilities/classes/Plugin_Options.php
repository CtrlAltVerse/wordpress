<?php

namespace cavWP;

/**
 * @ignore
 */
final class Plugin_Options
{
   public static function get_categories($key = '')
   {
      $main_categories = [
         'seo' => [
            'label' => esc_html__('SEO', 'cav-utilities'),
            'color' => '#4b9fff',
         ],
         'performance' => [
            'label' => esc_html__('Performance', 'cav-utilities'),
            'color' => '#ff8904',
         ],
         'divulgation' => [
            'label' => esc_html__('Divulgation', 'cav-utilities'),
            'color' => '#c800de',
         ],
         'network' => [
            'label' => esc_html__('Networks', 'cav-utilities'),
            'color' => '#c800de',
         ],
         'dashboard' => [
            'label' => esc_html__('Dashboard', 'cav-utilities'),
            'color' => '#3429cd',
         ],
         'theme' => [
            'label' => esc_html__('Theme', 'cav-utilities'),
            'color' => '#047442',
         ],
      ];

      $custom_categories = apply_filters('cav_settings_categories', []);

      $categories = array_merge($custom_categories, $main_categories);

      if (empty($key)) {
         return $categories;
      }

      if (isset($categories[$key])) {
         return $categories[$key];
      }

      return [
         'label' => 'Misc',
         'color' => 'green',
      ];
   }

   public static function get_options()
   {
      $options = [
         'metatags' => [
            'title'       => 'Metatags',
            'description' => esc_html__('Adds metatags for SEO and social share.', 'cav-utilities'),
            'category'    => 'seo',
            'active'      => true,
            'fields'      => [
               'theme_color' => [
                  'label'       => esc_html__('Site color', 'cav-utilities'),
                  'description' => esc_html__('Used on Chrome in Android and Windows 8 Pin.', 'cav-utilities'),
                  'type'        => 'color',
               ],
               'manifest' => [
                  'label' => esc_html__('Creates manifest.json file for Web Application', 'cav-utilities'),
                  'type'  => 'checkbox',
               ],
               'fb_app_id' => [
                  'label' => esc_html__('Facebook App ID', 'cav-utilities'),
                  'type'  => 'number',
               ],
               'twitter_site' => [
                  'label' => esc_html__("X's Site Account", 'cav-utilities'),
               ],
            ],
         ],
         'sitemaps' => [
            'title'       => 'Sitemaps, robots.txt and ads.txt',
            'description' => esc_html__('Creates adicional sitemaps and configure content for robots.txt and ads.txt.', 'cav-utilities'),
            'category'    => 'seo',
            'active'      => false,
            'fields'      => [
               'google_news' => [
                  'label'       => 'Google News',
                  'description' => sprintf(esc_html__('Creates %s.', 'cav-utilities'), 'sitemap-google-news.xml'),
                  'type'        => 'checkbox',
               ],
               'adds_robots_txt' => [
                  'label' => esc_html__('Custom content for robots.txt', 'cav-utilities'),
                  'type'  => 'textarea',
               ],
               'adds_ads_txt' => [
                  'label' => esc_html__('Custom content for ads.txt', 'cav-utilities'),
                  'type'  => 'textarea',
               ],
            ],
         ],
         'minify' => [
            'title'       => 'Minify HTML',
            'description' => '',
            'active'      => true,
            'category'    => 'performance',
            'fields'      => [
               'inline_js' => [
                  'label' => esc_html__('Minify JS inline', 'cav-utilities'),
                  'type'  => 'checkbox',
               ],
               'inline_css' => [
                  'label' => esc_html__('Minify CSS inline', 'cav-utilities'),
                  'type'  => 'checkbox',
               ],
               'remove_comments' => [
                  'label' => esc_html__('Remove comments', 'cav-utilities'),
                  'type'  => 'checkbox',
               ],
            ],
         ],
         'opensearch' => [
            'title'       => 'OpenSearch',
            'description' => esc_html__('Creates opensearch.osdx file', 'cav-utilities'),
            'active'      => true,
            'category'    => 'seo',
            'fields'      => [
               'longname' => [
                  'label' => esc_html__('Long name', 'cav-utilities'),
                  'attrs' => [
                     'maxlength' => 48,
                  ],
               ],
               'tags' => [
                  'label'       => esc_html__('Tags', 'cav-utilities'),
                  'description' => esc_html__('Keywords separated by space', 'cav-utilities'),
                  'attrs'       => [
                     'maxlength' => 256,
                  ],
               ],
               'contact' => [
                  'label' => esc_html__('E-mail for contact', 'cav-utilities'),
                  'type'  => 'email',
               ],
            ],
         ],
         'caches' => [
            'title'       => 'Cache & Performance',
            'description' => '',
            'active'      => false,
            'category'    => 'performance',
            'fields'      => [
               'defer_css' => [
                  'label' => esc_html__('Asynchronously loads external CSS files on mobile devices.', 'cav-utilities'),
                  'type'  => 'checkbox',
               ],
               'get_requests' => [
                  'label' => esc_html__('Cache GET requests', 'cav-utilities'),
                  'type'  => 'checkbox',
               ],
               'css_ver_timestamp' => [
                  'label' => esc_html__('Force refresh of CSS files on update', 'cav-utilities'),
                  'type'  => 'checkbox',
               ],
               'js_ver_timestamp' => [
                  'label' => esc_html__('Force refresh of JS files on update', 'cav-utilities'),
                  'type'  => 'checkbox',
               ],
            ],
         ],

         'links' => [
            'title'       => 'Links\'s page',
            'description' => 'Creates simple page (/links) with a list of links',
            'active'      => true,
            'category'    => 'divulgation',
            'fields'      => [
               'menu' => [
                  'label'   => 'Menu of links',
                  'type'    => 'select',
                  'choices' => self::get_menus(),
               ],
            ],
         ],
         'cdn' => [
            'title'       => 'Simple CDN',
            'description' => 'Swap domain of images, video, audios and another assets',
            'active'      => true,
            'category'    => 'performance',
            'fields'      => [
               'host' => [
                  'label'       => 'Target domain',
                  'description' => 'Only domain, without protocol.',
                  'attrs'       => [
                     'placeholder' => 'example.com',
                  ],
               ],
               'types' => [
                  'label'       => 'File extensions',
                  'description' => 'Separated by space, without dots.',
                  'type'        => 'textarea',
                  'attrs'       => [
                     'placeholder' => 'jpg png gif',
                  ],
               ],
            ],
         ],

         'seo_links' => [
            'title'       => 'PageSpeed Scores',
            'description' => __('Regularly test templates with PageSpeed.', 'cav-utilities'),
            'active'      => true,
            'category'    => 'performance',
            'fields'      => [
               'pagespeed_apikey' => [
                  'label' => __('PageSpeed API Key', 'cav-utilities'),
                  'type'  => 'password',
               ],
               'custom_urls' => [
                  'label'       => __('Custom URLs to check', 'cav-utilities'),
                  'type'        => 'textarea',
                  'description' => __('Add one full URL from this domain per line.', 'cav-utilities'),
                  'attrs'       => [
                     'rows' => '5',
                  ],
               ],
            ],
         ],

         /*
         '' => [
            'title'       => '',
            'description' => '',
            'active'      => false,
            'category'    => '',
            'fields'      => [
               '' => [
                  'label' => '',
                  'type'  => '',
               ],
            ],
         ],
         */
      ];

      $content_types = Utils::get_content_types();

      foreach ($content_types as $content_type => $name) {
         $options['metatags']['fields']["twitter_card_{$content_type}"] = [
            'label'   => sprintf(esc_html__("X's Card Format for %s", 'cav-utilities'), $name),
            'type'    => 'select',
            'default' => 'summary',
            'choices' => [
               'summary'             => 'Summary',
               'summary_large_image' => 'Summary with Large Image',
               'player'              => 'Player',
               'app'                 => 'App',
            ],
         ];
      }

      $custom_options = apply_filters('cav_settings_options', []);
      $all_options    = array_merge($custom_options, $options);

      ksort($all_options);
      uasort($all_options, ['cavWP\Sorters', 'cat_col']);

      return $all_options;
   }

   private static function get_menus()
   {
      $menus = wp_get_nav_menus();

      $return[0] = '(none)';

      foreach ($menus as $menu) {
         $return[$menu->term_id] = $menu->name;
      }

      return $return;
   }
}
