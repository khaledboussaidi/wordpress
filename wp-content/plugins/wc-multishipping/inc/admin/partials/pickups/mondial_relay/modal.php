<script type="text/template" id="tmpl-<?php echo wms_display_value($modal_id); ?>">
	<div class="wms_pickup_modal" id="<?php echo wms_display_value($modal_id); ?>">
		<div class="wc-backbone-modal">
			<div class="wc-backbone-modal-content">
				<section class="wc-backbone-modal-main" role="main">
					<div class="wc-backbone-modal-loader">
					</div>
					<header class="wc-backbone-modal-header">
						<h1><?php __('Mondial Relay Pickups'); ?></h1>
						<button class="modal-close modal-close-link dashicons dashicons-no-alt">
							<span class="screen-reader-text"><?php echo esc_html_e('Close modal panel', 'woocommerce'); ?></span>
						</button>
					</header>
                    <?php $map_to_use = get_option('wms_mondial_relay_section_pickup_points_map_type', 'google_maps');
                    if ('google_maps' == $map_to_use) { ?>
						<article>
							<div class="wms_pickup_modal_address">
								<div class="wms_pickup_modal_address_city">
									<label><?php esc_html_e('City', 'wc-multishipping'); ?>
										<input type="text" class="wms_pickup_modal_address_city_input">
									</label>
								</div>
								<div class="wms_pickup_modal_address_country">
                                    <?php echo woocommerce_form_field(
                                        'wms_pickup_modal_address_country_select',
                                        [
                                            'type' => 'select',
                                            'class' => ['wms_pickup_modal_address_country_select'],
                                            'label' => __('Select a country', 'wc-multishipping'),
                                            'options' => $countries,
                                        ]
                                    ); ?>
								</div>
								<div class="wms_pickup_modal_address_zip-code">
									<label><?php esc_html_e('Zip Code', 'wc-multishipping'); ?>
										<input type="text" class="wms_pickup_modal_address_zipcode_input">
									</label>
								</div>
								<div class="wms_pickup_modal_address_zip-code">
									<button type="button" class="wms_pickup_modal_address_search"><?php echo __('Find a pickup point', 'wc-multishipping'); ?></button>
								</div>
							</div>
							<div class="wms_pickup_modal_map">
							</div>
							<div class="wms_pickup_modal_listing">

							</div>
						</article>
                    <?php } else { ?>
						<div class="wms_pickup_modal_map">
						</div>
						<button id="wms_select_point" class="wms_select_pickup_point_button">
                            <?php echo __('Ship Here', 'wc-multishipping'); ?>
						</button>
                    <?php } ?>

				</section>
			</div>
			<div class="wc-backbone-modal-backdrop modal-close"></div>
		</div>
	</div>
</script>
