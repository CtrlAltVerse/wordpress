<?php

namespace cavWP;

class Columns
{
   public function __construct()
   {
      add_filter('manage_posts_columns', [$this, 'add_columns'], 10, 2);
      add_filter('manage_pages_columns', [$this, 'add_columns'], 10);

      add_action('manage_posts_custom_column', [$this, 'get_column'], 10, 2);
      add_action('manage_pages_custom_column', [$this, 'get_column'], 10, 2);
   }

   public function add_columns($columns, $post_type = 'page')
   {
      if (post_type_supports($post_type, 'page-attributes')) {
         $columns['order'] = __('Order');
      }

      if (post_type_supports($post_type, 'post-formats')) {
         $columns['post-format'] = __('Format');
      }

      return $columns;
   }

   public function get_column($column, $post_ID)
   {
      switch ($column) {
         case 'order':
            echo get_post($post_ID)->menu_order;
            break;

         case 'post-format':
            echo get_post_format($post_ID);
            break;
      }
   }
}
