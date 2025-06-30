<?php

namespace cavWP\Models;

use WP_Error;
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
   public function __construct(null|array|int|string|WP_Post $post = null, $post_type = 'post')
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
     * @param string $key          The propriety or meta to retrieve
     * @param string $size         Size of the thumbnail. Default: 'thumbnail'.
     * @param array  $attrs        Attributes for the thumbnail. Default: [].
     * @param string $taxonomy     Define taxonomy. Default: 'category'.
     * @param string $format       Date format. If empty, gets from configuration site. Default: ''.
     * @param bool   $apply_filter Whether apply standards filter. Default: true.
     * @param bool   $with_html    Whether if include HTML tags. Default: false.
     * @param bool   $sanitize     Whether if apply standard sanitizes. Default: true.
    *
     * @return null|array|string
    *
    * @since 1.0.0
    */
   public function get(string $key, string $size = 'thumbnail', array $attrs = [], string $taxonomy = 'category', string $format = '', bool $apply_filter = true, bool $with_html = false, bool $sanitize = true)
   {
      if(empty($this->data)){
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
         'author'           => 'post_author',
         'category'         => 'post_category',
         'content_filtered' => 'post_content_filtered',
         'content'          => 'post_content',
         'excerpt'          => 'post_excerpt',
         'image_alt'        => '_wp_attachment_image_alt',
         'image', 'thumb' => 'thumbnail',
         'link'         => 'permalink',
         'modified_gmt' => 'post_modified_gmt',
         'modified'     => 'post_modified',
         'order'        => 'menu_order',
         'parent'       => 'post_parent',
         'password'     => 'post_password',
         'slug'         => 'post_name',
         'status'       => 'post_status',
         'tag'          => 'tags_input',
         'template'     => 'page_template',
         'title', 'name' => 'post_title',
         'type'  => 'post_type',
         default => $key,
      };

      if ($this->data->__isset($key)) {
         return $this->data->__get($key);
      }

      if ('permalink' === $key) {
         return esc_url(get_permalink($this->data));
      }

      if ('thumbnail' === $key) {
         if ($with_html) {
            if (!has_post_thumbnail($this->ID)) {
               return apply_filters('cav_post_thumbnail_placeholder_img', '', $this->data);
            }

            return get_the_post_thumbnail($this->ID, $size, $attrs);
         }

         if (!has_post_thumbnail($this->ID)) {
            return apply_filters('cav_post_thumbnail_placeholder_url', false, $this->data);
         }

         return get_the_post_thumbnail_url($this->ID, $size);
      }

      if (isset($this->data->{$key})) {
         $value = $this->data->{$key};

         if ($sanitize) {
            $value = sanitize_post_field($key, $value, $this->ID, $this->data->filter);
         }

         if (!$apply_filter) {
            return $value;
         }

         if ('post_content' === $key) {
            $content = apply_filters('the_content', $value);

            return str_replace(']]>', ']]&gt;', $content);
         }

         if ('post_title' === $key) {
            return apply_filters('the_title', $value);
         }

         if ('post_excerpt' === $key) {
            return apply_filters('the_excerpt', $value);
         }

         if ('post_date' === $key) {
            $date = get_the_date($format, $this->data);

            if ($with_html) {
               $attr = get_the_date('c', $this->data);

               return "<time datetime=\"{$attr}\">{$date}</time>";
            }

            return $date;
         }

         if ('post_modified' === $key) {
            $date = get_the_modified_date($format, $this->data);

            if ($with_html) {
               $attr = get_the_modified_date('c', $this->data);

               return "<time datetime=\"{$attr}\">{$date}</time>";
            }

            return $date;
         }

         return $value;
      }

      if ('categories' === $key) {
         $taxonomy = 'category';
      }

      if ('tags' === $key) {
         $taxonomy = 'post_tag';
      }

      if (in_array($key, ['terms', 'categories', 'tags'])) {
         $terms = get_the_terms($this->data, $taxonomy);

         if ($apply_filter && is_wp_error($terms) || is_bool($terms)) {
            return [];
         }

         return $terms;
      }

      $value = get_post_meta($this->ID, $key, true);

      return '' === $value ? null : $value;
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
      if(empty($this->data)){
         return;
      }

      return get_post_meta($this->ID, $key, $single);
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
      if(empty($this->data)){
         return;
      }

      return $this->data->post_date !== $this->data->post_modified;
   }
}
