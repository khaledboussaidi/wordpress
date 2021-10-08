<?php

namespace WCMultiShipping\inc\admin\classes\chronopost;

use WCMultiShipping\inc\admin\classes\abstract_classes\abstract_meta_box;

class chronopost_meta_box extends abstract_meta_box
{
    const SHIPPING_PROVIDER_ID = 'chronopost';

    var $order;

    var $order_id = 0;

    var $helper;

    public function __construct()
    {
        $this->helper = new chronopost_helper();
    }

    public function wms_order_meta_box_display($order)
    {
        $this->order_id = $order->ID;
        $this->order = new \WC_Order($order->ID);

        $this->shipping_method_id = chronopost_order::get_shipping_method_name($this->order);
        if (!array_key_exists($this->shipping_method_id, chronopost_order::AVAILABLE_SHIPPING_METHODS)) return;

        wp_enqueue_script('wms_meta_box', WMS_ADMIN_JS_URL.'wms_meta_box.min.js?t='.time(), ['jquery']);

        ?>

		<table class="widefat wms_meta_box_options">


            <?php $this->display_shipment_data(); ?>

            <?php $this->display_insurance_options(); ?>

            <?php $this->display_ship_saturday_options(); ?>

            <?php $this->display_parcel_information(); ?>

            <?php $this->display_generate_outward_label_button(); ?>

            <?php $this->display_generate_inward_label_button(); ?>

            <?php $this->display_hidden_inputs(); ?>


		</table>

        <?php
    }

    private function display_insurance_options()
    {

        $insurance_enable = get_post_meta($this->order->get_id(), '_wms_chronopost_insurance_add', true);
        if ('' === $insurance_enable) $insurance_enable = get_option('wms_chronopost_section_insurance_ad_valorem_enabled');

        $config_min_insurance_amount = (float)get_option('wms_chronopost_section_insurance_ad_valorem_min_amount', 0);
        $post_meta_insurance_amount = (float)get_post_meta($this->order->get_id(), '_wms_chronopost_insurance_amount', true);

        $total_amount = 0;
        foreach ($this->order->get_items() as $item) {
            $total_amount += $item->get_total() + (float)$item->get_total_tax() * $item->get_quantity();
        }

        $amount_to_insure = !empty($post_meta_insurance_amount) ? $post_meta_insurance_amount : $total_amount;

        $amount_to_insure = min($amount_to_insure, 20000);

        $amount_reset_information = '';
        if ($amount_to_insure < $config_min_insurance_amount) {
            $amount_to_insure = 0;
            $amount_reset_information = esc_html('Value has been set to 0 as it was lower than "Min insurance amount" set in the config', 'wc-multishipping');
        }
        ?>
		<tbody id="wms_meta_box_insurance_options">

		<tr>
			<td><?php esc_html_e("Advalorem insurance", 'wc-multishipping') ?></td>
			<td class="insurance-enable">
				<select name="wms_insurance_add" data-order-id="<?php echo wms_display_value($this->order->get_id()); ?>" data-action="_wms_chronopost_add_insurance">
					<option value="0" <?php echo wms_display_value($insurance_enable == '0' ? ' selected="selected"' : ''); ?>>
                        <?php esc_html_e('No', 'wc-multishipping'); ?>
					</option>
					<option value="1" <?php echo wms_display_value($insurance_enable == '1' ? ' selected="selected"' : ''); ?>>
                        <?php esc_html_e('Yes', 'wc-multishipping'); ?>
					</option>
				</select>
			</td>
		</tr>
		<tr>
			<td> <?php
                esc_html_e('Insurance amount', 'wc-multishipping').' ';
                echo wc_help_tip(
                    sprintf(__('(Max value: %d)', 'wc-multishipping'), 2000).' '.$amount_reset_information
                ); ?>
			</td>
			<td class="wms_insurance_amount">
				<input type="number" value="<?php echo wms_display_value($amount_to_insure); ?>"
					   name="wms_insurance_amount"
					   style="width: 100%;"
					   data-order-id="<?php echo wms_display_value($this->order->get_id()); ?>"/>
			</td>
		</tr>
		</tbody>
        <?php
    }

