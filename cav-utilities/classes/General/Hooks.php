<?php

namespace cavWP\General;

class Hooks
{
   public function __construct()
   {
      add_filter('wp_is_mobile', [$this, 'set_is_mobile']);
   }

   public function set_is_mobile()
   {
      $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

      return str_contains($user_agent, 'Mobile') || str_contains($user_agent, 'Android') || str_contains($user_agent, 'Silk/') || str_contains($user_agent, 'Kindle') || str_contains($user_agent, 'BlackBerry') || str_contains($user_agent, 'Opera Mini') || str_contains($user_agent, 'Opera Mobi');
   }
}
