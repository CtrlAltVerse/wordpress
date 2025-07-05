<?php

namespace cavWP;

use SimpleXMLElement;

/**
 * @ignore
 */
final class Sitemap_Google_News
{
   public function __construct()
   {
      add_action('init', [$this, 'add_rewrite']);
      add_action('template_redirect', [$this, 'load_sitemap']);

      add_filter('redirect_canonical', [$this, 'remove_trailingslashit']);
      add_filter('robots_txt', [$this, 'add_robot']);
   }

   public function add_rewrite(): void
   {
      add_rewrite_rule('^sitemap\-google\-news\.xml/?$', 'index.php?cav=google-news', 'top');
   }

   public function add_robot($robots_txt)
   {
      $url = home_url('sitemap-google-news.xml');
      $robots_txt .= "Sitemap: {$url}\n";

      return $robots_txt;
   }

   public function get_sitemap()
   {
      $index = new SimpleXMLElement(<<<'XML'
      <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
          xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" />
      XML);

      $news_posts = get_posts([
         'post_type'      => ['post'],
         'posts_per_page' => 1000,
         'date_query'     => [
            ['after' => '-2 days'],
         ],
      ]);

      $locale = substr(get_bloginfo('language'), 0, 2);

      foreach ($news_posts as $news_post) {
         $post_title = apply_filters('cav_sitemap_google_news', $news_post->post_title, $news_post);

         $item = $index->addChild('url');
         $item->addChild('loc', get_permalink($news_post->ID));
         $news        = $item->addChild('news', null, 'http://www.google.com/schemas/sitemap-news/0.9');
         $publication = $news->addChild('publication', null, 'http://www.google.com/schemas/sitemap-news/0.9');
         $publication->addChild('name', $post_title, 'http://www.google.com/schemas/sitemap-news/0.9');
         $publication->addChild('language', $locale, 'http://www.google.com/schemas/sitemap-news/0.9');
         $news->addChild('publication_date', date('Y-m-j', strtotime($news_post->post_date)), 'http://www.google.com/schemas/sitemap-news/0.9');
         $news->addChild('title', $post_title, 'http://www.google.com/schemas/sitemap-news/0.9');
      }

      $xml = $index->asXML();

      return str_replace('version="1.0"', 'version="1.0" encoding="UTF-8"', $xml);
   }

   public function load_sitemap(): void
   {
      $cav_template = get_query_var('cav', false);

      if ('google-news' !== $cav_template) {
         return;
      }

      header('Content-Type: application/xml; charset=UTF-8');

      echo $this->get_sitemap();
      exit;
   }

   public function remove_trailingslashit($redirect_url)
   {
      $cav_template = get_query_var('cav', false);

      if ('google-news' !== $cav_template) {
         return $redirect_url;
      }

      return rtrim($redirect_url, '/');
   }
}