    private function display_ship_saturday_options()
    {
        $ship_saturday = get_post_meta($this->order->get_id(), '_wms_chronopost_ship_on_saturday', true);
        if ('' === $ship_saturday) {
            $order_shipping_method = $this->order->get_shipping_methods();
            $shipping_method = reset($order_shipping_method);
            $method_settings = (array)get_option('woocommerce_'.$this->shipping_method_id.'_'.$shipping_method->get_instance_id().'_settings');
            $ship_saturday = ((!empty($method_settings['deliver_on_saturday']) && $method_settings['deliver_on_saturday'] == 'yes') ? true : false);
        }

        $shipping_methods_no_saturday = [
            'chronorelaiseurope',
            'chronoexpress',
            'chronoclassic',
        ];

        ?>
		<tbody id="wms_meta_box_saturday_options">

		<tr>
			<td><?php esc_html_e("Ship On Saturday", 'wc-multishipping') ?></td>
			<td class="wms_saturday_shipping">
                <?php if (!in_array($this->shipping_method_id, $shipping_methods_no_saturday)): ?>
					<select name="wms_chronopost_ship_on_saturday" data-order-id="<?php echo wms_display_value($this->order->get_id()); ?>" data-action="update_saturday_shipping">
						<option value="0" <?php echo(empty($ship_saturday) ? ' selected="selected"' : ''); ?>>
                            <?php esc_html_e(
                                'No',
                                'wc-multishipping'
                            ); ?>
						</option>
						<option value="1" <?php echo(!empty($ship_saturday) ? ' selected="selected"' : ''); ?>>
                            <?php esc_html_e(
                                'Yes',
                                'wc-multishipping'
                            ); ?></option>
					</select>
                <?php else: ?>
                    <?php esc_html_e('Not active', 'wc-multishipping'); ?>
                <?php endif; ?>
			</td>
		</tr>
		</tbody>
        <?php
    }

    protected function display_parcel_information()
    {
        $parcel_number_meta = get_post_meta($this->order->get_id(), '_wms_'.static::SHIPPING_PROVIDER_ID.'_parcels_number', true) ? : 1;

        $parcel_class = $this->helper->get_parcel_class();


        $parcels_dimensions = json_decode(get_post_meta($this->order->get_id(), '_wms_'.static::SHIPPING_PROVIDER_ID.'_parcels_dimensions', true), true);
        if (empty($parcels_dimensions)) $parcels_dimensions = $parcel_class::get_parcels_dimensions($this->order);

        $max_weight = $this->shipping_method_id == 'chronorelais' || $this->shipping_method_id == 'chronorelaiseurope' ? 20 : 30;

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
				<td><?php echo wms_display_value($translations[$one_dimension_label]);
                    echo $one_dimension_label == 'weight' ? ' ('.wms_display_value(get_option('woocommerce_weight_unit')).')' : ''; ?></td>
				<td>
					<input name="wms_parcels_dimensions[<?php echo wms_display_value($one_parcel_number); ?>][<?php echo wms_display_value($one_dimension_label); ?>]" type="number" class="default"
						   placeholder="<?php echo ucfirst(__($one_dimension_label, 'wc-multishipping')) ?>"
						   value="<?php echo wms_display_value($one_dimension_value); ?>"
						   min="1"
						   step="0.01"
						   style="width: 100%;"
                        <?php echo $one_dimension_label == 'weight' ? 'max="'.wms_display_value($max_weight).'"' : ''; ?>
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

        $wms_insurance_add = wms_get_var('int', 'wms_insurance_add', '0');
        $wms_insurance_amount = wms_get_var('int', 'wms_insurance_amount', '0');
        $wms_saturday_shipping = wms_get_var('int', 'wms_chronopost_ship_on_saturday', '0');
        $wms_parcels_number = wms_get_var('int', 'wms_parcels_number', '0');
        $wms_parcels_dimensions = wms_get_var('array', 'wms_parcels_dimensions', []);


        array_walk_recursive(
            $wms_parcels_dimensions,
            function ($one_param) {
                if (!is_numeric($one_param)) wp_die(__('Invalid value for parcel dimensions. Process blocked for security reasons.', 'wc-multishipping'));
            }
        );

        update_post_meta($post_id, '_wms_chronopost_add_insurance', $wms_insurance_add);
        update_post_meta($post_id, '_wms_chronopost_insurance_amount', $wms_insurance_amount);
        update_post_meta($post_id, '_wms_chronopost_ship_on_saturday', $wms_saturday_shipping);
        update_post_meta($post_id, '_wms_chronopost_parcels_number', $wms_parcels_number);
        update_post_meta($post_id, '_wms_chronopost_parcels_dimensions', json_encode($wms_parcels_dimensions));
    }
}