<?php

namespace cavWP\Models;

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
      } elseif (is_string($term)) {
         $this->data = get_term_by('name', $term, $taxonomy);
      } elseif (is_array($term) && !empty($term['field']) && !empty($term['value'])) {
         $this->data = get_term_by($term['field'], $term['value'], $term['taxonomy'] ?? $term['tax'] ?? $term['type'] ?? $term['type'] ?? $taxonomy);
      }

      if ($this->data) {
         $this->ID = $this->data->term_id;
      } else {
         return new WP_Error('term_not_found', 'Term not found');
      }
   }

   public function get(string $key)
   {
      $key = match ($key) {
         'id', 'ID' => 'term_id',
         'title'            => 'name',
         'type'             => 'taxonomy',
         'group'            => 'term_group',
         'term_description' => 'description',
         'taxonomy_id'      => 'term_taxonomy_id',
         'link'             => 'permalink',
         default            => $key,
      };

      if (isset($this->data->{$key})) {
         return $this->data->{$key};
      }

      if ('permalink' === $key) {
         return get_term_link($this->data);
      }

      $value = get_term_meta($this->ID, $key, true);

      return '' === $value ? null : $value;
   }

   public function get_meta($key = '', $single = true)
   {
      return get_term_meta($this->ID, $key, $single);
   }
}
