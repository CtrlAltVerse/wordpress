<?php

namespace cavWP\Models;

use cavWP\Utils;
use WP_Error;

/**
 * Handles a WP Term.
 */
class Term
{
   public $ID = 0;
   protected $data;

   public function __construct($term = null, $taxonomy = 'category')
   {
      if (is_null($term)) {
         $current = \get_queried_object();

         if (is_a($current, 'WP_Term')) {
            $this->data = $current;
         }
      } elseif (is_numeric($term)) {
         $this->data = get_term_by('ID', $term, $taxonomy);
      } elseif (is_a($term, 'WP_Term')) {
         $this->data = $term;
      } elseif (is_a($term, '\cavWP\Models\Term')) {
         $this->data = $term->data;
      } elseif (is_string($term)) {
         $this->data = get_term_by('name', $term, $taxonomy);
      } elseif (is_array($term) && !empty($term['field']) && !empty($term['value'])) {
         $this->data = get_term_by($term['field'], $term['value'], $term['taxonomy'] ?? $term['tax'] ?? $term['type'] ?? $term['type'] ?? $taxonomy);
      }

      if ($this->data) {
         $this->ID = $this->data->term_id;
      } else {
         new WP_Error('term_not_found', 'Term not found');
      }
   }

   public function __get($key)
   {
      return $this->get($key);
   }

   public function __invoke()
   {
      return $this->data;
   }

   public function get(string $key, mixed $default = null, $image_size = null, $image_attrs = [], $apply_filter = true)
   {
      $key = match ($key) {
         'id', 'ID' => 'term_id',
         'title'            => 'name',
         'type'             => 'taxonomy',
         'group'            => 'term_group',
         'term_description' => 'description',
         'summary'          => 'description',
         'taxonomy_id'      => 'term_taxonomy_id',
         'link'             => 'permalink',
         default            => $key,
      };

      if (empty($this->data)) {
         return;
      }

      if (in_array($key, array_keys(get_class_vars(get_class($this->data))))) {
         $value = $this->data->{$key};
      } elseif ('children' === $key) {
         $value = get_terms([
            'parent'   => $this->ID,
            'taxonomy' => $this->data->taxonomy,
            'fields'   => 'ids',
         ]);
      } elseif ('permalink' === $key) {
         $value = get_term_link($this->data);
      } else {
         $value = get_term_meta($this->ID, $key, true);

         if (!empty($image_size)) {
            $image = Utils::maybe_image($value, $image_size, $image_attrs);

            if ($image) {
               return $value = $image;
            }
         }

         if ('' === $value) {
            $value = $default;
         }
      }

      $options = compact('image_size', 'image_attrs');

      if ($apply_filter) {
         $value = apply_filters('cavwp_term_get', $value, $key, $this->data, $default, $options);
      }

      return $value;
   }

   public function get_meta($key = '', $single = true)
   {
      return get_term_meta($this->ID, $key, $single);
   }

   public function get_posts($filters = [])
   {
      $taxonomy = get_taxonomy($this->data->taxonomy);

      $defaults = [
         'posts_per_page' => 4,
         'orderby'        => 'date',
         'order'          => 'DESC',
         'post_type'      => $taxonomy->object_type,
      ];

      $filters['tax_query'] = [[
         'taxonomy' => $this->data->taxonomy,
         'terms'    => $this->data->term_id,
      ]];

      $query_args = wp_parse_args($filters, $defaults);

      return get_posts($query_args);
   }
}
