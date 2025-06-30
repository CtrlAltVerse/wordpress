<?php

namespace cavWP\SEO_Links;

use cavWP\Services\PageSpeed;

/**
 * TODO:
 * full redo:
 * quando tema muda
 *
 * parcial redo:
 * quando usuario/term/post selecionado é deletado
 * quando há menos/novos templates
 *
 * find URL para index.php (já existentes na ordem de probabilidade)
 *
 * @ignore
 */
class Register
{
   private $options            = [];
   private $possible_templates = [
      [
         'entity'      => 'front_page',
         'entity_type' => 'post',
         'regex'       => '^front-page$',
         'order'       => 1,
      ],
      [
         'entity'      => 'privacy_policy',
         'entity_type' => 'post',
         'regex'       => '^privacy-policy$',
         'order'       => 1,
      ],
      [
         'entity' => 'user',
         'regex'  => '^author-(.*)',
         'order'  => 10,
      ],
      [
         'entity' => 'user',
         'regex'  => '^author$',
         'order'  => 15,
      ],
      [
         'entity' => 'term',
         'regex'  => '^category-(.*)',
         'order'  => 10,
      ],
      [
         'entity' => 'term',
         'regex'  => '^category$',
         'order'  => 15,
      ],
      [
         'entity'      => 'term',
         'entity_type' => 'term',
         'regex'       => '^taxonomy-(.*)',
         'order'       => 20,
      ],
      [
         'entity' => 'term',
         'regex'  => '^taxonomy$',
         'order'  => 25,
      ],
      [
         'entity' => 'date',
         'regex'  => '^date$',
         'order'  => 10,
      ],
      [
         'entity' => 'term',
         'regex'  => '^tag-(.*)',
         'order'  => 10,
      ],
      [
         'entity' => 'term',
         'regex'  => '^tag$',
         'order'  => 15,
      ],
      [
         'entity'      => 'archive',
         'entity_type' => 'post_type',
         'regex'       => '^archive-(.*)',
         'order'       => 80,
      ],
      [
         'entity'      => 'archive',
         'entity_type' => 'post_type,term,user,date',
         'regex'       => '^archive$',
         'order'       => 85,
      ],
      [
         'entity' => 'post',
         'regex'  => '^single-(.*)',
         'order'  => 20,
      ],
      [
         'entity' => 'post',
         'regex'  => '^single$',
         'order'  => 25,
      ],
      [
         'entity' => 'home',
         'regex'  => '^home$',
         'order'  => 10,
      ],
      [
         'entity' => 'post',
         'regex'  => '^page-(.*)',
         'order'  => 20,
      ],
      [
         'entity' => 'post',
         'regex'  => '^page$',
         'order'  => 25,
      ],
      [
         'entity' => 'search',
         'regex'  => '^search$',
         'order'  => 80,
      ],
      [
         'entity' => 'post',
         'regex'  => '^singular$',
         'order'  => 80,
      ],
      [
         'entity' => 'index',
         'regex'  => '^index$',
         'order'  => 99,
      ],
   ];
   private $post_types = [];
   private $taxonomies = [];
   private $templates  = [];
   private $urls       = [];

   public function __construct()
   {
      add_action('init', [$this, 'creates_table']);

      add_action('cav_seo_links_analysis', [$this, 'analysis']);

      if (!wp_next_scheduled('cav_seo_links_analysis')) {
         wp_schedule_event(strtotime('Saturday 04:30'), 'weekly', 'cav_seo_links_analysis', [], true);
      }
   }

   public function analysis(): void
   {
      $links = Utils::get_all_links();

      if (empty($links)) {
         $links = $this->populate_links();
      }

      $PageSpeed = new PageSpeed();

      foreach ($links as $link) {
         $url    = $link['url'];
         $_url   = str_replace('dbands.local', 'dbands.com.br', $link['url']);
         $report = $PageSpeed->run_test($_url);
         Utils::save_report($url, $report);
      }
   }

