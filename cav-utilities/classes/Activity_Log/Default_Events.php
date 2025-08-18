<?php

namespace cavWP\Activity_Log;

final class Default_Events
{
   private $Logger;

   public function __construct()
   {
      $this->Logger = new Logger();
   }

   public function comment_approved($comment_ID)
   {
      $this->Logger->add(
         'comment_approved',
         $comment_ID,
      );
   }

   public function comment_updated($comment_ID, $new_comment)
   {
      $old_comment = (array) get_comment($comment_ID);

      $changes = [];

      foreach ($new_comment as $column => $column_value) {
         if ($old_comment[$column] === $column_value) {
            continue;
         }

         $changes[] = $column;
      }

      if (empty($changes)) {
         return;
      }

      $this->Logger->add(
         'comment_updated',
         $comment_ID,
         $changes,
      );
   }

   public function post_deleted($post_ID, $WP_Post): void
   {
      $post = (array) $WP_Post;

      if ('revision' === $post['post_type']) {
         return;
      }

      unset($post['comment_status'], $post['filter'], $post['ID'], $post['ping_status'], $post['pinged'], $post['post_content_filtered'], $post['post_content'], $post['post_date_gmt'], $post['post_excerpt'], $post['post_modified_gmt'], $post['post_status'], $post['to_ping']);

      $this->Logger->add(
         'post_deleted',
         $post_ID,
         $post,
      );
   }

   public function post_updated($post_ID, $new_post, $old_post)
   {
      $new_post = (array) $new_post;
      $old_post = (array) $old_post;

      $changes = [];

      foreach ($new_post as $column => $column_value) {
         if ($old_post[$column] === $column_value) {
            continue;
         }

         $changes[] = $column;
      }

      unset($changes['post_modified'], $changes['post_modified_gmt']);

      if (empty($changes)) {
         return;
      }

      $details['changes']     = $changes;
      $details['post_type']   = $new_post['post_type'];
      $details['post_status'] = $new_post['post_status'];
      $details['post_author'] = $new_post['post_author'];

      $this->Logger->add(
         'post_updated',
         $post_ID,
         $details,
      );
   }

   public function search($query): void
   {
      if (!$query->is_search() || is_bot()) {
         return;
      }

      $this->Logger->add(
         'search',
         details: [
            'term'  => $query->get('s'),
            'found' => $query->found_posts,
            'url'   => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",
         ],
      );
   }

   public function term_deleted($term_ID, $_taxonomy_ID, $_taxonomy, $WP_Term): void
   {
      $term = (array) $WP_Term;

      unset($term['term_group'], $term['filter']);

      $this->Logger->add(
         'term_deleted',
         $term_ID,
         $term,
      );
   }

   public function user_deleted($user_ID, $reassign, $wp_user): void
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

   public function user_login($_username, $wp_user): void
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

   public function user_login_failed($username, $wp_error): void
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

   public function user_logout($user_ID): void
   {
      $this->Logger->add(
         'user_logout',
         $user_ID,
      );
   }

   public function user_registered($user_ID, $userdata): void
   {
      unset($userdata['user_url'], $userdata['locale'], $userdata['comment_shortcuts'], $userdata['use_ssl'], $userdata['user_pass']);

      $this->Logger->add(
         'user_registered',
         $user_ID,
         $userdata,
      );
   }

   public function user_updated($user_ID, $wp_user, $userdata): void
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
}
