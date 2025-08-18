<?php

namespace cavWP\Models;

use cavWP\Networks\Utils;
use WP_Post;

/**
 * Handles a WP Post.
 */
class Post
{
   public $ID = 0;
   protected $data;

   /**
    * Loads the current or a specific WP Post.
    *
    *  If int, by ID. or WP_Post.
    *
     * @param null|array|int|string|WP_Post $post      If null, loads the current post. If int, by ID. If string, by slug. If array, by title. Or WP_Post.
     * @param string                        $post_type
    *
    * @since 1.0.0
    */
   public function __construct(null|array|int|self|string|WP_Post $post = null, $post_type = 'post')
   {
      if (is_null($post)) {
         $this->data = get_post();

         if (!is_a($this->data, 'WP_Post')) {
            $current = \get_queried_object();

            if (is_a($current, 'WP_Post')) {
               $this->data = $current;
            }
         }
      } elseif (is_numeric($post)) {
         $this->data = \get_post($post);
      } elseif (is_a($post, 'cavWP\Models\Post')) {
         $this->data = $post->data;
      } elseif (is_a($post, 'WP_Post')) {
         $this->data = $post;
      } elseif (is_string($post)) {
         $this->data = \get_page_by_path($post, OBJECT, $post_type);
      } elseif (is_array($post) && !empty($post['field']) && !empty($post['value'])) {
         $field = match ($post['field']) {
            'slug' => 'post_name',
            'title', 'name' => 'post_title',
            'id', 'post_id' => 'ID',
            default => $post['field'],
         };

         $post_type = $post['post_type'] ?? $post['type'] ?? $post_type;

         if ('post_name' === $field) {
            $this->data = \get_page_by_path($post['value'], OBJECT, $post_type);
         } elseif ('ID' === $field) {
            $this->data = \get_post($post['value']);
         } elseif ('post_title' === $field) {
            $get_posts = \get_posts([
               'title'     => $post['value'],
               'post_type' => $post_type,
            ]);

            if (empty($get_posts)) {
               return;
            }

            $this->data = array_shift($get_posts);
         }
      }

      if ($this->data) {
         $this->ID = (int) $this->data->ID;
      }
   }

