<?php

namespace cavWP\Services;

/**
 * @ignore
 */
final class Twitter
{
   private $base_url = 'https://api.twitter.com/2';
   private $oauth    = [];

   public function __construct($api_key, $api_key_secret, $access_token, $access_token_secret)
   {
      $this->oauth = [
         'oauth_signature_method' => 'HMAC-SHA1',
         'oauth_version'          => '1.0',
         'oauth_timestamp'        => time(),
         'oauth_nonce'            => uniqid(),
         'oauth_consumer_key'     => $api_key,
         'oauth_token'            => $access_token,
      ];

      $this->add_signature($api_key_secret, $access_token_secret);
   }

   public function post($text, $link)
   {
      $request = wp_remote_post($this->base_url . '/tweets', [
         'headers' => [
            'Authorization' => $this->get_oauth(),
            'Content-Type'  => 'application/json',
         ],
         'body' => wp_json_encode([
            'text' => $text . ' ' . $link,
         ]),
      ]);

      if (is_wp_error($request)) {
         return false;
      }

      if (\wp_remote_retrieve_response_code($request) >= 300) {
         return false;
      }

      $response = json_decode(\wp_remote_retrieve_body($request), true);

      return $response['data']['id'];
   }

   private function add_signature($api_key_secret, $access_token_secret): void
   {
      $base_info     = $this->get_baseinfo($this->base_url . '/tweets', 'POST', $this->oauth);
      $composite_key = rawurlencode($api_key_secret) . '&' . rawurlencode($access_token_secret);

      $this->oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
   }

   private function get_baseinfo($baseURI, $method, $params)
   {
      $r = [];
      ksort($params);

      foreach ($params as $key => $value) {
         $r[] = "{$key}=" . rawurlencode($value);
      }

      return $method . '&' . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
   }

   private function get_oauth()
   {
      $auth   = 'OAuth ';
      $values = [];

      foreach ($this->oauth as $key => $value) {
         $values[] = "{$key}=\"" . rawurlencode($value) . '"';
      }
      $auth .= implode(', ', $values);

      return $auth;
   }
}
