<?php

namespace cavWP;

use cavWP\Models\Post;
use cavWP\Models\Term;
use cavWP\Models\User;
use WP_Locale;
use WP_Post_Type;

/**
 * @ignore
 */
final class Metatags
{
   public function __construct()
   {
      \add_action('cav_head_metas', [$this, 'prints_tags']);
   }

   public function get_user_tags()
   {
      if (!\is_author()) {
         return [];
      }

      $User  = new User();
      $title = $User->get('display_name');

      $metas['og:title'] = $title;
      $metas['og:type']  = 'profile';

      if ($first_name = $User->get('first_name')) {
         $metas['og:profile:first_name'] = $first_name;
      }

      if ($last_name = $User->get('last_name')) {
         $metas['og:profile:last_name'] = $last_name;
      }

      if ($username = $User->get('username')) {
         $metas['og:profile:username'] = $username;
      }

      $metas['og:url']      = $User->get('link');
      $metas['description'] = [
         'type'  => 'name',
         'value' => sprintf(esc_attr__('See all publications of %s', 'cav-utilities'), $title),
      ];

      return $metas;
   }

   public function prints_tags(): void
   {
      $metatags = array_merge(
         $this->get_global_tags(),
         $this->get_home_tags(),
         $this->get_singular_tags(),
         $this->get_user_tags(),
         $this->get_term_tags(),
         $this->get_date_tags(),
         $this->get_post_type_tags(),
         $this->get_search_tags(),
      );

      /**
       * An array of metatags to prints into head.
       *
       * Accepts the follow formats:
       *
       * $property => $content
       * <meta property="$property" content="$content">
       *
       * $key => ['type'=> 'name', 'value'=> $value]
       * <meta $name="$key" content="$value">
       */
      $metatags = apply_filters('cav_head_metatags', $metatags);

      if (isset($metatags['description']) && !isset($metatags['og:description'])) {
         $metatags['og:description'] = $metatags['description']['value'] ?? $metatags['description']['content'] ?? $metatags['description'];
      }

      if (isset($metatags['author']) && !isset($metatags['og:article:author'])) {
         $metatags['og:article:author'] = $metatags['author']['value'] ?? $metatags['author']['content'] ?? $metatags['author'];
      }

      if (isset($metatags['og:image']) && is_numeric($metatags['og:image'])) {
         $attachment = get_post($metatags['og:image']);

         if ($attachment) {
            $metatags['og:image']      = \wp_get_attachment_image_url($metatags['og:image'], 'full');
            $metatags['og:image:type'] = $attachment->post_mime_type;
            $metatags['og:image:alt']  = \get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);

            $attachment_metadata = \wp_get_attachment_metadata($attachment->ID);

            $metatags['og:image:width']  = $attachment_metadata['width']  ?? false;
            $metatags['og:image:height'] = $attachment_metadata['height'] ?? false;
         }
      }