   /**
    * Gets a propriety value, meta value, or the thumbnail.
    *
    * #### Possible keys
    * - `ID` - the post ID.
    * - `title` -
    * - `content` -
    * - `excerpt` -
    * - `author` -
    * - `link` -
    * - `date` -
    * - `date_gmt` -
    * - `modified` -
    * - `modified_gmt` -
    * - `thumb` -
    * - `thumb_id` -
    * - `category` -
    * - `categories` -
    * - `tag` -
    * - `tags` -
    * - `terms` -
    * - `type` -
    * - `order` -
    * - `parent` -
    * - `password` -
    * - `slug` -
    * - `template` -
    * - `content_filtered` -
    * - `image_alt` -
    * - `image_meta ` -
    * - `mime` (_alias: mime_type, post_mime_type_) - When attachment, the mime type.
    *
    *
    * If a post_meta key is the same of one those, use Post->get_meta().
    *
     * @param string     $key          The propriety or meta to retrieve
     * @param string     $size         Size of the thumbnail. Default: 'thumbnail'.
     * @param array      $attrs        Attributes for the thumbnail. Default: [].
     * @param string     $taxonomy     Define taxonomy. Default: 'category'.
     * @param string     $format       Date format. If empty, gets from configuration site. Default: ''.
     * @param bool       $apply_filter Whether apply standards filter. Default: true.
     * @param bool       $with_html    Whether if include HTML tags. Default: false.
     * @param bool       $sanitize     Whether if apply standard sanitizes. Default: true.
     * @param null|mixed $default
    *
     * @return null|array|string
    *
    * @since 1.0.0
    */
   public function get(string $key, string $size = 'thumbnail', array $attrs = [], string $taxonomy = 'category', string $format = '', bool $apply_filter = true, bool $with_html = false, bool $sanitize = true, mixed $default = null)
   {
      if (empty($this->data)) {
         return;
      }

      // $key ALIAS
      $key = match ($key) {
         'image_id', 'thumb_id', 'thumbnail_id' => '_thumbnail_id',
         'date', 'published', 'publish_date' => 'post_date',
         'id', 'post_id', 'post_ID', 'postID' => 'ID',
         'image_meta', 'image_metadata' => '_wp_attachment_metadata',
         'date_gmt', 'published_gmt' => 'post_date_gmt',
         'mime_type', 'mime' => 'post_mime_type',
         'time_read','time_to_read' => 'reading_time',
         'image', 'thumb' => 'thumbnail',
         'excerpt','summary' => 'post_excerpt',
         'title', 'name' => 'post_title',

         'author'           => 'post_author',
         'category'         => 'post_category',
         'content_filtered' => 'post_content_filtered',
         'content'          => 'post_content',
         'image_alt'        => '_wp_attachment_image_alt',
         'link'             => 'permalink',
         'comments_count'   => 'comment_count',
         'modified_gmt'     => 'post_modified_gmt',
         'modified'         => 'post_modified',
         'order'            => 'menu_order',
         'parent'           => 'post_parent',
         'password'         => 'post_password',
         'slug'             => 'post_name',
         'status'           => 'post_status',
         'tag'              => 'tags_input',
         'template'         => 'page_template',
         'type'             => 'post_type',
         default            => $key,
      };

      if ('categories' === $key) {
         $taxonomy = 'category';
      }

      if ('tags' === $key) {
         $taxonomy = 'post_tag';
      }

      $options = compact('size', 'attrs', 'taxonomy', 'format', 'apply_filter', 'with_html', 'sanitize');

      if (in_array($key, ['ancestors', 'page_template', 'post_category', 'tags_input'])) {
         $value = $this->data->__get($key);
      } elseif (isset($this->data->{$key})) {
         $value = $this->data->{$key};

         if ($sanitize) {
            $value = sanitize_post_field($key, $value, $this->ID, $this->data->filter);
         }

         if ($apply_filter) {
            if ('post_content' === $key) {
               $content = apply_filters('the_content', $value);
               $value   = str_replace(']]>', ']]&gt;', $content);
            }

            if ('post_title' === $key) {
               $value = apply_filters('the_title', $value);
            }

            if ('post_excerpt' === $key) {
               $value = apply_filters('the_excerpt', $value);
            }

            if ('post_date' === $key) {
               $date = get_the_date($format, $this->data);

               if ($with_html) {
                  $attr = get_the_date('c', $this->data);

                  $value = "<time datetime=\"{$attr}\">{$date}</time>";
               } else {
                  $value = $date;
               }
            }

            if ('post_modified' === $key) {
               $date = get_the_modified_date($format, $this->data);

               if ($with_html) {
                  $attr = get_the_modified_date('c', $this->data);

                  $value = "<time datetime=\"{$attr}\">{$date}</time>";
               } else {
                  $value = $date;
               }
            }
         }
      } elseif ('permalink' === $key) {
         $value = esc_url(get_permalink($this->data));
      } elseif ('share' === $key) {
         $type_or_keys = empty($attrs) ? 'share' : $attrs;
         $value        = $this->get_shares($type_or_keys);
      } elseif ('thumbnail' === $key) {
         if (has_post_thumbnail($this->data)) {
            if ($with_html) {
               $value = get_the_post_thumbnail($this->data, $size, $attrs);
            } else {
               $value = get_the_post_thumbnail_url($this->data, $size);
            }
         } else {
            if ($with_html) {
               $value = apply_filters('cav_post_thumbnail_placeholder_img', '', $this->data, $options);
            } else {
               $value = apply_filters('cav_post_thumbnail_placeholder_url', false, $this->data, $options);
            }
         }
      } elseif ('reading_time' === $key) {
         if (empty($this->data->post_content)) {
            return 0;
         }

         $language = get_bloginfo('language');

         $words_per_minute = match ($language) {
            default => 183,
         };

         $content = count(explode(' ', strip_tags($this->data->post_content)));

         $value = ceil($content / $words_per_minute);
      } elseif (in_array($key, ['terms', 'categories', 'tags'])) {
         $terms = get_the_terms($this->data, $taxonomy);

         if ($apply_filter && is_wp_error($terms) || is_bool($terms)) {
            $value = [];
         } elseif ($apply_filter) {
            $value = array_map(fn($term) => new Term($term), $terms);
         } else {
            $value = $terms;
         }
      } elseif (str_starts_with($key, 'author:')) {
         $author = new User($this->data->post_author);

         if ('thumbnail' === $size) {
            $size = 96;
         }

         $value = $author->get(str_replace('author:', '', $key), size: $size, attrs: $attrs);
      } else {
         $value = get_post_meta($this->ID, $key, true);

         if ('' === $value) {
            $value = $default;
         }
      }

      return apply_filters('cavwp_post_get', $value, $key, $this->data, $default, $options);
   }

