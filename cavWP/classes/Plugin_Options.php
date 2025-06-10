<?php

namespace cavWP;

use cavWP\Services\Facebook;
use cavWP\Services\Threads;

/**
 * @ignore
 */
final class Plugin_Options
{
   public static function get_categories($key = '')
   {
      $main_categories = [
         'seo' => [
            'label' => esc_html__('SEO', 'cavwp'),
            'color' => '#4b9fff',
         ],
         'performance' => [
            'label' => esc_html__('Performance', 'cavwp'),
            'color' => '#ff8904',
         ],
         'divulgation' => [
            'label' => esc_html__('Divulgation', 'cavwp'),
            'color' => '#c800de',
         ],
         'dashboard' => [
            'label' => esc_html__('Dashboard', 'cavwp'),
            'color' => '#3429cd',
         ],
         'theme' => [
            'label' => esc_html__('Theme', 'cavwp'),
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
      $Facebook = new Facebook();
      $Threads  = new Threads();

      $options = [
         'metatags' => [
            'title'       => 'Metatags',
            'description' => esc_html__('Adds metatags for SEO and social share.', 'cavwp'),
            'category'    => 'seo',
            'active'      => true,
            'fields'      => [
               'theme_color' => [
                  'label'       => esc_html__('Site color', 'cavwp'),
                  'description' => esc_html__('Used on Chrome in Android and Windows 8 Pin.', 'cavwp'),
                  'type'        => 'color',
               ],
               'fb_app_id' => [
                  'label' => esc_html__('Facebook App ID', 'cavwp'),
                  'type'  => 'number',
               ],
               'twitter_site' => [
                  'label' => esc_html__("X's Site Account", 'cavwp'),
               ],
            ],
         ],
         'sitemaps' => [
            'title'       => 'Sitemaps, robots.txt and ads.txt',
            'description' => esc_html__('Creates adicional sitemaps and configure content for robots.txt and ads.txt.', 'cavwp'),
            'category'    => 'seo',
            'active'      => false,
            'fields'      => [
               'google_news' => [
                  'label'       => 'Google News',
                  'description' => sprintf(esc_html__('Creates %s.', 'cavwp'), 'sitemap-google-news.xml'),
                  'type'        => 'checkbox',
               ],
               'adds_robots_txt' => [
                  'label' => esc_html__('Custom content for robots.txt', 'cavwp'),
                  'type'  => 'textarea',
               ],
               'adds_ads_txt' => [
                  'label' => esc_html__('Custom content for ads.txt', 'cavwp'),
                  'type'  => 'textarea',
               ],
            ],
         ],
         'autoshare' => [
            'title'       => 'Auto Share',
            'description' => esc_html__('', 'cavwp'),
            'active'      => true,
            'category'    => 'divulgation',
            'fields'      => [
               'post_types' => [
                  'label'   => esc_html__('Post types to publish', 'cavwp'),
                  'type'    => 'select',
                  'choices' => Utils::get_content_types('post_type'),
                  'default' => [],
                  'attrs'   => [
                     'multiple' => true,
                  ],
               ],
               'twitter_on' => [
                  'label' => esc_html__('Auto publish new posts on X', 'cavwp'),
                  'type'  => 'checkbox',
               ],
               'twitter_api_key' => [
                  'label' => 'X API Key',
                  'type'  => 'password',
               ],
               'twitter_api_key_secret' => [
                  'label' => 'X API Key Secret',
                  'type'  => 'password',
               ],
               'twitter_access_token' => [
                  'label' => 'X Access Token',
                  'type'  => 'password',
               ],
               'twitter_access_token_secret' => [
                  'label' => 'X Access Token Secret',
                  'type'  => 'password',
               ],
               'fb_on' => [
                  'label' => esc_html__('Auto publish new posts on Facebook', 'cavwp'),
                  'type'  => 'checkbox',
               ],
               'fb_page_id' => [
                  'label' => 'Facebook Page ID',
                  'type'  => 'number',
               ],
               'fb_api_id' => [
                  'label' => 'Facebook App ID',
                  'type'  => 'number',
               ],
               'fb_client_secret' => [
                  'label' => 'Facebook App Client Secret',
                  'type'  => 'password',
               ],
               'fb_renew_token' => [
                  'label'   => esc_html__('Get or Renew Page Access Token', 'cavwp'),
                  'type'    => 'link',
                  'default' => $Facebook->get_link_token(),
                  'attrs'   => [
                     'target' => '_blank',
                  ],
               ],
               'threads_on' => [
                  'label' => esc_html__('Auto publish new posts on Threads', 'cavwp'),
                  'type'  => 'checkbox',
               ],
               'threads_app_ID' => [
                  'label' => 'Threads App ID',
                  'type'  => 'number',
               ],
               'threads_app_client_secret' => [
                  'label' => 'Threads App Client Secret',
                  'type'  => 'password',
               ],
               'threads_renew_token' => [
                  'label'   => esc_html__('Get Access Token', 'cavwp'),
                  'type'    => 'link',
                  'default' => $Threads->get_link_token(),
                  'attrs'   => [
                     'target' => '_blank',
                  ],
               ],
            ],
         ],
         // 'minify' => [
         //    'title'        => 'Minify HTML',
         //    'description'  => '',
         //    'active'       => true,
         //    'category'     => 'performance',
         //    'fields'       => [
         //       'js_on' => [
         //          'label' => esc_html__('Minify JS inline', 'cavwp'),
         //          'type'  => 'checkbox',
         //       ],
         //       'css_on' => [
         //          'label' => esc_html__('Minify CSS inline', 'cavwp'),
         //          'type'  => 'checkbox',
         //       ],
         //    ],
         // ],
         'opensearch' => [
            'title'       => 'OpenSearch',
            'description' => esc_html__('Creates opensearch.osdx file', 'cavwp'),
            'active'      => true,
            'category'    => 'seo',
            'fields'      => [
               'longname' => [
                  'label' => esc_html__('Long name', 'cavwp'),
                  'attrs' => [
                     'maxlength' => 48,
                  ],
               ],
               'tags' => [
                  'label'       => esc_html__('Tags', 'cavwp'),
                  'description' => esc_html__('Keywords separated by space', 'cavwp'),
                  'attrs'       => [
                     'maxlength' => 256,
                  ],
               ],
               'contact' => [
                  'label' => esc_html__('E-mail for contact', 'cavwp'),
                  'type'  => 'email',
               ],
            ],
         ],
         'caches' => [
            'title'       => 'Cache',
            'description' => '',
            'active'      => false,
            'category'    => 'performance',
            'fields'      => [
               'get_requests' => [
                  'label' => esc_html__('Cache GET requests', 'cavwp'),
                  'type'  => 'checkbox',
               ],
               'css_ver_timestamp' => [
                  'label' => esc_html__('Force refresh of CSS files on update', 'cavwp'),
                  'type'  => 'checkbox',
               ],
               'js_ver_timestamp' => [
                  'label' => esc_html__('Force refresh of JS files on update', 'cavwp'),
                  'type'  => 'checkbox',
               ],
            ],
         ],
         'theme' => [
            'title'       => esc_html__('Theme Options', 'cavwp'),
            'description' => '',
            'active'      => false,
            'category'    => 'theme',
            'fields'      => [
               'title_tag' => [
                  'label' => esc_html__('Adds Title Tag support', 'cavwp'),
                  'type'  => 'checkbox',
               ],
               'add_supports' => [
                  'label'       => 'Adds theme supports',
                  'description' => 'Adds supports for post-thumbnails, responsive-embed and html5.',
                  'type'        => 'checkbox',
               ],
               'pages_template' => [
                  'label'       => 'Pages Templates',
                  'description' => esc_html__('Search /pages theme folder for hierarchies templates.', 'cavwp'),
                  'type'        => 'checkbox',
               ],
               'remove_tags' => [
                  'label' => esc_html__('Remove WP default tags', 'cavwp'),
                  'type'  => 'checkbox',
               ],
            ],
         ],
         'dashboard' => [
            'title'       => esc_html__('Dashboard Options', 'cavwp'),
            'description' => '',
            'category'    => 'dashboard',
            'active'      => false,
            'fields'      => [
               'show_image_sizes' => [
                  'label' => esc_html__('Show all images sizes on post editor (Gutenberg)', 'cavwp'),
                  'type'  => 'checkbox',
               ],
               'acf_show_key' => [
                  'label' => esc_html__('Show ACF keys on UI', 'cavwp'),
                  'type'  => 'checkbox',
               ],
               'change_schema_colors' => [
                  'label' => esc_html__('Change dashboards colors by environment', 'cavwp'),
                  'type'  => 'checkbox',
               ],
               'hide_bar' => [
                  'label' => 'Hide admin bar',
                  'type'  => 'checkbox',
               ],
               'new_user_mail' => [
                  'label' => 'Adds more information to new user email',
                  'type'  => 'checkbox',
               ],
            ],
         ],
         'smtp' => [
            'title'       => 'SMTP',
            'description' => 'Configures SMTP for sending emails.',
            'active'      => true,
            'category'    => 'dashboard',
            'fields'      => [
               'host' => [
                  'label' => 'Host',
                  'type'  => 'text',
               ],
               'port' => [
                  'label'   => 'Port',
                  'type'    => 'number',
                  'default' => 465,
               ],
               'user' => [
                  'label' => 'User',
                  'type'  => 'text',
               ],
               'password' => [
                  'label' => 'Password',
                  'type'  => 'password',
               ],
               'secure' => [
                  'label'   => 'Secure',
                  'type'    => 'select',
                  'choices' => [
                     'tls' => 'TLS',
                     'ssl' => 'SSL',
                  ],
               ],
               'test' => [
                  'label'   => esc_html__('Send a test mail', 'cavwp'),
                  'type'    => 'link',
                  'default' => home_url('?cav=send_test_mail'),
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
      ];

      $content_types = Utils::get_content_types();

      foreach ($content_types as $content_type => $name) {
         $options['metatags']['fields']["twitter_card_{$content_type}"] = [
            'label'   => sprintf(esc_html__("X's Card Format for %s", 'cavwp'), $name),
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

      return $all_options;
   }

   private static function get_menus()
   {
      $menus = wp_get_nav_menus();

      $return = [];

      foreach ($menus as $menu) {
         $return[$menu->term_id] = $menu->name;
      }

      return $return;
   }
}
