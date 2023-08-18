<?php if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('Ovic_Bundle_Settings')) {
    class Ovic_Bundle_Settings
    {
        public function __construct()
        {
            // Backend AJAX search
            add_filter('woocommerce_json_search_found_products', array($this, 'search_products'));
            // Product data tabs
            add_filter('woocommerce_product_data_tabs', array($this, 'product_data_tabs'), 10, 1);
            // Product data panels
            add_action('woocommerce_product_data_panels', array($this, 'product_data_panels'));
            // Save data panels
            add_action('woocommerce_process_product_meta_simple', array($this, 'save_option_field'));
        }

        public function result($id, $name = null)
        {
            $html    = '';
            $product = wc_get_product($id);

            if ($product->is_type('variable')) {
                return $html;
            }

            $price_min    = $product->get_regular_price();
            $price_max    = $product->get_regular_price();
            $price_sale   = $product->get_sale_price();
            $price_html   = $product->get_price_html();
            $product_name = $name != null ? $name : $product->get_name();

            $html .= '<li '.(!$product->is_in_stock() ? 'class="out-of-stock"' : '').' data-id="'.$id.'" data-price="'.$price_min.'" data-price-max="'.$price_max.'" data-price-sale="'.$price_sale.'"><span class="move"></span><span class="qty"></span><span class="sale"></span> <span class="name">'.$product_name.'</span> (#'.$id.' - '.$price_html.') <span class="remove">+</span></li>';

            return $html;
        }

        function search_products($found_products)
        {
            if (isset($_GET['ovic_bundle_search'])) {
                $html = '';

                if (!empty($found_products)) {
                    $html .= '<ul>';
                    foreach ((array) $found_products as $id => $product_name) {
                        $html .= $this->result($id, $product_name);
                    }
                    $html .= '</ul>';
                } else {
                    $html = '<ul><span>'.sprintf(esc_html__('No results found for "%s"', 'ovic-bundle'), $_GET['term']).'</span></ul>';
                }

                wp_send_json_success($html);
            }

            return $found_products;
        }

        function product_data_tabs($tabs)
        {
            $tabs['ovic_bundle'] = array(
                'label'  => esc_html__('Product Bundle', 'ovic-bundle'),
                'target' => 'ovic_bundle_settings',
                'class'  => array('show_if_simple'),
            );

            return $tabs;
        }

        function product_data_panels()
        {
            global $post;
            $post_id                = $post->ID;
            $ovic_bundle_items      = get_post_meta($post_id, 'ovic_bundle_ids', true);
            $ovic_bundle_sale_price = get_post_meta($post_id, 'ovic_bundle_sale_price', true);
            $ovic_optional          = get_post_meta($post_id, 'ovic_bundle_optional_products', true);
            $ovic_count             = get_post_meta($post_id, 'ovic_bundle_count_products', true);
            ?>
            <div id='ovic_bundle_settings' class='panel woocommerce_options_panel ovic_bundle_table'>
                <table>
                    <tr>
                        <th>
                            <?php esc_html_e('Search', 'ovic-bundle'); ?> (
                            <a href="<?php echo admin_url('admin.php?page=ovic-product-bundle'); ?>"
                               target="_blank">
                                <?php esc_html_e('settings', 'ovic-bundle'); ?>
                            </a>)
                        </th>
                        <td>
                            <div class="w100 ovic-bundle-search">
                                <span class="loading" id="ovic_bundle_loading">
                                    <?php esc_html_e('searching...', 'ovic-bundle'); ?>
                                </span>
                                <input type="search" id="ovic_bundle_keyword" class="ovic_bundle_keyword"
                                       placeholder="<?php esc_html_e('Type any keyword to search', 'ovic-bundle'); ?>"/>
                                <div id="ovic_bundle_results" class="ovic_bundle_results"></div>
                            </div>
                        </td>
                    </tr>
                    <tr class="ovic_bundle_tr_space">
                        <th><?php esc_html_e('Selected', 'ovic-bundle'); ?></th>
                        <td>
                            <div class="w100">
                                <input type="hidden" id="ovic_bundle_ids" class="ovic_bundle_ids"
                                       name="ovic_bundle_ids"
                                       value="<?php echo esc_attr($ovic_bundle_items); ?>"
                                       readonly/>
                                <div id="ovic_bundle_selected" class="ovic_bundle_selected">
                                    <ul>
                                        <?php
                                        $ovic_bundle_price = 0;
                                        if ($ovic_bundle_items) {
                                            $ovic_bundle_items = explode(',', $ovic_bundle_items);
                                            if (is_array($ovic_bundle_items) && count($ovic_bundle_items) > 0) {
                                                foreach ($ovic_bundle_items as $ovic_bundle_item) {
                                                    $ovic_bundle_item_arr  = explode('/', $ovic_bundle_item);
                                                    $ovic_bundle_item_id   = absint(isset($ovic_bundle_item_arr[0]) ? $ovic_bundle_item_arr[0] : 0);
                                                    $ovic_bundle_item_qty  = absint(isset($ovic_bundle_item_arr[1]) ? $ovic_bundle_item_arr[1] : 1);
                                                    $ovic_bundle_item_sale = absint(isset($ovic_bundle_item_arr[2]) ? $ovic_bundle_item_arr[2] : 0);
                                                    $ovic_bundle_product   = wc_get_product($ovic_bundle_item_id);
                                                    if (!$ovic_bundle_product) {
                                                        continue;
                                                    }
                                                    $ovic_bundle_price_qty  = $ovic_bundle_product->get_price() * $ovic_bundle_item_qty;
                                                    $ovic_bundle_price_sale = $ovic_bundle_price_qty - (($ovic_bundle_item_sale / 100) * $ovic_bundle_price_qty);
                                                    $ovic_bundle_price      += $ovic_bundle_price_sale;
                                                    if ($ovic_bundle_product->is_type('variable')) {
                                                        echo '<li '.(!$ovic_bundle_product->is_in_stock() ? 'class="out-of-stock"' : '').' data-id="'.$ovic_bundle_item_id.'" data-price="'.$ovic_bundle_product->get_variation_price('min').'" data-price-max="'.$ovic_bundle_product->get_variation_price('max').'" data-price-sale="'.$ovic_bundle_price_sale.'"><span class="move"></span><span class="qty"><input type="number" value="'.$ovic_bundle_item_qty.'" min="0"/></span><span class="sale"><input type="number" value="'.$ovic_bundle_item_sale.'" min="0" max="100"/>%</span>  <span class="name">'.$ovic_bundle_product->get_name().'</span> (#'.$ovic_bundle_product->get_id().' - '.$ovic_bundle_product->get_price_html().')<span class="remove">×</span></li>';
                                                    } else {
                                                        echo '<li '.(!$ovic_bundle_product->is_in_stock() ? 'class="out-of-stock"' : '').' data-id="'.$ovic_bundle_item_id.'" data-price="'.$ovic_bundle_product->get_price().'" data-price-max="'.$ovic_bundle_product->get_price().'" data-price-sale="'.$ovic_bundle_price_sale.'"><span class="move"></span><span class="qty"><input type="number" value="'.$ovic_bundle_item_qty.'" min="0"/></span><span class="sale"><input type="number" value="'.$ovic_bundle_item_sale.'" min="0" max="100"/>%</span> <span class="name">'.$ovic_bundle_product->get_name().'</span> (#'.$ovic_bundle_product->get_id().' - '.$ovic_bundle_product->get_price_html().')<span class="remove">×</span></li>';
                                                    }
                                                }
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr class="ovic_bundle_tr_space">
                        <th><?php echo esc_html__('Regular price', 'ovic-bundle').' ('.get_woocommerce_currency_symbol().')'; ?></th>
                        <td>
                            <span id="ovic_bundle_regular_price"><?php echo esc_html($ovic_bundle_price); ?></span>
                        </td>
                    </tr>
                    <!--                    <tr class="ovic_bundle_tr_space">-->
                    <!--                        <th>--><?php //echo esc_html__('Sale price', 'ovic-bundle');
                    ?><!--</th>-->
                    <!--                        <td>-->
                    <!--                            <input type="number" value="-->
                    <?php //echo esc_attr($ovic_bundle_sale_price);
                    ?><!--"-->
                    <!--                                   name="ovic_bundle_sale_price">-->
                    <!--                        </td>-->
                    <!--                    </tr>-->
                    <tr class="ovic_bundle_tr_space">
                        <th><?php esc_html_e('Optional products', 'ovic-bundle'); ?></th>
                        <td style="font-style: italic">
                            <div style="margin-bottom: 5px">
                                <input id="ovic_bundle_optional_products" name="ovic_bundle_optional_products"
                                       type="checkbox" <?php echo($ovic_optional == 'on' ? 'checked' : ''); ?>/>
                                <i><?php esc_html_e('Buyer can change the quantity of bundled products?', 'ovic-bundle'); ?></i>
                            </div>
                            <!--                            <div style="margin-bottom: 5px">-->
                            <!--                                <input id="ovic_bundle_count_products" name="ovic_bundle_count_products"-->
                            <!--                                       type="checkbox" -->
                            <?php //echo($ovic_count == 'on' ? 'checked' : '');
                            ?><!--/>-->
                            <!--                                <i>-->
                            <?php //esc_html_e('View count of bundled products?', 'ovic-bundle');
                            ?><!--</i>-->
                            <!--                            </div>-->
                        </td>
                    </tr>
                    <tr class="ovic_bundle_tr_space">
                        <th><?php esc_html_e('Before text', 'ovic-bundle'); ?></th>
                        <td>
                            <div class="w100">
                                <textarea name="ovic_bundle_before_text"
                                          placeholder="<?php esc_html_e('The text before bundled products', 'ovic-bundle'); ?>"><?php echo stripslashes(get_post_meta($post_id, 'ovic_bundle_before_text', true)); ?></textarea>
                            </div>
                        </td>
                    </tr>
                    <tr class="ovic_bundle_tr_space">
                        <th><?php esc_html_e('After text', 'ovic-bundle'); ?></th>
                        <td>
                            <div class="w100">
                                <textarea name="ovic_bundle_after_text"
                                          placeholder="<?php esc_html_e('The text after bundled products', 'ovic-bundle'); ?>"><?php echo stripslashes(get_post_meta($post_id, 'ovic_bundle_after_text', true)); ?></textarea>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <?php
        }

        function save_option_field($post_id)
        {
            if (isset($_POST['ovic_bundle_ids'])) {
                update_post_meta($post_id, 'ovic_bundle_ids', preg_replace('/[^,\/0-9]/', '', $_POST['ovic_bundle_ids']));
            }
            if (isset($_POST['ovic_bundle_sale_price'])) {
                update_post_meta($post_id, 'ovic_bundle_sale_price', $_POST['ovic_bundle_sale_price']);
            } else {
                update_post_meta($post_id, 'ovic_bundle_sale_price', 0);
            }
            if (isset($_POST['ovic_bundle_optional_products'])) {
                update_post_meta($post_id, 'ovic_bundle_optional_products', 'on');
            } else {
                update_post_meta($post_id, 'ovic_bundle_optional_products', 'off');
            }
            if (isset($_POST['ovic_bundle_count_products'])) {
                update_post_meta($post_id, 'ovic_bundle_count_products', 'on');
            } else {
                update_post_meta($post_id, 'ovic_bundle_count_products', 'off');
            }
            if (isset($_POST['ovic_bundle_before_text']) && ($_POST['ovic_bundle_before_text'] != '')) {
                update_post_meta($post_id, 'ovic_bundle_before_text', addslashes($_POST['ovic_bundle_before_text']));
            } else {
                delete_post_meta($post_id, 'ovic_bundle_before_text');
            }
            if (isset($_POST['ovic_bundle_after_text']) && ($_POST['ovic_bundle_after_text'] != '')) {
                update_post_meta($post_id, 'ovic_bundle_after_text', addslashes($_POST['ovic_bundle_after_text']));
            } else {
                delete_post_meta($post_id, 'ovic_bundle_after_text');
            }
        }
    }

    new Ovic_Bundle_Settings();
}