   public function creates_table(): void
   {
      if ((bool) \get_option('cav-seo_links-table_created')) {
         return;
      }

      if (!function_exists('dbDelta')) {
         require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      }

      global $wpdb;

      $table_name      = Utils::get_table_name($wpdb->prefix);
      $charset_collate = $wpdb->get_charset_collate();

      $sql_query = <<<SQL
      CREATE TABLE IF NOT EXISTS {$table_name} (
         `analysis_ID` BIGINT(30) NOT NULL AUTO_INCREMENT,
         `url` TEXT NOT NULL,
         `strategy` VARCHAR(7) NOT NULL,
         `report` LONGTEXT NOT NULL,
         `screenshot` MEDIUMTEXT NOT NULL,
         `datetime` DATETIME NOT NULL,
         PRIMARY KEY (analysis_ID),
         INDEX (analysis_ID)
      ) {$charset_collate} ENGINE=InnoDB;
      SQL;

      \dbDelta($sql_query);

      if (empty($wpdb->last_error)) {
         \update_option('cav-seo_links-table_created', current_time('mysql', true), 'yes');
      }
   }

   private function add_url($template, $url, $id = null, $group = false)
   {
      if (false === $url) {
         return false;
      }

      $type = $template['entity_type'] ?? $template['entity'];

      if (!empty($id)) {
         $current_IDs = $this->get_filter_urls($type);

         if (in_array($id, $current_IDs)) {
            return false;
         }

         if ('post' === $type) {
            $status = get_post_status($id);

            if (false === $status || 'publish' !== $status) {
               return false;
            }
         }

         if ('term' === $type) {
            $term = get_term($id);

            if (is_null($term) || is_wp_error($term) || 0 === $term->count) {
               return false;
            }
         }

         if ('post_type' === $type) {
            $post_type = wp_count_posts($id);

            if (empty($post_type) || 0 === $post_type->publish) {
               return false;
            }
         }

         if ('user' === $type) {
            if (0 === count_user_posts($id, 'post', true)) {
               return false;
            }
         }

         $this->urls[] = [
            'id'    => $id,
            'type'  => $type,
            'group' => $group,
            'url'   => $url,
            'file'  => $template['file'],
         ];

         return true;
      }

      $this->urls[] = [
         'url'  => $url,
         'type' => $type,
         'file' => $template['file'],
      ];

      return true;
   }

   private function find_templates(): void
   {
      $post_templates = wp_get_theme()->get_post_templates();

      foreach ($post_templates as $post_templates) {
         $post_templates = array_keys($post_templates);

         foreach ($post_templates as $post_template) {
            $templates[] = [
               'file'        => $post_template,
               'entity'      => 'template',
               'entity_type' => 'post',
               'order'       => 9,
            ];
         }
      }

      $theme_folder       = wp_get_theme()->get_template_directory();
      $template_folders[] = '';

      if (get_option('cav-theme-pages_template')) {
         $template_folders[] = 'pages';
      }

      foreach ($template_folders as $folder) {
         $files = scandir($theme_folder . DIRECTORY_SEPARATOR . $folder);

         foreach ($files as $file) {
            if (empty($folder)) {
               $file_path = $theme_folder . DIRECTORY_SEPARATOR . $file;
            } else {
               $file_path = implode(DIRECTORY_SEPARATOR, [$theme_folder, $folder, $file]);
               $file      = $folder . '/' . $file;
            }

            if (is_dir($file_path) && !file_exists($file_path . DIRECTORY_SEPARATOR . '_index.php')) {
               continue;
            }

            $_template = str_replace(['pages/', '.php'], '', $file);

            foreach ($this->possible_templates as $template) {
               preg_match("/{$template['regex']}/", $_template, $matches);

               if (empty($matches)) {
                  continue;
               }

               $templates[] = array_merge([
                  'file'     => $file,
                  'template' => preg_replace("/{$template['regex']}/", '${1}', $_template),
               ], $template);
            }
         }
      }

      usort($templates, ['cavWP\Sorters', 'order_then_file_cols']);

      $this->templates = $templates;
   }

