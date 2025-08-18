<?php

namespace cavWP\Services;

class Unsplash
{
   private $access_key;
   private $base_url = 'https://api.unsplash.com';

   public function __construct()
   {
      $this->access_key = get_option('cav-media-unsplash_key');
   }

   public function download($url)
   {
      if (empty($this->access_key)) {
         return [
            'error' => 'missing access key',
         ];
      }

      $request = \wp_remote_get($url, [
         'headers' => [
            'Authorization' => 'Client-ID ' . $this->access_key,
            'Content-Type'  => 'application/json',
         ],
      ]);

      if (\is_wp_error($request)) {
         return false;
      }

      return \wp_remote_retrieve_response_code($request) === 200;
   }

   public function search($query, $page = 1)
   {
      if (empty($this->access_key)) {
         return [
            'error' => 'missing access key',
         ];
      }

      $request = \wp_remote_get("{$this->base_url}/search/photos?" . http_build_query([
         'query'    => $query,
         'per_page' => 30,
         'page'     => $page,
         'lang'     => 'pt',
      ]), [
         'headers' => [
            'Authorization' => 'Client-ID ' . $this->access_key,
            'Content-Type'  => 'application/json',
         ],
      ]);

      if (\is_wp_error($request)) {
         return false;
      }

      if (\wp_remote_retrieve_response_code($request) >= 300) {
         return false;
      }

      return json_decode(\wp_remote_retrieve_body($request), true);
   }
}
