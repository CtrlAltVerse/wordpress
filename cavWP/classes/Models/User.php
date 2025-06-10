<?php

namespace cavWP\Models;

use WP_Error;
use WP_User;

/**
 * Handles a WP User.
 */
class User
{
   public $ID = 0;
   protected $data;

   public function __construct($user = null)
   {
      if (is_null($user)) {
         $current = \get_queried_object();

         if (is_a($current, 'WP_User')) {
            $this->data = $current;
         }
      } elseif (is_numeric($user)) {
         $this->data = new WP_User($user);
      } elseif (is_a($user, 'WP_User')) {
         $this->data = $user;
      } elseif (is_string($user)) {
         $this->data = new WP_User(0, $user);
      } elseif (is_array($user) && !empty($user['field']) && !empty($user['value'])) {
         $field = match ($user['field']) {
            'e-mail', 'mail' => 'email',
            'nicename', 'username' => 'slug',
            'user_id' => 'id',
            default   => $user['field'],
         };

         $this->data = get_user_by($field, $user['value']);
      }

      if ($this->data) {
         $this->ID = (int) $this->data->ID;
      } else {
         return new WP_Error('user_not_found', 'User not found');
      }
   }

   public function get($key, $size = 96, $attrs = [])
   {
      $key = match ($key) {
         'user_id', 'id' => 'ID',
         'nicename', 'username', 'slug' => 'user_nicename',
         'e-mail', 'mail', 'email' => 'user_email',
         'register', 'registered', 'date_registered', 'date' => 'user_registered',
         'link'     => 'permalink',
         'login'    => 'user_login',
         'url '     => 'user_url',
         'name'     => 'display_name',
         'gravatar' => 'avatar',
         default    => $key,
      };

      if ($this->data->__isset($key)) {
         return $this->data->__get($key);
      }

      if ('avatar' === $key) {
         return get_avatar($this->data->user_email, $size, '', '', $attrs);
      }

      if ('permalink' === $key) {
         return get_author_posts_url($this->ID);
      }

      $value = \get_user_meta($this->ID, $key, true);

      return '' === $value ? null : $value;
   }

   public function get_meta($key = '', $sigle = true)
   {
      return \get_user_meta($this->data->ID, $key, $sigle);
   }
}