   /**
    * Retrieves a meta value.
    *
     * @param string $key    The meta key to retrieve. If empty, returns data for all keys. Default: ''.
     * @param bool   $single Whether to return a single value. This parameter has no effect if `$key` is empty. Default: true.
    *
     * @return mixed An array of values if `$single` is false. The value of the meta field if `$single` is true. An empty string if the meta key is not found.
    *
    * @since 1.0.0
    */
   public function get_meta(string $key = '', bool $single = true)
   {
      if (empty($this->data)) {
         return;
      }

      return get_post_meta($this->ID, $key, $single);
   }

   public function get_shares($keys = 'share')
   {
      $all_shares = Utils::get_services($keys);

      $post_shares = [];

      foreach ($all_shares as $key => $share) {
         $title             = urlencode($this->get('title', apply_filter: false));
         $share['share']    = str_replace('%text%', $title, $share['share']);
         $link              = urlencode($this->get('link'));
         $share['share']    = str_replace('%link%', $link, $share['share']);
         $post_shares[$key] = $share;
      }

      return $post_shares;
   }

   /**
    * Checks if the post was modified.
    *
     * @return bool Whether if the post was modified.
    *
    * @since 1.0.0
    */
   public function has_modified()
   {
      if (empty($this->data)) {
         return;
      }

      return substr($this->data->post_date, 0, 10) !== substr($this->data->post_modified, 0, 10);
   }

   public function related($number = 3, $primary = 'term', $secondary = 'category', $exclude = [])
   {
      if (empty($this->data)) {
         return [];
      }

      if (!empty($exclude)) {
         $exclude = array_map(fn($item) => is_numeric($item) ? $item : $item->ID, $exclude);
      }

      $exclude[] = $this->ID;

      $query_args = [
         'post_type'      => $this->data->post_type,
         'posts_per_page' => $number + count($exclude),
      ];

      switch ($primary) {
         case 'term':
            $terms    = $this->get('terms', taxonomy: $secondary, apply_filter: false);
            $terms_ID = array_map(fn($term) => $term->term_id, $terms);

            $query_args['tax_query'] = [[
               'field'    => 'term_id',
               'taxonomy' => $secondary,
               'terms'    => $terms_ID,
            ]];
            break;

         case 'author':
            $query_args['author'] = $this->data->post_author;
            break;

         default:
            break;
      }

      $related  = get_posts($query_args);
      $selected = [];

      foreach ($related as $item) {
         if (count($selected) === $number) {
            break;
         }

         if (in_array($item->ID, $exclude)) {
            continue;
         }

         $selected[] = new self($item);
      }

      return $selected;
   }
}
