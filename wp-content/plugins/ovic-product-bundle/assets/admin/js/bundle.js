(function ($) {
    'use strict';

    var ovic_bundleTimeout = null;

    function ovic_bundle_arrange() {
        $('#ovic_bundle_selected li').arrangeable({
            dragEndEvent: 'OvicBundleDragEndEvent',
            dragSelector: '.move'
        });
    }

    function ovic_bundle_get_ids() {
        var listId = [];
        $('#ovic_bundle_selected li').each(function () {
            listId.push($(this).attr('data-id') + '/' + $(this).find('.qty>input').val() + '/' + $(this).find('.sale>input').val());
        });
        if (listId.length > 0) {
            $('#ovic_bundle_ids').val(listId.join(','));
        } else {
            $('#ovic_bundle_ids').val('');
        }
    }

    function ovic_bundle_change_regular_price() {
        var total     = 0;
        var total_max = 0;
        $('#ovic_bundle_selected li').each(function () {
            total += $(this).attr('data-price-sale') * $(this).find('.qty>input').val();
            total_max += $(this).attr('data-price-max') * $(this).find('.qty>input').val();
        });
        total     = accounting.formatMoney(total, '', ovic_bundle_vars.price_decimals, ovic_bundle_vars.price_thousand_separator, ovic_bundle_vars.price_decimal_separator);
        total_max = accounting.formatMoney(total_max, '', ovic_bundle_vars.price_decimals, ovic_bundle_vars.price_thousand_separator, ovic_bundle_vars.price_decimal_separator);
        if (total == total_max) {
            $('#ovic_bundle_regular_price').html(total);
        } else {
            $('#ovic_bundle_regular_price').html(total + ' - ' + total_max);
        }
    }

    function ovic_bundle_search_product() {
        // ajax search product
        ovic_bundleTimeout = null;

        var $keyWord = $('#ovic_bundle_keyword').val(),
            $ids     = $('#ovic_bundle_ids').val(),
            $loading = $('#ovic_bundle_loading'),
            $results = $('#ovic_bundle_results'),
            $data    = {
                security: ovic_bundle_vars.security,
                term    : $keyWord,
                limit   : ovic_bundle_vars.limit,
            };
        if ($keyWord !== '') {
            $.ajax({
                url    : ovic_bundle_vars.url,
                data   : $data,
                success: function (response) {
                    $results.show();
                    $results.html(response.data);
                    $loading.hide();
                },
            });
        } else {
            $results.hide();
            $loading.hide();
        }
    }

    $(document).ready(function () {

        // total price
        if ($('#product-type').val() == 'simple') {
            ovic_bundle_change_regular_price();
        }

        // search input
        $(document).on('keyup', '#ovic_bundle_keyword', function () {
            if ($('#ovic_bundle_keyword').val() !== '') {
                $('#ovic_bundle_loading').show();

                if (ovic_bundleTimeout !== null) {
                    clearTimeout(ovic_bundleTimeout);
                }
                ovic_bundleTimeout = setTimeout(ovic_bundle_search_product, 300);

                return false;
            }
        });

        // hide search result box if click outside
        $(document).on('click', function (event) {
            if ($(event.target).closest('.ovic-bundle-search').length && $('#ovic_bundle_keyword').val() !== '') {
                $('#ovic_bundle_results').show();
            } else {
                $('#ovic_bundle_results').hide();
            }
        });

        // actions on search result items
        $('#ovic_bundle_results').on('click', 'li', function () {
            $(this).children('span.qty').html('<input type="number" value="1" min="0"/>');
            $(this).children('span.sale').html('<input type="number" value="0" min="0" max="100"/>%');
            $(this).children('span.remove').html('×');
            $('#ovic_bundle_selected ul').append($(this));
            $('#ovic_bundle_results').hide();
            // $('#ovic_bundle_keyword').val('');
            ovic_bundle_get_ids();
            ovic_bundle_change_regular_price();
            ovic_bundle_arrange();

            return false;
        });

        // change qty of each item
        $('#ovic_bundle_selected').on('keyup change', '.qty input', function () {

            $(this).parent().parent().find('.sale>input').trigger('change');
            ovic_bundle_get_ids();
            ovic_bundle_change_regular_price();

            return false;
        });

        // change sale of each item
        $('#ovic_bundle_selected').on('keyup change', '.sale input', function () {
            var num   = $(this).val(),
                price = $(this).parent().parent().attr('data-price'),
                total = price - ((num / 100) * price);

            $(this).parent().parent().attr('data-price-sale', total);
            ovic_bundle_get_ids();
            ovic_bundle_change_regular_price();

            return false;
        });

        // actions on selected items
        $('#ovic_bundle_selected').on('click', 'span.remove', function () {
            $(this).parent().remove();
            ovic_bundle_get_ids();
            ovic_bundle_change_regular_price();

            return false;
        });

        // arrange
        ovic_bundle_arrange();

        $(document).on('OvicBundleDragEndEvent', function () {
            ovic_bundle_get_ids();
        });
    });

})(window.jQuery);