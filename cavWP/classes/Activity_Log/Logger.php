<?php

namespace cavWP\Activity_Log;

use DateTimeImmutable;
use WP_Error;

class Logger
{
   public function add(
      string $type,
      int $ID = 0,
      mixed $details = null,
      int $user_ID = 0,
      null|int|string $timestamp_gmt = null,
      ?string $IP = null,
      ?string $user_agent = null,
   ) {
      if (is_wp_error($this->checks_type($type))) {
         return null;
      }

      $entry['activity_type'] = $type;
      $entry['entity_ID']     = $ID;

      if (!empty($details)) {
         $entry['entity_details'] = maybe_serialize($details);
      }

      if (empty($entry['entity_ID'])) {
         if (is_a($details, 'WP_User') || is_a($details, 'WP_Post')) {
            $entry['entity_ID'] = $details->ID;
         }
      }

      if (!empty($timestamp_gmt)) {
         if (is_string($timestamp_gmt)) {
            $timestamp_gmt = strtotime($timestamp_gmt);
         }

         $datetime      = new DateTimeImmutable($timestamp_gmt);
         $timestamp_gmt = $datetime->format('Y-m-d H:i:s');
      }

      $entry['activity_time_gmt'] = $timestamp_gmt ?? current_time('mysql', true);
      $entry['current_user_ID']   = $user_ID       ?? get_current_user_id();
      $entry['current_IP']        = $IP            ?? CURRENT_IP;
      $entry['current_ua']        = $this->get_user_agent($user_agent);

      $entry = array_filter($entry);

      if (empty($entry)) {
         return false;
      }

      $short_circuit = apply_filters("add_{$type}_activity_log", null, $entry);

      if (null !== $short_circuit) {
         return $short_circuit;
      }

      global $wpdb;

      $success = $wpdb->insert(Utils::get_table_name($wpdb->prefix), $entry);

      if (false === $success) {
         return $success;
      }

      do_action('cav_activity_log_added', $type, $entry);

      return $entry;
   }

   public function add_unique(
      string $type,
      array $unique_query,
      int $ID = 0,
      mixed $details = null,
      null|int|string $timestamp_gmt = null,
      int $user_ID = 0,
      ?string $IP = null,
      ?string $user_agent = null,
   ) {
      if (is_wp_error($this->checks_type($type))) {
         return null;
      }

      $existing_entry = $this->get($type, $unique_query);

      if (!empty($existing_entry) && is_array($existing_entry)) {
         return false;
      }

      return $this->add(
         $type,
         $ID,
         $details,
         $user_ID,
         $timestamp_gmt,
         $IP,
         $user_agent,
      );
   }

   public function get(string $type, $query = [], $columns = [])
   {
      $type_a = $this->checks_type($type);

      if (is_wp_error($type_a)) {
         return $type_a;
      }

      $columns        = $this->parse_columns($columns, $type_a['columns'] ?? []);
      $where          = $this->parse_where($type, $query);
      $details_column = $columns['columns']['entity_details'] ?? false;

      global $wpdb;

      $table_name = Utils::get_table_name($wpdb->prefix);

      $activities = $wpdb->get_results(
         "SELECT {$columns['query']} FROM `{$table_name}` WHERE {$where};",
         ARRAY_A,
      );

      if (empty($activities)) {
         return [];
      }

      foreach ($activities as $activity) {
         if (!empty($details_column)) {
            $activity[$details_column] = maybe_unserialize($activity[$details_column]);
         }

         $activities[] = $activity;
      }

      return $activities;
   }

   public function get_last(string $type, $query = [], $columns = [])
   {
      $type_a = $this->checks_type($type);

      if (is_wp_error($type_a)) {
         return $type_a;
      }

      $columns      = $this->parse_columns($columns, $type_a['columns'] ?? []);
      $where        = $this->parse_where($type, $query);
      $column_value = $columns['columns']['entity_details'];

      global $wpdb;
      $table_name = Utils::get_table_name($wpdb->prefix);

      $activity = $wpdb->get_row(
         "SELECT {$columns['query']} FROM `{$table_name}` WHERE {$where} ORDER BY `activity_time_gmt` DESC LIMIT 1",
         ARRAY_A,
      );

      if (empty($activity)) {
         return [];
      }

      $activity[$column_value] = maybe_unserialize($activity[$column_value]);

      return $activity;
   }

