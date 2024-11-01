<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


/**
 * List table class
 */
if (!class_exists('ErrorReportTable')) {
    class ErrorReportTable extends \WP_List_Table
    {

        public function __construct()
        {
            parent::__construct(array(
                'singular' => 'errorReport',
                'plural' => 'errorReports',
                'ajax' => false
            ));
            $this->enqueueScriptsAndStyles();
        }


        public function enqueueScriptsAndStyles()
        {

        }

        public function get_table_classes()
        {
            return array('widefat', 'fixed', 'striped', $this->_args['plural']);
        }

        /**
         * Message to show if no designation found
         *
         * @return void
         */
        public function no_items()
        {
            _e('No reports found', 'tax-service');
        }

        /**
         * Default column values if no callback found
         *
         * @param object $item
         * @param string $column_name
         *
         * @return string
         */
        public function column_default($item, $column_name)
        {
            switch ($column_name) {
                case 'order_id':
                    return $item->order_id;
                case 'full_name':
                    $order = wc_get_order($item->order_id);
                    if ($order)
                        return $order->get_formatted_billing_full_name();
                    else return 'ՉԻ ԳՏՆՎԵԼ';
                case 'payment_gateway':
                    return $item->payment_gateway;
                case 'error_reason':
                    return $item->error_reason;
                case 'message':
                    return $item->message;
                case 'created_at':
                    return $item->created_at;
                default:
                    return isset($item->$column_name) ? $item->$column_name : '';
            }
        }

        /**
         * Method for name column
         *
         * @param array $item an array of DB data
         *
         * @return string
         */
        public function column_name($item)
        {
            $title = '<strong>' . $item['name'] . '</strong>';
            $actions = [
                'delete' => sprintf('<a href="?page=%s&action=%s&customer=%s&_wpnonce=%s">Delete</a>', sanitize_text_field($_REQUEST['page']), 'delete', absint($item['id']))
            ];
            return $title . $this->row_actions($actions);
        }

        /**
         * Get the column names
         *
         * @return array
         */
        public function get_columns()
        {
            $columns = array(
                'cb' => '<input type="checkbox" />',
                'id' => __('ID', 'tax-service'),
                'order_id' => __('Order Id', 'tax-service'),
                'full_name' => __('Full Name', 'tax-service'),
                'payment_gateway' => __('Payment Gateway', 'tax-service'),
                'error_reason' => __('Error reason', 'tax-service'),
                'message' => __('Error Message', 'tax-service'),
                'created_at' => __('Created at', 'tax-service'),
            );
            return $columns;
        }

        /**
         * Get sortable columns
         *
         * @return array
         */
        public function get_sortable_columns()
        {
            $sortable_columns = array(
                'order_id' => array('order_id', true),
                'id' => array('id', true),
            );

            return $sortable_columns;
        }

        /**
         * Set the bulk actions
         *
         * @return array
         */
        public function get_bulk_actions()
        {
            $actions = array(
                'trash' => __('Move to Trash', 'tax-service'),
            );
            return $actions;
        }

        /**
         * Render the checkbox column
         *
         * @param object $item
         *
         * @return string
         */
        public function column_cb($item)
        {
            return sprintf(
                '<input type="checkbox" name="id[]" value="%d" />', $item->id
            );
        }

        /**
         * Set the views
         *
         * @return array
         */
        public function get_views_()
        {
            $status_links = array();
            $base_link = admin_url('admin.php?page=sample-page');
            foreach ($this->counts as $key => $value) {
                $class = ($key == $this->page_status) ? 'current' : 'status-' . $key;
                $status_links[$key] = sprintf('<a href="%s" class="%s">%s <span class="count">(%s)</span></a>', add_query_arg(array('status' => $key), $base_link), $class, $value['label'], $value['count']);
            }
            return $status_links;
        }

        private function hkd_recursive_sanitize_text_field( $array ) {
            foreach ( $array as $key => &$value ) {
                if ( is_array( $value ) ) {
                    $value = $this->hkd_recursive_sanitize_text_field( $value );
                } else {
                    $value = sanitize_text_field( $value );
                }
            }
            return $array;
        }

        /**
         * Prepare the class items
         *
         * @return void
         */
        public function prepare_items()
        {
            $request = $this->hkd_recursive_sanitize_text_field($_REQUEST);

            if ($this->current_action() === 'trash') {
                $ids = isset($_REQUEST['id']) ?  $request['id'] : array();
                $this->deleteItems($ids);
            }
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable);


            $per_page = 20;
            $current_page = $this->get_pagenum();
            $offset = ($current_page - 1) * $per_page;
            $this->page_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '2';

            // only ncessary because we have sample data
            $args = array(
                'offset' => $offset,
                'number' => $per_page,
            );

            if (isset($request['s'])) {
                $args['s'] = $request['s'];
            }

            if (isset($request['orderby']) && isset($request['order'])) {
                $args['orderby'] = $request['orderby'];
                $args['order'] = $request['order'];
            }
            $data = $this->get_all_error_tax_report($args);
            $this->items = $data['data'];

            $this->set_pagination_args(array(
                'total_items' => $this->get_all_error_tax_report_count($args)['count'],
                'per_page' => $per_page
            ));
        }

        public function deleteItems($ids)
        {
            global $wpdb;
            $ids = implode(',', array_map('absint', $ids));
            $wpdb->query("DELETE FROM " . $wpdb->prefix . "tax_service_report WHERE ID IN($ids)");
        }

        public function get_all_error_tax_report_count($args)
        {
            global $wpdb;
            $cache_key = 'tax-report-count';
            $items = wp_cache_get($cache_key, 'tax-service');
            if (false === $items) {
                $sql = 'SELECT COUNT(id) as countItems FROM ' . $wpdb->prefix . 'tax_service_report ';
                if (!empty($args['s'])) {
                    $sql .= " WHERE order_id LIKE '%" . $args['s'] . "%'";
                }
                if (!empty($args['orderby'])) {
                    $sql .= ' ORDER BY ' . $args['orderby'];
                    $sql .= !empty(sanitize_text_field($_REQUEST['order'])) ? ' ' . $args['order'] : ' ASC';
                } else {
                    $sql .= ' ORDER BY id DESC';
                }
                $items = $wpdb->get_results($sql);

                wp_cache_set($cache_key, $items, 'tax-service');
            }
            return [
                'count' => isset($items[0]->countItems) ? $items[0]->countItems: 0
            ];
        }


        public function get_all_error_tax_report($args = array())
        {
            global $wpdb;
            $cache_key = 'tax-error_report-all';
            $items = wp_cache_get($cache_key, 'tax-service');
            if (false === $items) {

                $sql = "SELECT * FROM {$wpdb->prefix}tax_service_report";
                if (!empty($args['s'])) {
                    $sql .= " WHERE order_id LIKE '%" . $args['s'] . "%'";
                }
                if (!empty($args['orderby'])) {
                    $sql .= ' ORDER BY ' . $args['orderby'];
                    $sql .= !empty( sanitize_text_field($_REQUEST['order'])) ? ' ' . $args['order'] : ' ASC';
                } else {
                    $sql .= ' ORDER BY id DESC';
                }
                $sql .= " LIMIT ". $args['number'];
                $sql .= ' OFFSET ' . $args['offset'];

                $items = $wpdb->get_results($sql);
                wp_cache_set($cache_key, $items, 'tax-service');
            }
            return [
                'data' => $items,
                'count' => count($items)];
        }
    }
}