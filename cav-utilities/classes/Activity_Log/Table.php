<?php

namespace cavWP\Activity_Log;

use cavWP\Models\User;
use WP_List_Table;

if (!class_exists('WP_List_Table')) {
   require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Table extends WP_List_Table
{
   public function column_cb($item)
   {
      return sprintf(
         '<input type="checkbox" name="element[]" value="%s" />',
         $item['id'],
      );
   }

   public function column_default($item, $column)
   {
      switch ($column) {
         case 'content':
            return var_export($item[$column], true);
            break;

         case 'date':
            return date_i18n(esc_html__('M j, Y @ H:i'), strtotime($item[$column]));
            break;

         case 'user':
            if (!empty($item[$column])) {
               $User = new User($item[$column]);

               if ($User->exists()) {
                  $link = '<a href="' . $User->get('edit') . '">';
                  $link .= $User->get('username');
                  $link .= '</a>';

                  return $link;
               }

               return 'ID: ' . $item[$column];
            }
            break;

         case 'ip':
         case 'ua':
         case 'entity_ID':
         case 'event':
         default:
            return $item[$column];
      }
   }

   public function column_event($item)
   {
      $actions = [
         'delete' => sprintf('<a href="?page=%s&action=%s&element=%s">' . esc_html__('Delete') . '</a>', $_REQUEST['page'], 'delete', $item['id']),
      ];

      return sprintf('%1$s %2$s', $item['event'], $this->row_actions($actions));
   }

   public function column_ip($item)
   {
      $actions = [
         'view' => sprintf(
            '<a href="https://who.is/whois-ip/ip-address/%s" target="_blank">%s</a>',
            urlencode($item['ip']),
            esc_html__('Parse', 'cav-utilities'),
         ),
      ];

      return sprintf('%1$s %2$s', $item['ip'], $this->row_actions($actions));
   }

   public function column_ua($item)
   {
      $actions = [
         'view' => '<a href="https://udger.com/resources/online-parser?Fheaders=User-Agent%3A+' . urlencode($item['ua']) . '&Fip=&test=5581&action=analyze" target="_blank">' . esc_html__('Parse', 'cav-utilities') . '</a>',
      ];

      return sprintf('%1$s %2$s', $item['ua'], $this->row_actions($actions));
   }

   public function do_bulk_actions(): void
   {
      if (!empty($_POST['_wpnonce'])) {
         $action = 'bulk-' . $this->_args['plural'];

         if (!wp_verify_nonce($_POST['_wpnonce'], $action)) {
            wp_die('Nope! Security check failed!');
         }
      }

      $action = $this->current_action();

      switch ($action) {
         case 'delete':
         case 'delete_all':
            $logger = new Logger();
            $logger->del($_REQUEST['element']);
            break;

         default:
            break;
      }
   }

   public function get_bulk_actions()
   {
      return [
         'delete_all' => esc_html__('Delete'),
      ];
   }

   public function get_columns()
   {
      return [
         'cb'        => '<input type="checkbox" />',
         'event'     => esc_html__('Event', 'cav-utilities'),
         'entity_ID' => esc_html__('ID', 'cav-utilities'),
         'content'   => esc_html__('Content', 'cav-utilities'),
         'user'      => esc_html__('User'),
         'ip'        => esc_html__('IP'),
         'ua'        => esc_html__('User agent'),
         'date'      => esc_html__('Date'),
      ];
   }

   public function prepare_items(): void
   {
      $this->do_bulk_actions();
      $columns = $this->get_columns();

      $hidden = get_user_meta(get_current_user_id(), 'managesettings_page_cavwp-activity_logscolumnshidden', true);

      if (empty($hidden)) {
         $hidden = [];
      }

      $sortable              = $this->get_sortable_columns();
      $this->_column_headers = [$columns, $hidden, $sortable, 'event'];

      $orderby = $_GET['orderby'] ?? 'id';
      $order   = $_GET['order']   ?? 'desc';

      if ('date' === $orderby) {
         $orderby = 'time_gmt';
      }

      $logger = new Logger();

      $per_page     = $this->get_items_per_page('cav_activity_logs_per_page');
      $current_page = $this->get_pagenum();
      $query        = $logger->get_all(page: $current_page, per_page: $per_page, order: [$orderby, $order], search: $_POST['s'] ?? null);
      $this->items  = $query['items'];

      $this->set_pagination_args([
         'total_items' => $query['total'],
         'per_page'    => $per_page,
         'total_pages' => ceil($query['total'] / $per_page),
      ]);
   }

   protected function get_sortable_columns()
   {
      return [
         'entity_ID' => ['entity_ID'],
         'event'     => ['event'],
         'user'      => ['user'],
         'ip'        => ['ip'],
         'ua'        => ['ua'],
         'date'      => ['date'],
      ];
   }
}
