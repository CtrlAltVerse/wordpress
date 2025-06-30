<?php

namespace cavWP\Activity_Log;

final class Default_Events
{
   private $Logger;

   public function __construct()
   {
      $this->Logger = new Logger();
   }

   public function delete_post($post_ID, $WP_Post): void
   {
      $post = (array) $WP_Post;

      unset($post['comment_status'], $post['filter'], $post['ID'], $post['ping_status'], $post['pinged'], $post['post_content_filtered'], $post['post_content'], $post['post_date_gmt'], $post['post_excerpt'], $post['post_modified_gmt'], $post['post_status'], $post['to_ping']);

      $this->Logger->add(
         'post_deleted',
         $post_ID,
         $post,
      );
   }

   public function delete_term($term_ID, $_taxonomy_ID, $_taxonomy, $WP_Term): void
   {
      $term = (array) $WP_Term;

      unset($term['term_group'], $term['filter']);

      $this->Logger->add(
         'term_deleted',
         $term_ID,
         $term,
      );
   }

   public function delete_user($user_ID, $reassign, $wp_user): void
   {
      $user = (array) $wp_user->data;
      unset($user['ID'], $user['user_pass'], $user['user_url'], $user['user_status']);

      $user['reassign'] = $reassign;
      $user['roles']    = $wp_user->roles;

      $this->Logger->add(
         'user_deleted',
         $user_ID,
         $user,
      );
   }

   public function profile_update($user_ID, $wp_user, $userdata): void
   {
      $old_userdata = (array) $wp_user->data;

      if ($userdata['user_pass'] !== $old_userdata['user_pass']) {
         $diff[] = 'user_pass';
      }

      if ($userdata['user_email'] !== $old_userdata['user_email']) {
         $diff[] = 'user_email';
      }

      if ($userdata['display_name'] !== $old_userdata['display_name']) {
         $diff[] = 'display_name';
      }

      if (is_array($wp_user->roles) && !empty($wp_user->roles)) {
         if (empty($userdata['role']) || $userdata['role'] !== $wp_user->roles[0]) {
            $diff[] = 'role';
         }
      } elseif (!empty($userdata['role'])) {
         $diff[] = 'role';
      }

      if (empty($diff)) {
         return;
      }

      $this->Logger->add(
         'user_updated',
         $user_ID,
         $diff,
      );
   }

   public function user_register($user_ID, $userdata): void
   {
      unset($userdata['user_url'], $userdata['locale'], $userdata['comment_shortcuts'], $userdata['use_ssl'], $userdata['user_pass']);

      $this->Logger->add(
         'user_registered',
         $user_ID,
         $userdata,
      );
   }

   public function wp_login($_username, $wp_user): void
   {
      $user = (array) $wp_user->data;

      unset($user['ID'], $user['user_pass'], $user['user_url'], $user['user_status'], $user['user_nicename'], $user['user_registered'], $user['user_activation_key']);

      $user['roles'] = $wp_user->roles;

      $this->Logger->add(
         'user_login',
         $wp_user->data->ID,
         $user,
      );
   }

   public function wp_login_failed($username, $wp_error): void
   {
      $details = [
         'username' => $username,
         'error'    => array_map('strip_tags', $wp_error->get_error_messages()),
      ];

      $this->Logger->add(
         'user_login_failed',
         details: $details,
      );
   }

   public function wp_logout($user_ID): void
   {
      $this->Logger->add(
         'user_logout',
         $user_ID,
      );
   }
}
