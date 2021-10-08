<?php


namespace WCMultiShipping\inc\front\pickup\abstract_classes;


abstract class abstract_pickup_widget
{
    const PICKUP_LOCATION_SESSION_VAR_NAME = '';

    const SHIPPING_PROVIDER_ID = '';

    abstract protected static function get_order_class();

    abstract protected function get_pickup_point();

    abstract protected static function is_shipping_method_enabled();

    public static function register_hooks()
    {

        $page = new static();

        if (!$page::is_shipping_method_enabled()) return;

        add_filter('woocommerce_shipping_methods', [$page, 'add_wms_shipping_methods']);

        add_action('woocommerce_after_checkout_form', [$page, 'add_wms_assets']);

        add_action('woocommerce_after_shipping_rate', [$page, 'add_widget_pickup_point'], 10, 2);

        add_action('wp_ajax_wms_get_pickup_point', [$page, 'get_pickup_point']);
        add_action('wp_ajax_nopriv_wms_get_pickup_point', [$page, 'get_pickup_point']);

        add_action('wp_ajax_wms_select_pickup_point', [$page, 'select_pickup_point']);
        add_action('wp_ajax_nopriv_wms_select_pickup_point', [$page, 'select_pickup_point']);

        add_filter('woocommerce_order_button_html', [$page, 'prevent_place_order_button'], 10, 2);
        add_action('woocommerce_checkout_process', [$page, 'prevent_checkout_process']);

        add_action('woocommerce_checkout_order_processed', [$page, 'save_pickup_info'], 10, 1);
        add_action('woocommerce_checkout_order_created', [$page, 'apply_pickup_address'], 10, 1);
    }

    public function add_wms_shipping_methods($shipping_methods)
    {
        $shipping_methods_class = static::get_shipping_methods_class();

        $provider_shipping_methods = $shipping_methods_class::load_shipping_methods();

        foreach ($provider_shipping_methods as $one_shipping_method) {
            $shipping_methods[$one_shipping_method::ID] = get_class($one_shipping_method);
        }

        return $shipping_methods;
    }

    public function add_wms_assets()
    {
        echo '<script type="text/javascript">
                if (ajaxurl === undefined) var ajaxurl = "'.admin_url('admin-ajax.php').'"
              </script>';

        wp_enqueue_script(
            'backbone-modal',
            get_site_url().'/wp-content/plugins/woocommerce/assets/js/admin/backbone-modal.js',
            ['jquery', 'wp-util', 'backbone']
        );

        $map_to_use = get_option('wms_'.static::SHIPPING_PROVIDER_ID.'_section_pickup_points_map_type', 'google_maps');
        if ('mondial_relay_map' == $map_to_use && static::SHIPPING_PROVIDER_ID == 'mondial_relay') {

            wp_enqueue_script('mondialrelay-leaflet-maps', '//unpkg.com/leaflet/dist/leaflet.js', [], '', true);
            wp_enqueue_script('mondialrelay-parcelshoppicker', 'https://widget.mondialrelay.com/parcelshop-picker/jquery.plugin.mondialrelay.parcelshoppicker.min.js', [], '', true);
            wp_enqueue_script('wms_pickup_modal_mondial_relay', WMS_FRONT_JS_URL.'pickups/mondial_relay/mondial_relay_pickup_widget.min.js?time='.time(), ['wp-i18n']);
            wp_set_script_translations('wms_pickup_modal_mondial_relay', 'wc-multishipping');
        } elseif ('openstreetmap' == $map_to_use) {

            wp_enqueue_script('openstreetmap-leaflet-maps', '//unpkg.com/leaflet/dist/leaflet.js', [], '', true);
            wp_enqueue_script('wms_pickup_modal_openstreetmap', WMS_FRONT_JS_URL.'pickups/openstreetmap/openstreetmap_pickup_widget.min.js?time='.time(), ['wp-i18n']);
            wp_set_script_translations('wms_pickup_modal_openstreetmap', 'wc-multishipping');
        } else {

            $google_maps_api_key = get_option('wms_'.static::SHIPPING_PROVIDER_ID.'_section_pickup_points_google_maps_api_key');
            if (!empty($google_maps_api_key)) {
                wp_enqueue_script('wms_pickup_modal_google_maps', WMS_FRONT_JS_URL.'pickups/google_maps/google_maps_pickup_widget.min.js?time='.time(), ['wp-i18n']);
                wp_set_script_translations('wms_pickup_modal_google_maps', 'wc-multishipping');

                wp_enqueue_script('google', 'https://maps.googleapis.com/maps/api/js?key='.$google_maps_api_key.'&v=quarterly', [], '', true);
            }
        }
        wp_enqueue_style('wms_pickup_CSS', WMS_FRONT_CSS_URL.'pickups/wooshippping_pickup_widget.min.css?time='.time());
    }