   private function find_urls(): void
   {
      foreach ($this->templates as $template) {
         // home
         if ('home' === $template['entity']) {
            if ('page' === $this->options['show_on_front']) {
               $posts_page_ID           = $this->options['page_for_posts'];
               $template['entity_type'] = 'post';
               $this->add_url($template, get_permalink($posts_page_ID), $posts_page_ID);
            } else {
               $this->add_url($template, home_url());
            }

         // search
         } elseif ('search' === $template['entity']) {
            $this->add_url($template, get_search_link('do'));

         // front_page
         } elseif ('front_page' === $template['entity']) {
            if ('posts' === $this->options['show_on_front']) {
               continue;
            }

            $front_page_ID = $this->options['front_page'];
            $this->add_url($template, get_permalink($front_page_ID), $front_page_ID);

         // privacy_policy
         } elseif ('privacy_policy' === $template['entity']) {
            $privacy_page_ID = $this->options['privacy_policy'];
            $this->add_url($template, get_permalink($privacy_page_ID), $privacy_page_ID);

         // others
         } else {
            call_user_func([$this, "get_{$template['entity']}_url"], $template);
         }
      }

      usort($this->urls, ['cavWP\Sorters', 'file_col']);
   }

   private function get_archive_url($template)
   {
      $custom_pts = array_diff($this->post_types, ['post', 'page']);

      if ('^archive-(.*)' === $template['regex']) {
         foreach ($custom_pts as $post_type) {
            if ($post_type === $template['template']) {
               $url = get_post_type_archive_link($post_type);
               $id  = $post_type;
               break;
            }
         }
      } elseif ('^archive$' === $template['regex']) {
         $has_date = $this->get_filter_urls('date', '', 'type');

         if (empty($has_date)) {
            if ($this->get_date_url($template)) {
               return;
            }
         }

         if ($this->get_term_url($template)) {
            return;
         }

         $has_author = $this->get_filter_urls('user', false, 'type');

         if (!$has_author && $this->get_user_url($template)) {
            return;
         }

         $already_archive_cpts = $this->get_filter_urls('post_type');
         $remaining_post_types = array_diff($custom_pts, $already_archive_cpts);

         if (!empty($remaining_post_types)) {
            $post_type = $remaining_post_types[0];
            $url       = get_post_type_archive_link($post_type);
            $id        = $post_type;
         }
      }

      if (empty($url)) {
         return;
      }

      return $this->add_url($template, $url, $id);
   }

   private function get_date_url($template)
   {
      $latest_post = get_posts([
         'post_status'    => 'publish',
         'posts_per_page' => $this->options['posts_per_page'],
      ]);

      if (empty($latest_post)) {
         return false;
      }

      if ('archive' === $template['entity']) {
         $template['entity_type'] = 'archive';
      }

      return $this->add_url($template, home_url(substr($latest_post[0]->post_date, 0, 4)));
   }

   private function get_filter_urls($type, $group = null, $col = 'id')
   {
      $currents = [];

      foreach ($this->urls as $url) {
         if ('all' !== $type) {
            if (!isset($url['type']) || $type !== $url['type']) {
               continue;
            }
         }

         if (!is_null($group) && (isset($url['group']) && $group !== $url['group'])) {
            continue;
         }

         $currents[] = $url[$col];
      }

      return $currents;
   }

   private function get_index_url($template): void {}