      foreach ($metatags as $key => $data) {
         if (is_numeric($key)) {
            continue;
         }

         $content = $data['value'] ?? $data['content'] ?? $data;

         if (empty($content)) {
            continue;
         }

         $type = $data['type'] ?? 'property';

         if (is_array($content)) {
            foreach ($content as $value) {
               $value = strip_tags($value);
               echo "<meta {$type}=\"{$key}\" content=\"{$value}\" />";
            }
            continue;
         }

         $content = strip_tags($content);
         echo "<meta {$type}=\"{$key}\" content=\"{$content}\" />";
      }
   }

   private function get_current_content_type()
   {
      if (is_home() || is_front_page()) {
         return 'home';
      }

      if (is_singular()) {
         return get_queried_object()->post_type;
      }

      if (is_tax() || is_category() || is_tag()) {
         return get_queried_object()->taxonomy;
      }

      return false;
   }

   private function get_date_tags()
   {
      if (!\is_date()) {
         return [];
      }

      $day = get_query_var('day', null);

      if (!empty($day)) {
         $dates[] = $day;
      }

      $month = get_query_var('monthnum', null);

      if (!empty($month)) {
         $wp_locale = new WP_Locale();
         $dates[]   = $wp_locale->get_month_genitive($month);
      }

      $dates[] = get_query_var('year');
      $title   = implode(' ' . esc_attr__('of') . ' ', $dates);

      $metas['og:title'] = $title;

      $metas['description'] = [
         'type'  => 'name',
         'value' => sprintf(esc_attr__('See all publications of %s', 'cav-utilities'), $title),
      ];

      $metas['og:url'] = Utils::get_date_link(true, true, true);

      return $metas;
   }

   private function get_global_tags()
   {
      $metas['og:locale']    = str_replace('-', '_', get_bloginfo('language'));
      $metas['og:site_name'] = get_bloginfo('name');
      $metas['og:type']      = 'website';

      if ($site_icon_ID = get_option('site_icon')) {
         $metas['msapplication-TileImage'] = [
            'type'    => 'name',
            'content' => wp_get_attachment_image_url($site_icon_ID, 'full'),
         ];
      }

      if ($fb_app_id = get_option('cav-metatags-fb_app_id')) {
         $metas['fb:app_id'] = $fb_app_id;
      }

      if ($color = get_option('cav-metatags-theme_color')) {
         $metas['msapplication-TileColor'] = [
            'type'  => 'name',
            'value' => $color,
         ];
         $metas['theme-color'] = [
            'type'  => 'name',
            'value' => $color,
         ];
      }

      if ($twitter_site = get_option('cav-metatags-twitter_site')) {
         $metas['twitter:site'] = [
            'type'  => 'name',
            'value' => '@' . ltrim($twitter_site, '@'),
         ];
      }

      $current_content_type = $this->get_current_content_type();

      if ($current_content_type && $twitter_card = get_option("cav-metatags-twitter_card_{$current_content_type}")) {
         $metas['twitter:card'] = [
            'type'  => 'name',
            'value' => $twitter_card,
         ];
      }

      return $metas;
   }

   private function get_home_tags()
   {
      if (!\is_home()) {
         return [];
      }

      $metas['og:title']    = \get_bloginfo('name');
      $metas['description'] = [
         'type'  => 'name',
         'value' => \get_bloginfo('description'),
      ];
      $metas['og:url'] = \home_url();

      return $metas;
   }

   private function get_post_type_tags()
   {
      if (!\is_post_type_archive()) {
         return [];
      }

      $post_type = get_queried_object();

      if (!$post_type instanceof WP_Post_Type) {
         return [];
      }

      $metas['og:title'] = $post_type->labels->archives;

      $metas['description'] = [
         'type'  => 'name',
         'value' => $post_type->description,
      ];

      $metas['og:url'] = get_post_type_archive_link($post_type->name);

      return $metas;
   }

   private function get_search_tags()
   {
      if (!\is_search()) {
         return [];
      }

      $query             = get_search_query();
      $metas['og:title'] = sprintf(esc_attr__('%s - Search Results', 'cav-utilities'), $query);

      $metas['description'] = [
         'type'  => 'name',
         'value' => sprintf(esc_attr__('See all publications with %s', 'cav-utilities'), $query),
      ];

      $metas['og:url'] = get_search_link($query);

      return $metas;
   }

   private function get_singular_tags()
   {
      if (!\is_singular()) {
         return [];
      }

      $Post = new Post();

      $metas['og:type'] = 'article';

      $metas['og:title'] = $Post->get('title', apply_filter: false);

      $metas['description'] = [
         'type'  => 'name',
         'value' => $Post->get('excerpt', apply_filter: false),
      ];

      $metas['og:url'] = $Post->get('link');

      if (has_post_thumbnail()) {
         $thumbnail_ID = $Post->get('thumbnail_id');

         if (is_numeric($thumbnail_ID)) {
            $metas['og:image'] = $thumbnail_ID;
         }
      }

      $twitter_creator = $Post->get('author:x_twitter');

      if (!empty($twitter_creator)) {
         $metas['twitter:creator'] = [
            'type'  => 'name',
            'value' => '@' . ltrim($twitter_creator, '@'),
         ];
      }

      if (!is_page()) {
         $metas['og:article:published_time'] = $Post->get('date', format: 'c');

         if ($Post->has_modified()) {
            $metas['og:article:modified_time'] = $Post->get('modified', format: 'c');
         }
      }

      $metas['author'] = [
         'type'  => 'name',
         'value' => get_the_author_meta('display_name', $Post->get('author')),
      ];

      $categories = $Post->get('categories');

      if (!empty($categories) && is_array($categories)) {
         $metas['og:article:section'] = $categories[0]->name;
      }

      $tags = $Post->get('tag');

      if (!empty($tags)) {
         $metas['og:article:tag'] = $tags;
      }

      return $metas;
   }

   private function get_term_tags()
   {
      if (!\is_category() && !\is_tag() && !\is_tax()) {
         return [];
      }

      $Term  = new Term();
      $title = $Term->get('title');

      $metas['og:title'] = $title;
      $description       = $Term->get('description');

      if (!empty($description)) {
         $description = sprintf(esc_attr__('See all publications of %s', 'cav-utilities'), $title);
      }

      $metas['description'] = [
         'type'  => 'name',
         'value' => $description,
      ];

      $metas['og:url'] = $Term->get('link');

      return $metas;
   }
}