    public function select_pickup_point()
    {

        if (1 !== wp_verify_nonce(wms_get_var('cmd', 'wms_nonce', ''), 'wms_pickup_selection')) wp_die('Invalid nonce');

        $pickup_id = wms_get_var('cmd', 'pickup_id', '');
        $pickup_name = wms_get_var('string', 'pickup_name', '');
        $pickup_address = wms_get_var('string', 'pickup_address', '');
        $pickup_city = wms_get_var('cmd', 'pickup_city', '');
        $pickup_zip_code = wms_get_var('int', 'pickup_zipcode', '');
        $pickup_country = wms_get_var('cmd', 'pickup_country', 'FR');

        $pickup_info = [
            'pickup_id' => $pickup_id,
            'pickup_name' => $pickup_name,
            'pickup_address' => $pickup_address,
            'pickup_city' => $pickup_city,
            'pickup_zipcode' => $pickup_zip_code,
            'pickup_country' => $pickup_country,
        ];

        if (empty($pickup_id) || empty($pickup_name) || empty($pickup_address) || empty($pickup_city) || empty($pickup_zip_code) || empty($pickup_country)) {
            wp_send_json(
                [
                    'error' => true,
                    'error_message' => __('A parameter is missing', 'wc-multishipping'),
                ]
            );
        }

        WC()->session->set(self::PICKUP_LOCATION_SESSION_VAR_NAME, $pickup_info);

        wp_send_json(
            [
                'error' => false,
                'error_message' => '',
            ]
        );
    }

    public static function add_widget_pickup_point($method, $index)
    {

        if (!is_checkout()) return;

        $order_class = static::get_order_class();

        $method_id = $method->get_id();
        $available_relay_shipping_methods = $order_class::ID_SHIPPING_METHODS_RELAY;
        if (!in_array($method_id, $available_relay_shipping_methods)) return;

        $wc_session = WC()->session;
        if (!in_array($wc_session->chosen_shipping_methods[0], $available_relay_shipping_methods) || $wc_session->chosen_shipping_methods[0] != $method_id) {
            return;
        }

        wp_enqueue_script(
            'backbone-modal',
            get_site_url().'/wp-content/plugins/woocommerce/assets/js/admin/backbone-modal.js',
            ['jquery', 'wp-util', 'backbone']
        );


        global $woocommerce;
        $countries_obj = new \WC_Countries();
        $countries = $countries_obj->__get('countries');

        $pickup_info = WC()->session->get(self::PICKUP_LOCATION_SESSION_VAR_NAME);

        $map_to_use = get_option('wms_'.static::SHIPPING_PROVIDER_ID.'_section_pickup_points_map_type', 'google_maps');

        if ('mondial_relay_map' == $map_to_use && static::SHIPPING_PROVIDER_ID == 'mondial_relay') {

            $modal_id = 'wms_pickup_open_modal_mondial_relay';
            include WMS_FRONT_PARTIALS.'pickups'.DS.'mondial_relay'.DS.'widget.php';
        } else if ('openstreetmap' == $map_to_use) {

            $modal_id = 'wms_pickup_open_modal_openstreetmap';
            include WMS_FRONT_PARTIALS.'pickups'.DS.'openstreetmap'.DS.'widget.php';
        } else {

            $google_maps_api_key = get_option('wms_'.static::SHIPPING_PROVIDER_ID.'_section_pickup_points_google_maps_api_key');
            if (!empty($google_maps_api_key)) {

                $modal_id = 'wms_pickup_open_modal_google_maps';
                include WMS_FRONT_PARTIALS.'pickups'.DS.'google_maps'.DS.'widget.php';
            } else {
                if (current_user_can('administrator')) {
                    echo '<div id="wms_google_maps_issue">'.sprintf(__('Can\'t display the pick up selection button. No Google Maps Api Key set in your %s plugin configuration.', 'wc-multishipping'), static::SHIPPING_PROVIDER_NAME).'</div>';
                    echo '<div id="wms_google_maps_access_config"><a href="'.admin_url('admin.php?page=wc-settings&tab='.static::SHIPPING_PROVIDER_ID).'" >'.__('Access configuration', 'wc-multishipping').'</a></div>';
                } else {
                    echo '<div id="wms_google_maps_issue">'.sprintf(__('Can\'t display the pick up selection button. No Google Maps Api Key set in the %s plugin configuration. Please contact the website admin.', 'wc-multishipping'), static::SHIPPING_PROVIDER_NAME).'</div>';
                }

                return;
            }
        }
    }