   private function get_post_url($template)
   {
      $group = false;

      if ('^single-(.*)' === $template['regex']) {
         foreach ($this->post_types as $post_type) {
            if (str_starts_with($template['template'], "{$post_type}-")) {
               $post_slug = str_replace("{$post_type}-", '', $template['template']);
               $_post     = get_page_by_path($post_slug, OBJECT, $post_type);

               if (is_null($_post)) {
                  return;
               }
               break;
            }

            if ($template['template'] === $post_type) {
               $args['post_type'] = $post_type;
               break;
            }
         }
      } elseif ('^single$' === $template['regex']) {
         $already_post_types = $this->get_filter_urls('post', null, 'group');

         $remaining_post_types = array_diff($this->post_types, [...$already_post_types, 'page']);

         if (empty($remaining_post_types)) {
            return;
         }

         $args['post_type'] = $remaining_post_types;
      } elseif ('^page-(.*)' === $template['regex']) {
         $slug_or_id = $template['template'];
         $_post      = get_page_by_path($slug_or_id);

         if (is_null($_post) && is_numeric($slug_or_id)) {
            $_post = get_post($slug_or_id);

            if (empty($_post)) {
               return;
            }
         }

         if (isset($_post) && 'page' !== $_post->post_type) {
            return;
         }
      } elseif ('^page$' === $template['regex']) {
         $args['post_type'] = 'page';
      } elseif ('^singular$' === $template['regex']) {
         $already_post_types   = $this->get_filter_urls('post', '', 'group');
         $remaining_post_types = array_diff($this->post_types, $already_post_types);

         if ($remaining_post_types) {
            return;
         }

         $args['post_type'] = $remaining_post_types;
      }

      if (isset($args)) {
         $_posts = get_posts(wp_parse_args($args, [
            'exclude'     => $this->get_filter_urls('post'),
            'post_status' => 'publish',
            'numberposts' => 1,
            'orderby'     => 'rand',
         ]));

         if (empty($_posts)) {
            return;
         }

         $_post = $_posts[0];
         $group = $_post->post_type;
      }

      if (empty($_post)) {
         return;
      }

      return $this->add_url($template, get_permalink($_post), $_post->ID, $group);
   }

   private function get_template_url($template)
   {
      $_posts = get_posts([
         'exclude'     => $this->get_filter_urls('post'),
         'post_status' => 'publish',
         'numberposts' => 1,
         'orderby'     => 'rand',
         'meta_query'  => [[
            'key'   => '_wp_page_template',
            'value' => $template['file'],
         ]],
      ]);

      if (empty($_posts)) {
         return;
      }

      $_post = $_posts[0];

      return $this->add_url($template, get_permalink($_post), $_post->ID);
   }

