<?php

namespace cavWP;

/**
 * @ignore
 */
final class Cache_Requests
{
   public function __construct()
   {
      add_filter('pre_http_request', [$this, 'cache_request_get'], 10, 3);
      add_filter('http_response', [$this, 'cache_request_save'], 10, 3);
   }

   public function cache_request_get($continue, $args, $url)
   {
      if ('GET' !== $args['method']) {
         return $continue;
      }

      if (isset($args['cache']) && false === $args['cache']) {
         return $continue;
      }

      if (!empty($args['cache_key'])) {
         $cache_key = Utils::generate_key($args['cache_key']);
      } else {
         $cache_key = Utils::generate_key($url, 'hash');
      }

      if ('disk' === ($args['cache_type'] ?? false)) {
         $cache       = new DiskCache($cache_key);
         $cached_data = $cache->get();
      } else {
         $cached_data = get_transient($cache_key);
      }

      if (!empty($cached_data)) {
         return $cached_data;
      }

      return $continue;
   }

   public function cache_request_save($response, $args, $url)
   {
      if ('GET' !== $args['method']) {
         return $response;
      }

      if (isset($args['cache']) && false === $args['cache']) {
         return $response;
      }

      if (!empty($args['cache_key'])) {
         $cache_key = Utils::generate_key($args['cache_key']);
      } else {
         $cache_key = Utils::generate_key($url, 'hash');
      }

      $cache_duration = $args['cache_duration'] ?? '6 hours';

      if ('disk' === ($args['cache_type'] ?? false)) {
         $cache = new DiskCache($cache_key);
         $cache->set($response, $cache_duration);
      } else {
         $cache_expiration = strtotime($cache_duration) - time();
         set_transient($cache_key, $response, $cache_expiration);
      }

      return $response;
   }
}