    public function save_pickup_info($order_id)
    {

        $order_class = static::get_order_class();

        $method_id = $order_class::get_shipping_method_name(new \WC_Order($order_id));
        $available_relay_shipping_methods = $order_class::ID_SHIPPING_METHODS_RELAY;
        if (!in_array($method_id, $available_relay_shipping_methods)) return;

        $pickup_info = WC()->session->get(self::PICKUP_LOCATION_SESSION_VAR_NAME);
        if (empty($pickup_info)) return;

        array_walk(
            $pickup_info,
            function ($one_info) {
                esc_sql(wms_display_value($one_info));
            }
        );

        update_post_meta($order_id, $order_class::PICKUP_INFO_META_KEY, $pickup_info);

        WC()->session->set(self::PICKUP_LOCATION_SESSION_VAR_NAME, null);
    }

    public function prevent_place_order_button($order_button)
    {
        $order_class = static::get_order_class();

        $selected_shipping_method = WC()->session->get('chosen_shipping_methods');
        if (empty($selected_shipping_method) || !in_array(reset($selected_shipping_method), $order_class::ID_SHIPPING_METHODS_RELAY)) {
            return $order_button;
        }

        $pickup_info = WC()->session->get(self::PICKUP_LOCATION_SESSION_VAR_NAME);
        if (!empty($pickup_info)) return $order_button;

        $textButton = esc_html__('Please select a pick-up point', 'wc-multishipping');

        return '<button type="submit" disabled class="button alt" name="woocommerce_checkout_place_order" id="place_order">'.wms_display_value($textButton).'</button>';
    }

    public function prevent_checkout_process()
    {
        $order_class = static::get_order_class();

        $wc_session = WC()->session;

        $selected_shipping_method = WC()->session->get('chosen_shipping_methods');
        if (empty($selected_shipping_method) || !in_array(reset($selected_shipping_method), $order_class::ID_SHIPPING_METHODS_RELAY)) return;

        $pickup_info = WC()->session->get(self::PICKUP_LOCATION_SESSION_VAR_NAME);

        if (empty($pickup_info)) throw new Exception(esc_html__('Please select a pick-up point', 'wc-multishipping'));


        $customer_data = $wc_session->get('customer');
        $customer_shipping_country = $customer_data['shipping_country'];
        $customer_phone_number = wms_get_var('string', 'billing_phone', '');

        if (empty($customer_phone_number)) $customer_phone_number = wms_get_var('string', 'shipping_phone', '');

        $customer_phone_number = str_replace(' ', '', $customer_phone_number);
        $customer_phone_number = wms_display_value($customer_phone_number);

        $french_mobile_number_regex = '/^(?:(?:\+|00)33|0)(?:6|7)\d{8}$/';
        $belgian_mobile_number_regex = '/^(?:(?:\+|00)32|0)4\d{8}$/';

        if (empty($customer_phone_number) || ('FR' === $customer_shipping_country && !preg_match($french_mobile_number_regex, $customer_phone_number)) || ('BE' === $customer_shipping_country && !preg_match($belgian_mobile_number_regex, $customer_phone_number))) {
            throw new \Exception(__('Please define a mobile phone number for SMS notification tracking', 'wc-multishipping'));
        }
    }

    public function apply_pickup_address($order)
    {
        $order_class = static::get_order_class();
        if (!$order_class::is_wms_shipping_method($order)) return;

        $pickup_data = WC()->session->get(self::PICKUP_LOCATION_SESSION_VAR_NAME);

        $order->set_shipping_company($pickup_data['pickup_name']);
        $order->set_shipping_address_1($pickup_data['pickup_address']);
        $order->set_shipping_postcode($pickup_data['pickup_zipcode']);
        $order->set_shipping_city($pickup_data['pickup_city']);
        $order->set_shipping_country($pickup_data['pickup_country']);

        $order->save();
    }
}