   /**
    * Undocumented function.
    *
     * @param string               $type
     * @param array                $unique_query
     * @param int                  $ID
     * @param mixed                $details
     * @param null|null|int|string $timestamp_gmt
     * @param int                  $user_ID
     * @param null|string          $IP
     * @param null|string          $user_agent
    *
     * @return void
    *
    * @since 1.0.0
    * @see Logger::add()
    */
   public function replace(
      string $type,
      array $unique_query,
      int $ID = 0,
      mixed $details = null,
      null|int|string $timestamp_gmt = null,
      int $user_ID = 0,
      ?string $IP = null,
      ?string $user_agent = null,
   ) {
      if (is_wp_error($this->checks_type($type))) {
         return null;
      }

      $existing_entry = $this->get($type, $unique_query);

      if (!empty($existing_entry) && is_array($existing_entry)) {
         $deleted = $this->del($existing_entry[0]['ID']);

         if (false === $deleted) {
            return false;
         }
      }

      return $this->add(
         $type,
         $ID,
         $details,
         $user_ID,
         $timestamp_gmt,
         $IP,
         $user_agent,
      );
   }

   /**
    * @ignore
    *
     * @param string $type
    */
   private function checks_type($type)
   {
      $types = Utils::get_activity_log_events();

      if (!in_array($type, array_keys($types))) {
         return new WP_Error('cav_activity_log_type_unknown', __('Event type unknown.', 'cavwp'));
      }

      if (191 < strlen($type)) {
         return new WP_Error('cav_activity_log_type_invalid', __('Event type is too long.', 'cavwp'));
      }

      return $types[$type];
   }

   /**
    * @ignore
    *
     * @param int $activity_ID
    */
   private function del(int $activity_ID)
   {
      global $wpdb;
      $table_name = Utils::get_table_name($wpdb->prefix);

      return $wpdb->query(
         $wpdb->prepare(
            'DELETE FROM %s WHERE activity_ID = %d',
            $table_name,
            $activity_ID,
         ),
         ARRAY_A,
      );
   }

   private function get_user_agent($default = null): ?string
   {
      $user_agent = $default ?? $_SERVER['HTTP_USER_AGENT'] ?? null;

      if (!empty($user_agent)) {
         $user_agent = wp_unslash($user_agent);
         $user_agent = substr($user_agent, 0, 255);
      }

      return $user_agent;
   }

   /**
    * @ignore
    *
     * @param array $columns
     * @param array $type_columns
    */
   private function parse_columns($columns = [], $type_columns = [])
   {
      $default_columns = [
         'activity_ID'       => 'ID',
         'activity_type'     => 'type',
         'entity_ID'         => 'entity_ID',
         'entity_details'    => 'details',
         'activity_time'     => 'time',
         'activity_time_gmt' => 'time_gmt',
         'current_user_ID'   => 'current_user',
         'current_IP'        => 'IP',
         'current_ua'        => 'user_agent',
      ];

      $columns = wp_parse_args($columns, wp_parse_args($type_columns, $default_columns));

      /*
      TODO:
      $datetime = new DateTime($row['activity_time_gmt'], new DateTimeZone('UTC'));
      $datetime->setTimezone(new DateTimeZone($local_tz));
      $local_time = $datetime->format('Y-m-d H:i:s');
      */

      foreach ($columns as $name => $rename) {
         if (empty($rename)) {
            continue;
         }

         if ('activity_time' === $name) {
            $local_tz    = wp_timezone_string();
            $columns_s[] = "CONVERT_TZ(activity_time_gmt, 'UTC', '{$local_tz}') AS `{$rename}`";
            continue;
         }

         $columns_s[] = "`{$name}` AS `{$rename}`";
      }

      return [
         'query'   => implode(', ', $columns_s),
         'columns' => $columns,
      ];
   }

   /**
    * @ignore
    *
     * @param string $type
     * @param array  $columns
    */
   private function parse_where(string $type, $columns = [])
   {
      $where[] = '`activity_type` = "' . $type . '"';

      foreach ($columns as $filter) {
         $column  = $filter['key']     ?? null;
         $compare = $filter['compare'] ?? '=';
         $compare = strtoupper($compare);

         if (empty($column) || empty($filter['value'])) {
            continue;
         }

         switch (true) {
            case 'BETWEEN' === $compare && is_numeric($filter['value'][0]) || !empty($filter['raw']):
               $value = $filter['value'][0] . ' AND ' . $filter['value'][1];
               break;

            case 'BETWEEN' === $compare:
               $value = '"' . $filter['value'][0] . '" AND "' . $filter['value'][1] . '"';
               break;

            case is_numeric($filter['value']):
               $value = $filter['value'];
               break;

            case is_array($filter['value']):
               $value = '("' . implode('","', $filter['value']) . '")';
               break;

            default:
               $value = '"' . $filter['value'] . '"';
               break;
         }

         $where[] = "`{$column}` {$compare} {$value}";
      }

      return implode(' AND ', $where);
   }
}
