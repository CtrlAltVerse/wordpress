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
      return str_contains($_SERVER['HTTP_USER_AGENT'], 'Mobile') || str_contains($_SERVER['HTTP_USER_AGENT'], 'Android') || str_contains($_SERVER['HTTP_USER_AGENT'], 'Silk/') || str_contains($_SERVER['HTTP_USER_AGENT'], 'Kindle') || str_contains($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') || str_contains($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') || str_contains($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi');
   }
}
