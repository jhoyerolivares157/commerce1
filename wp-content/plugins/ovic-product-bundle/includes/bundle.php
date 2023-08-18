<?php if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
if (!class_exists('Ovic_Bundle_Woo')) {
    class Ovic_Bundle_Woo
    {
        function __construct()
        {
            // Enqueue frontend scripts
            add_action('wp_enqueue_scripts', array($this, 'bundle_enqueue_scripts'));
            // Add to cart form & button
            add_action('woocommerce_after_single_product_summary', array($this, 'add_to_cart_form'), 6);
            // Add to cart
            add_action('woocommerce_ajax_added_to_cart', array($this, 'ajax_added_to_cart'));
            add_action('woocommerce_add_to_cart', array($this, 'ovic_bundle_add_to_cart'), 10, 6);
            add_filter('woocommerce_add_cart_item', array($this, 'ovic_bundle_add_cart_item'), 10, 1);
            add_filter('woocommerce_add_cart_item_data', array($this, 'ovic_bundle_add_cart_item_data'), 10, 2);
            add_filter('woocommerce_get_cart_item_from_session', array($this, 'get_cart_item_from_session'), 10, 2);
            // Cart item
            add_filter('woocommerce_cart_item_name', array($this, 'ovic_bundle_cart_item_name'), 10, 2);
            add_filter('woocommerce_cart_item_price', array($this, 'ovic_bundle_cart_item_price'), 10, 2);
            add_filter('woocommerce_cart_item_quantity', array($this, 'ovic_bundle_cart_item_quantity'), 10, 3);
            add_filter('woocommerce_cart_item_subtotal', array($this, 'cart_item_subtotal'), 10, 2);
            add_filter('woocommerce_cart_item_remove_link', array($this, 'ovic_bundle_cart_item_remove_link'), 10, 2);
            add_filter('woocommerce_cart_contents_count', array($this, 'ovic_bundle_cart_contents_count'));
            add_action('woocommerce_after_cart_item_quantity_update', array($this, 'cart_item_quantity_update'), 1, 2);
            add_action('woocommerce_before_cart_item_quantity_zero', array($this, 'cart_item_quantity_update'), 1);
            add_action('woocommerce_cart_item_removed', array($this, 'ovic_bundle_cart_item_removed'), 10, 2);
            // Checkout item
            add_filter('woocommerce_checkout_item_subtotal', array($this, 'cart_item_subtotal'), 10, 2);
            // Checkout order detail item
            add_filter('woocommerce_order_formatted_line_subtotal', array($this, 'cart_item_subtotal'), 10, 2);
            // Hide on cart & checkout page
            if (get_option('_ovic_hide_bundle', 'no') == 'yes') {
                add_filter('woocommerce_cart_item_visible', array($this, 'ovic_bundle_item_visible'), 10, 2);
                add_filter('woocommerce_order_item_visible', array($this, 'ovic_bundle_item_visible'), 10, 2);
                add_filter('woocommerce_checkout_cart_item_visible', array($this, 'ovic_bundle_item_visible'), 10, 2);
            }
            // Hide on mini-cart
            if (get_option('_ovic_hide_bundle_mini_cart', 'no') == 'yes') {
                add_filter('woocommerce_widget_cart_item_visible', array($this, 'ovic_bundle_item_visible'), 10, 2);
            }
            // Item class
            add_filter('woocommerce_cart_item_class', array($this, 'ovic_bundle_item_class'), 10, 2);
            add_filter('woocommerce_mini_cart_item_class', array($this, 'ovic_bundle_item_class'), 10, 2);
            add_filter('woocommerce_order_item_class', array($this, 'ovic_bundle_item_class'), 10, 2);
            // Hide item meta
            add_filter('woocommerce_display_item_meta', array($this, 'ovic_bundle_display_item_meta'), 10, 2);
            add_filter('woocommerce_order_items_meta_get_formatted', array($this, 'items_meta_get_formatted'), 10, 1);
            // Order item
            add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_order_line_item'), 10, 3);
            add_filter('woocommerce_order_item_name', array($this, 'ovic_bundle_cart_item_name'), 10, 2);
            // Admin order
            add_filter('woocommerce_hidden_order_itemmeta', array($this, 'hidden_order_itemmeta'), 10, 1);
            add_action('woocommerce_before_order_itemmeta', array($this, 'before_order_itemmeta'), 10, 1);
            // Add custom data
            add_action('wp_ajax_ovic_bundle_custom_data', array($this, 'custom_data_callback'));
            add_action('wp_ajax_nopriv_ovic_bundle_custom_data', array($this, 'custom_data_callback'));
            // Calculate totals
            add_action('woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'), 99, 1);
            // Shipping
            add_filter('woocommerce_cart_shipping_packages', array($this, 'shipping_packages'));
        }

        function bundle_enqueue_scripts()
        {
            wp_register_style('ovic-bundle', OVIC_BUNDLE_URI.'assets/css/bundle.css');
            wp_register_script('ovic-bundle', OVIC_BUNDLE_URI.'assets/js/bundle.min.js', array('jquery'), OVIC_BUNDLE_VERSION, true);
        }

        function custom_data_callback()
        {
            if (isset($_POST['ovic_bundle_ids'])) {
                if (!isset($_POST['ovic_bundle_nonce']) || !wp_verify_nonce($_POST['ovic_bundle_nonce'], 'ovic_bundle_nonce')) {
                    die('Permissions check failed');
                }
                if (!isset($_SESSION)) {
                    session_start();
                }
                $_SESSION['ovic_bundle_ids'] = preg_replace('/[^,\/0-9]/', '', $_POST['ovic_bundle_ids']);
            }
            wp_die();
        }

        function ovic_bundle_cart_contents_count($count)
        {
            $cart_contents = WC()->cart->cart_contents;
            $bundled_items = 0;
            foreach ($cart_contents as $cart_item_key => $cart_item) {
                if (!empty($cart_item['ovic_bundle_parent_id'])) {
                    $bundled_items += $cart_item['quantity'];
                }
            }

            return intval($count - $bundled_items);
        }

        function ovic_bundle_cart_item_name($name, $item)
        {
            if (isset($item['ovic_bundle_parent_id']) && !empty($item['ovic_bundle_parent_id'])) {
                if ((strpos($name, '</a>') !== false) && (get_option('_ovic_bundle_link', 'yes') == 'yes')) {
                    return '<a href="'.get_permalink($item['ovic_bundle_parent_id']).'">'.get_the_title($item['ovic_bundle_parent_id']).'</a> &rarr; '.$name;
                } else {
                    return get_the_title($item['ovic_bundle_parent_id']).' &rarr; '.strip_tags($name);
                }
            } else {
                return $name;
            }
        }

        function cart_item_quantity_update($cart_item_key, $quantity = 0)
        {
            if (!empty(WC()->cart->cart_contents[$cart_item_key]) && (isset(WC()->cart->cart_contents[$cart_item_key]['ovic_bundle_keys']))) {
                if ($quantity <= 0) {
                    $quantity = 0;
                } else {
                    $quantity = WC()->cart->cart_contents[$cart_item_key]['quantity'];
                }
                foreach (WC()->cart->cart_contents[$cart_item_key]['ovic_bundle_keys'] as $ovic_bundle_key) {
                    WC()->cart->set_quantity($ovic_bundle_key, $quantity * (WC()->cart->cart_contents[$ovic_bundle_key]['ovic_bundle_qty'] ? WC()->cart->cart_contents[$ovic_bundle_key]['ovic_bundle_qty'] : 1), false);
                }
            }
        }

        function ovic_bundle_cart_item_removed($cart_item_key, $cart)
        {
            if (isset($cart->removed_cart_contents[$cart_item_key]['ovic_bundle_keys'])) {
                $ovic_bundle_keys = $cart->removed_cart_contents[$cart_item_key]['ovic_bundle_keys'];
                foreach ($ovic_bundle_keys as $ovic_bundle_key) {
                    unset($cart->cart_contents[$ovic_bundle_key]);
                }
            }
        }

        function ajax_added_to_cart($product_id)
        {
            if (isset($_POST['ovic_bundle_ids'])) {
                $ovic_bundle_ids = $_POST['ovic_bundle_ids'];
                add_filter('woocommerce_add_cart_item_data',
                    function ($cart_item_data) use ($ovic_bundle_ids, $product_id) {
                        $terms        = get_the_terms($product_id, 'product_type');
                        $product_type = !empty($terms) && isset(current($terms)->name) ? sanitize_title(current($terms)->name) : 'simple';
                        if ($product_type == 'simple') {
                            $cart_item_data['ovic_bundle_ids'] = $ovic_bundle_ids;
                        }

                        return $cart_item_data;
                    }
                );
            }
        }

        function ovic_bundle_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
        {
            if (isset($cart_item_data['ovic_bundle_ids']) && ($cart_item_data['ovic_bundle_ids'] != '')) {
                $items = explode(',', $cart_item_data['ovic_bundle_ids']);
                if (is_array($items) && (count($items) > 0)) {
                    // add child products
                    foreach ($items as $item) {
                        $ovic_bundle_item     = explode('/', $item);
                        $ovic_bundle_item_id  = absint(isset($ovic_bundle_item[0]) ? $ovic_bundle_item[0] : 0);
                        $ovic_bundle_item_qty = absint(isset($ovic_bundle_item[1]) ? $ovic_bundle_item[1] : 1);
                        if (($ovic_bundle_item_id > 0) && ($ovic_bundle_item_qty > 0)) {
                            $ovic_bundle_item_variation_id = 0;
                            $ovic_bundle_item_variation    = array();
                            // ensure we don't add a variation to the cart directly by variation ID
                            if ('product_variation' === get_post_type($ovic_bundle_item_id)) {
                                $ovic_bundle_item_variation_id      = $ovic_bundle_item_id;
                                $ovic_bundle_item_id                = wp_get_post_parent_id($ovic_bundle_item_variation_id);
                                $ovic_bundle_item_variation_product = wc_get_product($ovic_bundle_item_variation_id);
                                $ovic_bundle_item_variation         = $ovic_bundle_item_variation_product->get_attributes();
                            }
                            $ovic_bundle_product = wc_get_product($ovic_bundle_item_id);
                            if ($ovic_bundle_product) {
                                // set price zero for child product
                                if (!$ovic_bundle_product->is_type('subscription')) {
                                    $ovic_bundle_product->set_price(0);
                                }
                                // add to cart
                                $ovic_bundle_product_qty = $ovic_bundle_item_qty * $quantity;
                                $ovic_bundle_cart_id     = WC()->cart->generate_cart_id($ovic_bundle_item_id, $ovic_bundle_item_variation_id, $ovic_bundle_item_variation, array(
                                        'ovic_bundle_parent_id'  => $product_id,
                                        'ovic_bundle_parent_key' => $cart_item_key,
                                        'ovic_bundle_qty'        => $ovic_bundle_item_qty,
                                    )
                                );
                                $ovic_bundle_item_key    = WC()->cart->find_product_in_cart($ovic_bundle_cart_id);
                                if (!$ovic_bundle_item_key) {
                                    $ovic_bundle_item_key                            = $ovic_bundle_cart_id;
                                    WC()->cart->cart_contents[$ovic_bundle_item_key] = array(
                                        'product_id'             => $ovic_bundle_item_id,
                                        'variation_id'           => $ovic_bundle_item_variation_id,
                                        'variation'              => $ovic_bundle_item_variation,
                                        'quantity'               => $ovic_bundle_product_qty,
                                        'data'                   => $ovic_bundle_product,
                                        'ovic_bundle_parent_id'  => $product_id,
                                        'ovic_bundle_parent_key' => $cart_item_key,
                                        'ovic_bundle_qty'        => $ovic_bundle_item_qty,
                                    );
                                } else {
                                    WC()->cart->cart_contents[$ovic_bundle_item_key]['quantity'] += $ovic_bundle_product_qty;
                                }
                                WC()->cart->cart_contents[$cart_item_key]['ovic_bundle_keys'][] = $ovic_bundle_item_key;
                            }
                        }
                    }
                }
            }
        }

        function ovic_bundle_add_cart_item($cart_item)
        {
            if (isset($cart_item['ovic_bundle_parent_key'])) {
                $cart_item['data']->price = 0;
            }

            return $cart_item;
        }

        function ovic_bundle_add_cart_item_data($cart_item_data, $product_id)
        {
            $ovic_bundle_ids = filter_input(INPUT_POST, 'ovic_bundle_ids');
            $terms           = get_the_terms($product_id, 'product_type');
            $product_type    = !empty($terms) && isset(current($terms)->name) ? sanitize_title(current($terms)->name) : 'simple';
            if ($product_type == 'simple' && $ovic_bundle_ids) {
                $cart_item_data['ovic_bundle_ids'] = $ovic_bundle_ids;
            }

            return $cart_item_data;
        }

        function ovic_bundle_item_visible($visible, $item)
        {
            if (isset($item['ovic_bundle_parent_id'])) {
                return false;
            } else {
                return $visible;
            }
        }

        function ovic_bundle_item_class($class, $item)
        {
            if (isset($item['ovic_bundle_parent_id'])) {
                $class .= ' ovic_bundle-cart-item ovic_bundle-cart-child ovic_bundle-item-child';
            } elseif (isset($item['ovic_bundle_ids'])) {
                $class .= ' ovic_bundle-cart-item ovic_bundle-cart-parent ovic_bundle-item-parent';
            }

            return $class;
        }

        function ovic_bundle_display_item_meta($html, $item)
        {
            if (isset($item['ovic_bundle_ids']) || isset($item['ovic_bundle_parent_id'])) {
                return '';
            } else {
                return $html;
            }
        }

        function items_meta_get_formatted($formatted_meta)
        {
            foreach ($formatted_meta as $key => $meta) {
                if (($meta['key'] == 'ovic_bundle_ids') || ($meta['key'] == 'ovic_bundle_parent_id')) {
                    unset($formatted_meta[$key]);
                }
            }

            return $formatted_meta;
        }

        function add_order_line_item($item, $cart_item_key, $values)
        {
            if (isset($values['ovic_bundle_parent_id'])) {
                $item->update_meta_data('ovic_bundle_parent_id', $values['ovic_bundle_parent_id']);
            }
            if (isset($values['ovic_bundle_ids'])) {
                $item->update_meta_data('ovic_bundle_ids', $values['ovic_bundle_ids']);
            }
        }

        function hidden_order_itemmeta($hidden)
        {
            return array_merge($hidden, array('ovic_bundle_parent_id', 'ovic_bundle_ids'));
        }

        function before_order_itemmeta($item_id)
        {
            if (($ovic_bundle_parent_id = wc_get_order_item_meta($item_id, 'ovic_bundle_parent_id', true))) {
                echo sprintf(esc_html__('(bundled in %s)', 'ovic-bundle'), get_the_title($ovic_bundle_parent_id));
            }
        }

        function get_cart_item_from_session($cart_item, $item_session_values)
        {
            if (isset($item_session_values['ovic_bundle_ids']) && !empty($item_session_values['ovic_bundle_ids'])) {
                $cart_item['ovic_bundle_ids'] = $item_session_values['ovic_bundle_ids'];
            }
            if (isset($item_session_values['ovic_bundle_parent_id'])) {
                $cart_item['ovic_bundle_parent_id']  = $item_session_values['ovic_bundle_parent_id'];
                $cart_item['ovic_bundle_parent_key'] = $item_session_values['ovic_bundle_parent_key'];
                $cart_item['ovic_bundle_qty']        = $item_session_values['ovic_bundle_qty'];
                if (isset($cart_item['data']->subscription_sign_up_fee)) {
                    $cart_item['data']->subscription_sign_up_fee = 0;
                }
            }

            return $cart_item;
        }

        function ovic_bundle_cart_item_remove_link($link, $cart_item_key)
        {
            if (isset(WC()->cart->cart_contents[$cart_item_key]['ovic_bundle_parent_id'])) {
                return '';
            }

            return $link;
        }

        function ovic_bundle_cart_item_quantity($quantity, $cart_item_key, $cart_item)
        {
            if (isset($cart_item['ovic_bundle_parent_id'])) {
                return $cart_item['quantity'];
            }

            return $quantity;
        }

        function ovic_bundle_get_price($cart_item)
        {
            $product_id = $cart_item['product_id'];
            if ($cart_item['variation_id'] > 0) {
                $product_id       = $cart_item['variation_id'];
                $variable_product = new WC_Product_Variation($product_id);
                $price_sale       = $variable_product->get_price();
            } else {
                $bundle_product = wc_get_product($product_id);
                $price_sale     = $bundle_product->get_price();
            }
            $price_sale = $price_sale * $cart_item['quantity'];
            if ($cart_item['ovic_bundle_parent_id'] != $product_id) {
                $bundle_ids = $this->ovic_bundle_get_items($cart_item['ovic_bundle_parent_id']);
                $key        = array_search($product_id, array_column($bundle_ids, 'id'));
                $price_sale = $price_sale - (($bundle_ids[$key]['sale'] / 100) * $price_sale);
            }

            return wc_price($price_sale);
        }

        function ovic_bundle_cart_item_price($price, $cart_item)
        {
            if (isset($cart_item['ovic_bundle_parent_id'])) {
                return $this->ovic_bundle_get_price($cart_item);
            }

            return $price;
        }

        function cart_item_subtotal($subtotal, $cart_item)
        {
            if (isset($cart_item['ovic_bundle_parent_id'])) {
                $price = $this->ovic_bundle_get_price($cart_item);
                if (is_cart()) {
                    $price = '';
                }

                return $price;
            }

            return $subtotal;
        }

        function add_to_cart_form()
        {
            global $product;
            $ovic_bundle_items = $this->ovic_bundle_get_items($product->get_id());
            if (!empty($ovic_bundle_items) && $product->is_type('simple')) {
                $this->ovic_bundle_show_items($ovic_bundle_items);
            }
        }

        function ovic_bundle_add_to_cart_button()
        {
            add_action('woocommerce_before_add_to_cart_button', array($this, 'ovic_bundle_add_to_cart_ids'), 10);
            wc_get_template('single-product/add-to-cart/simple.php');
            remove_action('woocommerce_before_add_to_cart_button', array($this, 'ovic_bundle_add_to_cart_ids'), 10);
        }

        function ovic_bundle_add_to_cart_ids()
        {
            global $product;
            $ovic_bundle_ids = $product->get_id().'/1/0,'.get_post_meta($product->get_id(), 'ovic_bundle_ids', true);
            echo '<input name="ovic_bundle_ids" id="ovic_bundle_ids" type="hidden" value="'.$ovic_bundle_ids.'"/>';
        }

        function before_calculate_totals($cart_object)
        {
            //  This is necessary for WC 3.0+
            if (is_admin() && !defined('DOING_AJAX')) {
                return;
            }
            foreach ($cart_object->get_cart() as $cart_item_key => $cart_item) {
                // child product price
                if (isset($cart_item['ovic_bundle_parent_id']) && ($cart_item['ovic_bundle_parent_id'] != '')) {
                    if (!$cart_item['data']->is_type('subscription')) {
                        $cart_item['data']->set_price(0);
                    }
                }
                // main product price
                if (isset($cart_item['ovic_bundle_ids']) && ($cart_item['ovic_bundle_ids'] != '') && $cart_item['data']->is_type('simple')) {
                    $ovic_bundle_ids    = $this->ovic_bundle_get_items($cart_item['product_id']);
                    $ovic_bundle_items  = explode(',', $cart_item['ovic_bundle_ids']);
                    $ovic_bundle_price  = 0;
                    $subscription_price = 0;
                    $count              = 0;
                    if (is_array($ovic_bundle_items) && count($ovic_bundle_items) > 0) {
                        foreach ($ovic_bundle_items as $key => $ovic_bundle_item) {
                            $ovic_bundle_item_arr  = explode('/', $ovic_bundle_item);
                            $ovic_bundle_item_id   = absint(isset($ovic_bundle_item_arr[0]) ? $ovic_bundle_item_arr[0] : 0);
                            $ovic_bundle_item_qty  = absint(isset($ovic_bundle_item_arr[1]) ? $ovic_bundle_item_arr[1] : 0);
                            $ovic_bundle_item_sale = 0;
                            if ($key == 0 && $ovic_bundle_item_qty <= 0) {
                                $ovic_bundle_item_qty = 1;
                            }
                            if ($key > 0 && !empty($ovic_bundle_ids) && isset($ovic_bundle_ids[$count]['sale'])) {
                                $ovic_bundle_item_sale = $ovic_bundle_ids[$count]['sale'];
                                $count++;
                            }
                            $ovic_bundle_item_product = wc_get_product($ovic_bundle_item_id);
                            if (!$ovic_bundle_item_product || ($ovic_bundle_item_qty <= 0)) {
                                continue;
                            }
                            $ovic_bundle_price_qty  = $ovic_bundle_item_product->get_price() * $ovic_bundle_item_qty;
                            $ovic_bundle_price_sale = $ovic_bundle_price_qty - (($ovic_bundle_item_sale / 100) * $ovic_bundle_price_qty);
                            $ovic_bundle_price      += $ovic_bundle_price_sale;
                            if ($ovic_bundle_item_product->is_type('subscription')) {
                                $subscription_price += $ovic_bundle_price_sale;
                            }
                        }
                    }
                    $cart_item['data']->set_price(floatval($ovic_bundle_price - $subscription_price));
                }
            }
        }

        function shipping_packages($packages)
        {
            if (!empty($packages)) {
                foreach ($packages as $package_key => $package) {
                    if (!empty($package['contents'])) {
                        foreach ($package['contents'] as $cart_item_key => $cart_item) {
                            if (isset($cart_item['ovic_bundle_parent_id']) && ($cart_item['ovic_bundle_parent_id'] != '')) {
                                unset($packages[$package_key]['contents'][$cart_item_key]);
                            }
                        }
                    }
                }
            }

            return $packages;
        }

        public function ovic_bundle_show_items($ovic_bundle_items)
        {
            global $product;

            $product_id           = $product->get_id();
            $ovic_bundle_optional = get_post_meta($product_id, 'ovic_bundle_optional_products', true);
            $ovic_before_text     = get_post_meta($product_id, 'ovic_bundle_before_text', true);
            $ovic_after_text      = get_post_meta($product_id, 'ovic_bundle_after_text', true);

            array_unshift($ovic_bundle_items,
                array(
                    'id'   => $product_id,
                    'qty'  => 1,
                    'sale' => 0,
                )
            );

            /* ENQUEUE SCRIPT */
            wp_enqueue_style('ovic-bundle');
            wp_enqueue_script('ovic-bundle');
            wp_localize_script('ovic-bundle', 'ovic_bundle_vars', array(
                    'ajax_url'                 => admin_url('admin-ajax.php'),
                    'alert_selection'          => esc_html__('Please select some product options before adding this bundle to the cart.', 'ovic-bundle'),
                    'alert_empty'              => esc_html__('Please choose at least one product before adding this bundle to the cart.', 'ovic-bundle'),
                    'bundle_price_text'        => get_option('_ovic_bundle_price_text', 'Bundle price:'),
                    'bundle_price_save_text'   => get_option('_ovic_bundle_price_save_text', 'You save:'),
                    'change_image'             => get_option('_ovic_bundle_change_image', 'yes'),
                    'price_format'             => get_woocommerce_price_format(),
                    'price_decimals'           => wc_get_price_decimals(),
                    'price_thousand_separator' => wc_get_price_thousand_separator(),
                    'price_decimal_separator'  => wc_get_price_decimal_separator(),
                    'currency_symbol'          => get_woocommerce_currency_symbol(),
                    'ovic_bundle_nonce'        => wp_create_nonce('ovic_bundle_nonce'),
                )
            );
            /* CONTENT */
            echo '<div id="ovic_bundle_wrap" class="ovic_bundle-wrap">';

            do_action('ovic_bundle_before_table', $product);

            if (!empty($ovic_before_text)) {
                echo '<div id="ovic_bundle_before_text" class="ovic_bundle-before-text ovic_bundle-text">'.do_shortcode(stripslashes($ovic_before_text)).'</div>';
            }
            $_ovic_bundle_thumb    = get_option('_ovic_bundle_thumb', 'yes');
            $_ovic_bundle_qty      = get_option('_ovic_bundle_qty', 'yes');
            $_ovic_bundle_price    = get_option('_ovic_bundle_price', 'html');
            $_ovic_bundle_discount = get_option('_ovic_bundle_discount', 'yes');
            ?>
            <table id="ovic_bundle_products" cellspacing="0" class="ovic_bundle-table ovic_bundle-products">
                <thead>
                <tr>
                    <?php if ($ovic_bundle_optional == 'on') { ?>
                        <th class="manage-column check-column"></th>
                    <?php } ?>
                    <?php if ($_ovic_bundle_thumb != 'no') { ?>
                        <th class="manage-column column-thumb"></th>
                    <?php } ?>
                    <th class="manage-column column-name column-primary"><?php echo esc_html__('Products', 'ovic-bundle'); ?></th>
                    <?php if (($_ovic_bundle_qty == 'yes') && $ovic_bundle_optional == 'on') { ?>
                        <th class="manage-column column-qty"><?php echo esc_html__('Qty', 'ovic-bundle'); ?></th>
                    <?php } ?>
                    <?php if ($_ovic_bundle_price != 'no') { ?>
                        <th class="manage-column column-price"><?php echo esc_html__('Price', 'ovic-bundle'); ?></th>
                    <?php } ?>
                    <?php if ($_ovic_bundle_discount != 'no') { ?>
                        <th class="manage-column column-discount"><?php echo esc_html__('Discount', 'ovic-bundle'); ?></th>
                    <?php } ?>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($ovic_bundle_items)): ?>
                    <?php foreach ($ovic_bundle_items as $key => $ovic_bundle_item) {
                        $ovic_bundle_product = wc_get_product($ovic_bundle_item['id']);
                        if (!$ovic_bundle_product) {
                            continue;
                        }
                        ?>
                        <tr class="ovic_bundle-product"
                            data-id="<?php echo esc_attr($ovic_bundle_product->is_type('variable') || !$product->is_in_stock() ? 0 : $ovic_bundle_item['id']); ?>"
                            data-price="<?php echo esc_attr(!$product->is_in_stock() ? 0 : $ovic_bundle_product->get_price()); ?>"
                            data-qty="<?php echo esc_attr($ovic_bundle_item['qty']); ?>"
                            data-sale="<?php echo esc_attr($ovic_bundle_item['sale']); ?>">
                            <?php if ($ovic_bundle_optional == 'on') { ?>
                                <td class="ovic_bundle-check check-column">
                                    <label for="ovic_bundle-checkbox-<?php echo esc_attr($ovic_bundle_item['id']) ?>">
                                        <input type="checkbox"
                                               id="ovic_bundle-checkbox-<?php echo esc_attr($ovic_bundle_item['id']) ?>"
                                               class="input-text check"
                                               checked <?php if ($ovic_bundle_item['id'] == $product_id) {
                                            echo 'disabled';
                                        } ?>/>
                                    </label>
                                </td>
                            <?php } ?>
                            <?php if ($_ovic_bundle_thumb != 'no') { ?>
                                <td class="ovic_bundle-thumb column-thumb">
                                    <div class="thumb">
                                        <?php
                                        echo apply_filters('ovic_bundle_item_thumbnail',
                                            $ovic_bundle_product->get_image(array(60, 60)),
                                            $ovic_bundle_product
                                        );
                                        ?>
                                    </div>
                                </td>
                            <?php } ?>
                            <td class="ovic_bundle-title column-name">
                                <?php
                                do_action('ovic_bundle_before_item_name', $ovic_bundle_product);
                                echo '<div class="ovic_bundle-title-inner">';
                                if (($_ovic_bundle_qty == 'yes') && $ovic_bundle_optional != 'on') {
                                    echo apply_filters('ovic_bundle_text_qty', $ovic_bundle_item['qty'].' × ', $ovic_bundle_item['qty'], $ovic_bundle_product);
                                }
                                $ovic_bundle_item_name = '';
                                if ($ovic_bundle_product->is_visible() && (get_option('_ovic_bundle_link', 'yes') == 'yes')) {
                                    $ovic_bundle_item_name .= '<a href="'.$ovic_bundle_product->get_permalink().'" target="_blank">';
                                }
                                if ($ovic_bundle_product->is_in_stock()) {
                                    $ovic_bundle_item_name .= $ovic_bundle_product->get_name();
                                } else {
                                    $ovic_bundle_item_name .= '<s>'.$ovic_bundle_product->get_name().'</s>';
                                }
                                if ($ovic_bundle_product->is_visible() && (get_option('_ovic_bundle_link', 'yes') == 'yes')) {
                                    $ovic_bundle_item_name .= '</a>';
                                }
                                if (isset($ovic_bundle_item['sale']) && $ovic_bundle_item['sale'] > 0) {
                                    $ovic_bundle_item_name .= '<div class="ovic_bundle-sale">-'.$ovic_bundle_item['sale'].'%</div>';
                                }
                                echo apply_filters('ovic_bundle_item_name', $ovic_bundle_item_name, $ovic_bundle_product);
                                echo '</div>';
                                do_action('ovic_bundle_after_item_name', $ovic_bundle_product);
                                ?>
                            </td>
                            <?php if (($_ovic_bundle_qty == 'yes') && $ovic_bundle_optional == 'on') {
                                $max_qty = null;
                                $min_qty = ($ovic_bundle_item['id'] == $product_id) ? 1 : 0;
                                if (($ovic_bundle_product->get_backorders() == 'no') && ($ovic_bundle_product->get_stock_status() != 'onbackorder') && is_int($ovic_bundle_product->get_stock_quantity())) {
                                    $max_qty = $ovic_bundle_product->get_stock_quantity();
                                }
                                ?>
                                <td class="ovic_bundle-qty column-qty">
                                    <?php
                                    do_action('woocommerce_before_add_to_cart_quantity');
                                    woocommerce_quantity_input(
                                        array(
                                            'input_value' => $ovic_bundle_item['qty'],
                                            'min_value'   => $min_qty,
                                            'max_value'   => $max_qty,
                                        ),
                                        $ovic_bundle_product
                                    );
                                    do_action('woocommerce_after_add_to_cart_quantity');
                                    ?>
                                </td>
                                <?php
                            }
                            ?>
                            <?php if ($_ovic_bundle_price != 'no') { ?>
                                <td class="ovic_bundle-price column-price">
                                    <div class="price">
                                        <?php
                                        $ovic_bundle_price = '';
                                        switch ($_ovic_bundle_price) {
                                            case 'price':
                                                $ovic_bundle_price = wc_price($ovic_bundle_product->get_price());
                                                break;
                                            case 'html':
                                                $ovic_bundle_price = $ovic_bundle_product->get_price_html();
                                                break;
                                            case 'subtotal':
                                                $ovic_bundle_price = wc_price($ovic_bundle_product->get_price() * $ovic_bundle_item['qty']);
                                                break;
                                        }
                                        echo apply_filters('ovic_bundle_item_price', $ovic_bundle_price, $ovic_bundle_product);
                                        ?>
                                    </div>
                                </td>
                            <?php } ?>
                            <?php if ($_ovic_bundle_discount != 'no') { ?>
                                <td class="ovic_bundle-total column-discount">
                                    <div class="discount">
                                        <?php
                                        $ovic_bundle_price = $ovic_bundle_product->get_price() * $ovic_bundle_item['qty'];
                                        $ovic_bundle_price = wc_price($ovic_bundle_price - (($ovic_bundle_item['sale'] / 100) * $ovic_bundle_price));
                                        echo apply_filters('ovic_bundle_item_total', $ovic_bundle_price, $ovic_bundle_product);
                                        ?>
                                    </div>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                <?php endif; ?>
                </tbody>
            </table>
            <?php
            if (!empty($ovic_after_text)) {
                echo '<div id="ovic_bundle_after_text" class="ovic_bundle-after-text ovic_bundle-text">'.do_shortcode(stripslashes($ovic_after_text)).'</div>';
            }

            do_action('ovic_bundle_after_table', $product);
            ?>
            <div class="footer-bundle">
                <div class="ovic-bundle-subtotal">
                    <div id="ovic_bundle_total" class="ovic_bundle-total ovic_bundle-text"></div>
                    <div id="ovic_bundle_total_save" class="ovic_bundle-total-save ovic_bundle-text"></div>
                </div>
                <?php $this->ovic_bundle_add_to_cart_button(); ?>
            </div>
            <?php
            echo '</div>';
        }

        function ovic_bundle_get_items($product_id)
        {
            $ovic_bundle_arr = array();
            if (($ovic_bundle_ids = get_post_meta($product_id, 'ovic_bundle_ids', true))) {
                $ovic_bundle_items = explode(',', $ovic_bundle_ids);
                if (is_array($ovic_bundle_items) && count($ovic_bundle_items) > 0) {
                    foreach ($ovic_bundle_items as $ovic_bundle_item) {
                        $ovic_bundle_item_arr = explode('/', $ovic_bundle_item);
                        $ovic_bundle_arr[]    = array(
                            'id'   => absint(isset($ovic_bundle_item_arr[0]) ? $ovic_bundle_item_arr[0] : 0),
                            'qty'  => absint(isset($ovic_bundle_item_arr[1]) ? $ovic_bundle_item_arr[1] : 1),
                            'sale' => absint(isset($ovic_bundle_item_arr[2]) ? $ovic_bundle_item_arr[2] : 0),
                        );
                    }
                }
            }
            if (count($ovic_bundle_arr) > 0) {
                return $ovic_bundle_arr;
            } else {
                return false;
            }
        }
    }

    new Ovic_Bundle_Woo();
}