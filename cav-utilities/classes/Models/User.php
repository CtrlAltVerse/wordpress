<?php

namespace cavWP\Models;

use cavWP\Networks\Utils;
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
      }
   }

   public function exists()
   {
      return !empty($this->ID);
   }

   public function get($key, $size = 96, $attrs = [], $default = null, $apply_filter = true)
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
         'edit_url' => 'edit',
         default    => $key,
      };

      $value = null;

      if ('avatar' === $key) {
         $value = \get_avatar($this->ID, $size, '', '', $attrs);
      }

      if (!$this->exists()) {
         return $value;
      }

      if (in_array($key, array_keys(get_class_vars(get_class($this->data)))) || in_array($key, array_keys((array) $this->data->data))) {
         $value = $this->data->__get($key);
      } elseif ('socials' === $key) {
         $value = $this->get_socials();
      } elseif ('permalink' === $key) {
         $value = \get_author_posts_url($this->ID);
      } elseif ('posts' === $key) {
         $value = get_posts([
            'numberposts' => \get_option('posts_per_page'),
            ...$attrs,
            'author' => $this->ID,
         ]);
      } elseif ('edit' === $key) {
         $value = \get_edit_user_link($this->ID);
      } elseif (empty($value)) {
         $value = \get_user_meta($this->ID, $key, true);

         if ('' === $value) {
            $value = $default;
         }
      }

      $options = compact('size', 'attrs');

      if ($apply_filter) {
         $value = apply_filters('cavwp_user_get', $value, $key, $this->data, $default, $options);
      }

      return $value;
   }

   public function get_meta($key = '', $sigle = true)
   {
      return \get_user_meta($this->ID, $key, $sigle);
   }

   public function get_socials($only = [])
   {
      $all_socials  = Utils::get_services('profile');
      $user_socials = [];

      foreach ($all_socials as $key => $social) {
         if (!empty($only) && !in_array($key, $only)) {
            continue;
         }

         $social_value  = \get_user_meta($this->ID, $key, true);
         $social['raw'] = $social_value;

         if (empty($social_value)) {
            continue;
         }

         if (!empty($social['profile_type'])) {
            if ('url' === $social['profile_type'] && !str_starts_with($social_value, 'https://')) {
               continue;
            }

            if ('number' === $social['profile_type'] && !is_numeric($social_value)) {
               continue;
            }
         } else {
            $social_value = ltrim($social_value, '@');
         }

         $social['profile']  = str_replace('%user%', $social_value, $social['profile']);
         $user_socials[$key] = $social;
      }

      return $user_socials;
   }
}
