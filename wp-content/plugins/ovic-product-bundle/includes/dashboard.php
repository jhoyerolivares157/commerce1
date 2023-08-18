<?php if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('Ovic_Bundle_Dashboard')) {
    class Ovic_Bundle_Dashboard
    {
        public function __construct()
        {
            add_action('admin_menu', array($this, 'admin_menu'), 10);
            // Enqueue backend scripts
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            // Backend AJAX remove
            add_filter('wp_ajax_ovic_bundle_remove_product', array($this, 'remove_bundle_product'));
            add_action('wp_ajax_nopriv_ovic_bundle_remove_product', array($this, 'remove_bundle_product'));
            /* add plugin meta */
            add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
            add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
            add_filter('network_admin_plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
        }

        function admin_menu()
        {
            if (current_user_can('edit_theme_options')) {
                add_submenu_page(
                    'ovic-plugins',
                    'Ovic Product Bundle',
                    'Ovic Product Bundle',
                    'manage_options',
                    'ovic-product-bundle',
                    array($this, 'admin_menu_content')
                );
            }
        }

        function admin_scripts()
        {
            $screen    = get_current_screen();
            $screen_id = $screen ? $screen->id : '';
            if ($screen_id == 'ovic-plugins_page_ovic-product-bundle') {
                wp_enqueue_style('ovic_bundle-dashboard', OVIC_BUNDLE_URI.'assets/admin/css/dashboard.css');
            }
            if (in_array($screen_id, wc_get_screen_ids())) {
                $ajax_url = add_query_arg(
                    array(
                        'action'             => 'woocommerce_json_search_products_and_variations',
                        'ovic_bundle_search' => '1',
                        'exclude'            => !empty($_GET['post']) ? $_GET['post'] : 0,
                    ),
                    admin_url('admin-ajax.php')
                );
                wp_enqueue_style('ovic-bundle', OVIC_BUNDLE_URI.'assets/admin/css/bundle.css');
                wp_enqueue_script('drag-arrange', OVIC_BUNDLE_URI.'assets/admin/js/drag-arrange.js', array(), OVIC_BUNDLE_VERSION, true);
                wp_enqueue_script('accounting', OVIC_BUNDLE_URI.'assets/admin/js/accounting.js', array(), OVIC_BUNDLE_VERSION, true);
                wp_enqueue_script('ovic-bundle', OVIC_BUNDLE_URI.'assets/admin/js/bundle.js', array('jquery',), OVIC_BUNDLE_VERSION, true);
                wp_localize_script('ovic-bundle', 'ovic_bundle_vars', array(
                        'ovic_bundle_nonce'        => wp_create_nonce('ovic_bundle_nonce'),
                        'security'                 => wp_create_nonce('search-products'),
                        'url'                      => $ajax_url,
                        'limit'                    => get_option('_ovic_search_limit'),
                        'price_decimals'           => wc_get_price_decimals(),
                        'price_thousand_separator' => wc_get_price_thousand_separator(),
                        'price_decimal_separator'  => wc_get_price_decimal_separator(),
                    )
                );
            }
        }

        function remove_bundle_product()
        {
            if (isset($_POST['id'])) {
                update_post_meta($_POST['id'], 'ovic_bundle_ids', '');
            }
            wp_die();
        }

        function field_select($field)
        {
            $value = isset($field['default']) ? get_option($field['id'], $field['default']) : '';
            ?>
            <tr>
                <th><?php echo esc_html($field['title']); ?></th>
                <td>
                    <select name="<?php echo esc_attr($field['id']) ?>">
                        <?php foreach ($field['options'] as $key => $option):
                            $selected = ($value == $key) ? ' selected' : '';
                            ?>
                            <option value="<?php echo esc_attr($key); ?>"
                                <?php echo esc_attr($selected); ?>>
                                <?php echo esc_html($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($field['desc'])): ?>
                        <p class="description"><?php echo esc_html($field['desc']); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }

        function field_text($field)
        {
            $value = isset($field['default']) ? get_option($field['id'], $field['default']) : '';
            ?>
            <tr>
                <th><?php echo esc_html($field['title']); ?></th>
                <td>
                    <input type="text" name="<?php echo esc_attr($field['id']); ?>"
                           value="<?php echo esc_attr($value); ?>"/>
                    <?php if (isset($field['desc'])): ?>
                        <p class="description"><?php echo esc_html($field['desc']); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }

        function admin_menu_content()
        {
            ?>
            <div class="ovic_bundle_settings_page wrap">
                <h1 class="ovic_settings_page_title"><?php echo esc_html__('Ovic Product Bundles', 'ovic-bundle'); ?></h1>
                <div class="ovic_settings_page_content">
                    <script>
                        jQuery(document).on('click', '.ovic_bundle_settings_page #col-right a.delete', function (e) {
                            e.preventDefault();
                            var _this   = jQuery(this),
                                _id     = _this.data('id'),
                                _parent = _this.closest('tr');

                            _parent.css('background', 'rgba(255, 138, 8, 0.66)');
                            jQuery.post(ajaxurl, {
                                    action: 'ovic_bundle_remove_product',
                                    id    : _id
                                }, function () {
                                    _parent.remove();
                                }
                            );
                        });
                    </script>
                    <div id="col-container" class="wp-clearfix">
                        <div id="col-left">
                            <div class="col-wrap">
                                <form method="post" action="options.php">
                                    <?php wp_nonce_field('update-options') ?>
                                    <table class="form-table">
                                        <tr class="heading">
                                            <th colspan="2">
                                                <?php esc_html_e('General', 'ovic-bundle'); ?>
                                            </th>
                                        </tr>
                                        <?php
                                        $this->field_select(
                                            array(
                                                'id'      => '_ovic_bundle_thumb',
                                                'title'   => esc_html__('Show thumbnail', 'ovic-bundle'),
                                                'default' => 'yes',
                                                'options' => array(
                                                    'yes' => esc_html__('Yes', 'ovic-bundle'),
                                                    'no'  => esc_html__('No', 'ovic-bundle'),
                                                ),
                                            )
                                        );
                                        $this->field_select(
                                            array(
                                                'id'      => '_ovic_bundle_qty',
                                                'title'   => esc_html__('Show quantity', 'ovic-bundle'),
                                                'default' => 'yes',
                                                'options' => array(
                                                    'yes' => esc_html__('Yes', 'ovic-bundle'),
                                                    'no'  => esc_html__('No', 'ovic-bundle'),
                                                ),
                                            )
                                        );
                                        $this->field_select(
                                            array(
                                                'id'      => '_ovic_bundle_price',
                                                'title'   => esc_html__('Show price', 'ovic-bundle'),
                                                'default' => 'html',
                                                'options' => array(
                                                    'price'    => esc_html__('Price', 'ovic-bundle'),
                                                    'html'     => esc_html__('Price HTML', 'ovic-bundle'),
                                                    'subtotal' => esc_html__('Subtotal', 'ovic-bundle'),
                                                    'no'       => esc_html__('No', 'ovic-bundle'),
                                                ),
                                            )
                                        );
                                        $this->field_select(
                                            array(
                                                'id'      => '_ovic_bundle_discount',
                                                'title'   => esc_html__('Show price discount', 'ovic-bundle'),
                                                'default' => 'yes',
                                                'options' => array(
                                                    'yes' => esc_html__('Yes', 'ovic-bundle'),
                                                    'no'  => esc_html__('No', 'ovic-bundle'),
                                                ),
                                            )
                                        );
                                        $this->field_select(
                                            array(
                                                'id'      => '_ovic_bundle_link',
                                                'title'   => esc_html__('Link to bundled product', 'ovic-bundle'),
                                                'default' => 'yes',
                                                'options' => array(
                                                    'yes' => esc_html__('Yes', 'ovic-bundle'),
                                                    'no'  => esc_html__('No', 'ovic-bundle'),
                                                ),
                                            )
                                        );
                                        $this->field_select(
                                            array(
                                                'id'      => '_ovic_hide_bundle',
                                                'title'   => esc_html__('Hide products in the bundle on cart & checkout page', 'ovic-bundle'),
                                                'default' => 'no',
                                                'options' => array(
                                                    'yes' => esc_html__('Yes', 'ovic-bundle'),
                                                    'no'  => esc_html__('No', 'ovic-bundle'),
                                                ),
                                                'desc'    => esc_html__('Hide products in the bundle, just show the main product on the cart & checkout page.', 'ovic-bundle'),
                                            )
                                        );
                                        $this->field_select(
                                            array(
                                                'id'      => '_ovic_hide_bundle_mini_cart',
                                                'title'   => esc_html__('Hide products in the bundle on mini-cart', 'ovic-bundle'),
                                                'default' => 'no',
                                                'options' => array(
                                                    'yes' => esc_html__('Yes', 'ovic-bundle'),
                                                    'no'  => esc_html__('No', 'ovic-bundle'),
                                                ),
                                                'desc'    => esc_html__('Hide products in the bundle, just show the main product on mini-cart.', 'ovic-bundle'),
                                            )
                                        );
                                        $this->field_text(
                                            array(
                                                'id'      => '_ovic_bundle_price_text',
                                                'title'   => esc_html__('Bundle price text', 'ovic-bundle'),
                                                'default' => esc_html__('Bundle price:', 'ovic-bundle'),
                                                'desc'    => esc_html__('The text before price when choosing variation in the bundle.', 'ovic-bundle'),
                                            )
                                        );
                                        $this->field_text(
                                            array(
                                                'id'      => '_ovic_bundle_price_save_text',
                                                'title'   => esc_html__('Bundle save price text', 'ovic-bundle'),
                                                'default' => esc_html__('You save:', 'ovic-bundle'),
                                                'desc'    => esc_html__('The text before price you saved in the bundle.', 'ovic-bundle'),
                                            )
                                        );
                                        ?>
                                        <tr>
                                            <th><?php esc_html_e('Search limit', 'ovic-bundle'); ?></th>
                                            <td>
                                                <input name="_ovic_search_limit" type="number" min="1"
                                                       max="500"
                                                       value="<?php echo get_option('_ovic_search_limit', '10'); ?>"/>
                                            </td>
                                        </tr>
                                        <tr class="submit">
                                            <th colspan="2">
                                                <input type="submit" name="submit"
                                                       class="button button-primary"
                                                       value="<?php esc_html_e('Update Options', 'ovic-bundle'); ?>"/>
                                                <input type="hidden" name="action" value="update"/>
                                                <input type="hidden" name="page_options"
                                                       value="_ovic_bundle_thumb,_ovic_bundle_qty,_ovic_bundle_price,_ovic_bundle_discount,_ovic_bundle_link,_ovic_hide_bundle,_ovic_hide_bundle_mini_cart,_ovic_bundle_price_text,_ovic_bundle_price_save_text,_ovic_search_limit"/>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
                            </div>
                        </div>
                        <div id="col-right">
                            <div class="col-wrap">
                                <table class="wp-list-table widefat fixed striped tags ui-sortable">
                                    <thead>
                                    <tr>
                                        <th scope="col" id="thumb" class="manage-column column-thumb">
                                            <?php esc_html_e('Thumb', 'ovic-bundle'); ?>
                                        </th>
                                        <th scope="col" id="name"
                                            class="manage-column column-name column-primary">
                                            <span><?php esc_html_e('Name', 'ovic-bundle'); ?></span>
                                        </th>
                                        <th scope="col" id="slug" class="manage-column column-slug">
                                            <span><?php esc_html_e('Slug', 'ovic-bundle'); ?></span>
                                        </th>
                                        <th scope="col" id="count" class="manage-column column-count">
                                            <span><?php esc_html_e('Count', 'ovic-bundle'); ?></span>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody id="the-list" data-wp-lists="list:bundle">
                                    <?php
                                    $count    = 0;
                                    $paged    = (isset($_GET['paged'])) ? absint($_GET['paged']) : 1;
                                    $args     = array(
                                        'post_type'      => 'product',
                                        'posts_per_page' => 9,
                                        'paged'          => $paged,
                                        'tax_query'      => array(
                                            array(
                                                'taxonomy' => 'product_type',
                                                'field'    => 'slug',
                                                'terms'    => 'simple',
                                            ),
                                        ),
                                        'meta_query'     => array(
                                            array(
                                                'key'     => 'ovic_bundle_ids',
                                                'value'   => '',
                                                'compare' => '!=',
                                            ),
                                        ),
                                    );
                                    $products = new WP_Query($args);

                                    if ($products->have_posts()) {
                                        while ($products->have_posts()) : $products->the_post();
                                            $count++;
                                            $product_id        = get_the_ID();
                                            $bundle_items      = get_post_meta($product_id, 'ovic_bundle_ids', true);
                                            $list_bundle       = explode(',', $bundle_items);
                                            $product           = wc_get_product($product_id);
                                            $thumbnail         = $product->get_image(array(40, 40));
                                            $product_name      = $product->get_name();
                                            $product_slug      = $product->get_slug();
                                            $product_permalink = $product->is_visible() ? $product->get_permalink() : '';
                                            $product_edit      = get_edit_post_link($product_id);
                                            ?>
                                            <tr id="bundle-<?php echo esc_attr($product_id); ?>">
                                                <td class="thumb column-thumb" data-colname="Image">
                                                    <?php echo wp_kses_post($thumbnail); ?>
                                                </td>
                                                <td class="name column-name has-row-actions column-primary"
                                                    data-colname="Name">
                                                    <figure class="thumb-info">
                                                        <?php echo wp_kses_post($thumbnail); ?>
                                                    </figure>
                                                    <div class="info">
                                                        <strong>
                                                            <a href="<?php echo esc_url($product_edit); ?>"
                                                               class="row-title">
                                                                <?php echo esc_html($product_name); ?>
                                                            </a>
                                                        </strong>
                                                        <br>
                                                        <div class="row-actions">
                                                    <span class="edit">
                                                        <a href="<?php echo esc_url($product_edit); ?>"
                                                           target="_blank"
                                                           aria-label="Edit “<?php echo esc_attr($product_name); ?>”">
                                                            Edit
                                                        </a> |
                                                    </span>
                                                            <span class="delete">
                                                        <a href="#"
                                                           data-id="<?php echo esc_attr($product_id); ?>"
                                                           class="delete aria-button-if-js"
                                                           aria-label="Delete “<?php echo esc_attr($product_name); ?>”"
                                                           role="button">
                                                            Delete
                                                        </a> |
                                                    </span>
                                                            <span class="view">
                                                        <a href="<?php echo esc_url($product_permalink); ?>"
                                                           aria-label="View “<?php echo esc_attr($product_name); ?>” archive"
                                                           target="_blank">
                                                            View
                                                        </a>
                                                    </span>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="toggle-row">
                                                        <span class="screen-reader-text">Show more details</span>
                                                    </button>
                                                </td>
                                                <td class="slug column-slug" data-colname="slug">
                                                    <?php echo esc_html($product_slug); ?>
                                                </td>
                                                <td class="count column-count" data-colname="Count">
                                                    <?php echo count($list_bundle); ?>
                                                </td>
                                            </tr>
                                        <?php
                                        endwhile;

                                        wp_reset_postdata();
                                    }
                                    ?>
                                    </tbody>
                                </table>
                                <div class="tablenav bottom">
                                    <div class="tablenav-pages">
                                        <span class="displaying-num">
                                            <?php
                                            /* translators: %s: number */
                                            printf(
                                                esc_html__('%s items', 'ovic-bundle'), // %s will be a number eventually, but must be a string for now.
                                                $count
                                            );
                                            ?>
                                        </span>
                                        <span class="pagination-links">
                                            <?php
                                            $next_disable = '';
                                            $prev_disable = ' disabled';
                                            $max_page     = $products->max_num_pages;
                                            $next_page    = intval($paged) + 1;
                                            $prev_page    = ($paged > 1) ? intval($paged) - 1 : 0;
                                            if ($next_page > $max_page) {
                                                $next_disable = ' disabled';
                                            }
                                            if ($paged > 1) {
                                                $prev_disable = '';
                                            }
                                            ?>
                                            <a class="tablenav-pages-navspan button<?php echo esc_attr($prev_disable); ?>"
                                               href="<?php echo esc_url(get_pagenum_link($prev_page)); ?>">
                                                <span class="screen-reader-text">Prev page</span>
                                                <span aria-hidden="true">‹</span>
                                            </a>
                                            <span id="table-paging" class="paging-input">
                                                <span class="tablenav-paging-text">
                                                    <?php
                                                    printf(
                                                        '%s %s <span class="total-pages">%s</span>',
                                                        $paged,
                                                        esc_html__('of', 'ovic-bundle'),
                                                        $max_page
                                                    );
                                                    ?>
                                                </span>
                                            </span>
                                            <a class="next-page button<?php echo esc_attr($next_disable); ?>"
                                               href="<?php echo esc_url(get_pagenum_link($next_page)); ?>">
                                                <span class="screen-reader-text">Next page</span>
                                                <span aria-hidden="true">›</span>
                                            </a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Show row meta on the plugin screen.
         *
         * @param $actions
         * @param $plugin_file
         *
         * @return array
         */
        public function plugin_row_meta($actions, $plugin_file)
        {
            if (OVIC_BUNDLE_BASENAME === $plugin_file) {
                $row_meta = array(
                    'donate' => '<a href="https://paypal.me/hoangkhanh92">Buy me a coffee</a>',
                );

                return array_merge($actions, $row_meta);
            }

            return (array) $actions;
        }

        /**
         * Show action links on the plugin screen.
         *
         * @param $actions
         * @param $plugin_file
         *
         * @return array
         */
        public static function plugin_action_links($actions, $plugin_file)
        {
            if (OVIC_BUNDLE_BASENAME === $plugin_file) {
                $action_links = array(
                    'settings' => '<a href="'.admin_url('/admin.php?page=ovic-product-bundle').'" aria-label="'.esc_attr__('View Bundle settings', 'ovic-bundle').'">'.esc_html__('Settings', 'ovic-bundle').'</a>',
                );

                return array_merge($action_links, $actions);
            }

            return (array) $actions;
        }
    }

    new Ovic_Bundle_Dashboard();
}