   private function get_term_url($template)
   {
      $custom_taxs = array_diff($this->taxonomies, ['category', 'post_tag']);
      $group       = false;

      if ('^category-(.*)' === $template['regex']) {
         $term_or_id = $template['template'];
         $taxonomy   = 'category';

         $term = get_term_by('slug', $term_or_id, $taxonomy);

         if (empty($term) && is_numeric($term_or_id)) {
            $term = get_term($term_or_id, $taxonomy);

            if (empty($term)) {
               return;
            }
         }
      } elseif ('^category$' === $template['regex']) {
         $taxonomy         = 'category';
         $args['taxonomy'] = $taxonomy;
      } elseif ('^tag-(.*)' === $template['regex']) {
         $term_or_id = $template['template'];
         $taxonomy   = 'post_tag';

         $term = get_term_by('slug', $term_or_id, $taxonomy);

         if (empty($term) && is_numeric($term_or_id)) {
            $term = get_term($term_or_id, $taxonomy);

            if (empty($term)) {
               return;
            }
         }
      } elseif ('^tag$' === $template['regex']) {
         $taxonomy         = 'post_tag';
         $args['taxonomy'] = $taxonomy;
      } elseif ('^taxonomy-(.*)' === $template['regex']) {
         if (empty($custom_taxs)) {
            return;
         }

         foreach ($custom_taxs as $taxonomy) {
            if (str_starts_with($template['template'], "{$taxonomy}-")) {
               $term_slug = str_replace("{$taxonomy}-", '', $template['template']);
               $term      = get_term_by('slug', $term_slug, $taxonomy);

               if (empty($term) || is_wp_error($term)) {
                  return;
               }
               break;
            }

            if ($template['template'] === $taxonomy) {
               $args['taxonomy'] = $taxonomy;
               break;
            }
         }
      } elseif ('^taxonomy$' === $template['regex']) {
         if (empty($custom_taxs)) {
            return;
         }

         $already_taxonomies   = $this->get_filter_urls('term', null, 'group');
         $remaining_taxonomies = array_diff($custom_taxs, $already_taxonomies);

         if (empty($remaining_taxonomies)) {
            return;
         }

         $args['taxonomy'] = $remaining_taxonomies;
      }

      if ('archive' === $template['entity']) {
         $template['entity_type'] = 'archive';
         $already_taxonomies      = $this->get_filter_urls('term', null, 'group');
         $remaining_taxonomies    = array_diff($this->taxonomies, $already_taxonomies);

         if (empty($remaining_taxonomies)) {
            return;
         }
         $args['taxonomy'] = $remaining_taxonomies;
      }

      if (isset($args)) {
         $terms = get_terms(wp_parse_args($args, [
            'exclude'    => $this->get_filter_urls('term'),
            'hide_empty' => true,
            'number'     => 1,
            'orderby'    => 'count',
            'order'      => 'DESC',
         ]));

         if (empty($terms)) {
            return;
         }

         $term     = $terms[0];
         $taxonomy = $term->taxonomy;
         $group    = $term->taxonomy;
      }

      if (empty($term)) {
         return false;
      }

      return $this->add_url($template, get_term_link($term, $taxonomy), $term->term_id, $group);
   }

   private function get_user_url($template)
   {
      if (str_starts_with($template['regex'], '^author-')) {
         $user_or_id = $template['template'];

         $user = get_user_by('slug', $user_or_id);

         if (empty($user) && is_numeric($user_or_id)) {
            $user = get_user_by('ID', $user_or_id);

            if (empty($user)) {
               return false;
            }
         }
      } elseif ('^author$' === $template['regex']) {
         $args = [];
      }

      if ('archive' === $template['entity']) {
         $template['entity_type'] = 'archive';
         $args                    = [];
      }

      if (isset($args)) {
         $users = get_users(wp_parse_args($args, [
            'exclude'             => $this->get_filter_urls('user'),
            'number'              => 1,
            'fields'              => 'all',
            'orderby'             => 'post_count',
            'order'               => 'DESC',
            'has_published_posts' => ['post'],
         ]));

         if (empty($users)) {
            return false;
         }

         $user = $users[0];
      }

      if (empty($user)) {
         return false;
      }

      return $this->add_url($template, get_author_posts_url($user->ID), $user->ID);
   }

   private function populate_links()
   {
      $urls = get_option('cav-seo_links-unique_urls', []);

      if (is_array($urls) && !empty($urls)) {
         return;
      }

      $this->set_vars();
      $this->find_templates();
      $this->find_urls();

      update_option('cav-seo_links-unique_urls', $this->urls, 'no');

      return Utils::get_all_links();
   }

   private function set_vars(): void
   {
      $this->options = [
         'show_on_front'  => \get_option('show_on_front', 'posts'),
         'front_page'     => (int) \get_option('page_on_front', 0),
         'page_for_posts' => (int) \get_option('page_for_posts', 0),
         'posts_per_page' => (int) \get_option('posts_per_page', 10),
         'privacy_policy' => (int) \get_option('wp_page_for_privacy_policy', 0),
      ];

      $this->post_types = array_diff(array_values(\get_post_types([
         'public' => true,
      ])), ['attachment']);

      usort($this->post_types, ['cavWP\Sorters', 'length_desc']);

      $this->taxonomies = array_diff(array_values(\get_taxonomies([
         'public' => true,
      ])), ['post_format', 'page_type']);

      usort($this->taxonomies, ['cavWP\Sorters', 'length_desc']);
   }
}
