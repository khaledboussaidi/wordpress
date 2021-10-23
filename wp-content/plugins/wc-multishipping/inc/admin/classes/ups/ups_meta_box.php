<?php

namespace WCMultiShipping\inc\admin\classes\ups;

use WCMultiShipping\inc\admin\classes\abstract_classes\abstract_meta_box;

class ups_meta_box extends abstract_meta_box
{
    const SHIPPING_PROVIDER_ID = 'ups';

    var $order;

    var $order_id = 0;

    var $helper;

    public function __construct()
    {
        $this->helper = new ups_helper();
    }

    public function wms_order_meta_box_display($order)
    {
        $this->order_id = $order->ID;
        $this->order = new \WC_Order($order->ID);

        $this->shipping_method_id = ups_order::get_shipping_method_name($this->order);
        if (!array_key_exists($this->shipping_method_id, ups_order::AVAILABLE_SHIPPING_METHODS)) return;

        wp_enqueue_script('wms_meta_box', WMS_ADMIN_JS_URL.'wms_meta_box.min.js?t='.time(), ['jquery']);

        ?>

		<table class="widefat wms_meta_box_options">


            <?php $this->display_shipment_data(); ?>

            <?php $this->display_parcel_information(); ?>

            <?php $this->display_generate_outward_label_button(); ?>

            <?php $this->display_hidden_inputs(); ?>


		</table>

        <?php
    }

    protected function display_parcel_information()
    {
        $parcel_number_meta = get_post_meta($this->order->get_id(), '_wms_'.static::SHIPPING_PROVIDER_ID.'_parcels_number', true) ? : 1;

        $parcel_class = $this->helper->get_parcel_class();


        $parcels_dimensions = json_decode(get_post_meta($this->order->get_id(), '_wms_'.static::SHIPPING_PROVIDER_ID.'_parcels_dimensions', true), true);
        if (empty($parcels_dimensions)) $parcels_dimensions = $parcel_class::get_parcels_dimensions($this->order);

        ?>

		<tr id="wms_parcels_number">
			<td><?php esc_html_e('Parcels number', 'wooshipping') ?></td>
			<td class=wms_parcels_number">
				<input id="wms_parcels_number_input" name="wms_parcels_number" type="number" value="<?php echo wms_display_value($parcel_number_meta); ?>"
					   min="1"
					   style="width: 100%;"
					   data-order-id="<?php echo wms_display_value($this->order->get_id()); ?>"/>
			</td>
		</tr>
        <?php foreach ($parcels_dimensions as $one_parcel_number => $one_parcel_dimensions): ?>

		<tbody <?php if ($one_parcel_number == key($parcels_dimensions)) echo 'id="wms_meta_box_parcel_information"'; ?> class="wms_metabox_parcel_info">

		<tr>
			<td colspan="2">
				<div class="title"><?php esc_html_e(sprintf("Parcel n°%d dimensions", $one_parcel_number + 1), 'wc-multishipping') ?></div>
			</td>
		</tr>
        <?php
        $translations = [
            'weight' => __('Weight', 'wc-multishipping'),
            'length' => __('Length', 'wc-multishipping'),
            'height' => __('Height', 'wc-multishipping'),
            'width' => __('Width', 'wc-multishipping'),
        ];

        foreach ($one_parcel_dimensions as $one_dimension_label => $one_dimension_value) : ?>
			<tr>
				<td><?php echo wms_display_value($translations[$one_dimension_label]); ?></td>
				<td>
					<input name="wms_parcels_dimensions[<?php echo wms_display_value($one_parcel_number); ?>][<?php echo wms_display_value($one_dimension_label); ?>]" type="number" class="default"
						   placeholder="<?php echo ucfirst(__($one_dimension_label, 'wc-multishipping')) ?>"
						   value="<?php echo wms_display_value($one_dimension_value); ?>"
						   min="1"
						   step="0.01"
						   style="width: 100%;"
						   data-order-id="<?php echo wms_display_value($this->order->get_id()); ?>"/>
				</td>
			</tr>
        <?php endforeach; ?>
		</tbody>
    <?php endforeach; ?>

        <?php
    }

    public static function save_meta_box_values($post_id)
    {
        $wms_nonce = wms_get_var('cmd', 'wms_nonce', '');
        if (empty($wms_nonce)) return;
        if (!wp_verify_nonce($wms_nonce, 'wms_generate_label_nonce')) return;

        $wms_parcels_number = wms_get_var('int', 'wms_parcels_number', '0');
        $wms_parcels_dimensions = wms_get_var('array', 'wms_parcels_dimensions', []);

        array_walk_recursive(
            $wms_parcels_dimensions,
            function ($one_param) {
                if (!is_numeric($one_param)) return;
            }
        );

        update_post_meta($post_id, '_wms_ups_parcels_number', $wms_parcels_number);
        update_post_meta($post_id, '_wms_ups_parcels_dimensions', json_encode($wms_parcels_dimensions));
    }
}