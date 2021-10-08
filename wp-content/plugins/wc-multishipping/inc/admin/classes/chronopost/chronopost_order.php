<?php


namespace WCMultiShipping\inc\admin\classes\chronopost;

use WCMultiShipping\inc\admin\classes\abstract_classes\abstract_order;

class chronopost_order extends abstract_order
{

    const SHIPPING_PROVIDER_DISPLAYED_NAME = 'Chronopost';
    const SHIPPING_PROVIDER_ID = 'chronopost';

    const PICKUP_INFO_META_KEY = '_wms_chronopost_pickup_info';

    const WC_WMS_TRANSIT = 'wc-wms_cp_transit';
    const WC_WMS_DELIVERED = 'wc-wms_cp_delivered';
    const WC_WMS_ANOMALY = 'wc-wms_cp_anomaly';
    const WC_WMS_READY_TO_SHIP = 'wc-wms_cp_ready';

    const WC_WMS_TRANSIT_LABEL = 'Chronopost In-Transit';
    const WC_WMS_DELIVERED_LABEL = 'Chronopost Delivered';
    const WC_WMS_ANOMALY_LABEL = 'Chronopost Anomaly';
    const WC_WMS_READY_TO_SHIP_LABEL = 'Chronopost Ready to ship';

    const AVAILABLE_SHIPPING_METHODS = [
        'chronopost_10' => 'Chronopost 10',
        'chronopost_13' => 'Chronopost 13',
        'chronopost_18' => 'Chronopost 18',
        'chronopost_classic' => 'Chronopost Classic',
        'chronopost_express' => 'Chronopost Express',
        'chronopost_precise' => 'Chronopost Precise',
        'chronopost_relais' => 'Chronopost Relais',
        'chronopost_relais_dom' => 'Chronopost Relais Domicile',
        'chronopost_relais_europe' => 'Chronopost Relais Europe',
        'chronopost_sameday' => 'Chronopost Same Day',
    ];

    const ID_SHIPPING_METHODS_RELAY = [
        'chronopost_relais',
        'chronopost_relais_europe_dom',
    ];

    public static function get_integration_helper()
    {
        return new chronopost_helper();
    }

    public static function delete_label_meta_from_tracking_numbers($tracking_numbers)
    {
        if (empty($tracking_numbers)) {
            wms_enqueue_message(__('Unable to delete => Nothing to delete', 'wc-multishipping'));

            return false;
        }

        $integration_helper = static::get_integration_helper();
        $label_class = $integration_helper->get_label_class();


        $label_entries = $label_class::get_info_from_tracking_numbers($tracking_numbers);
        if (empty($label_entries) || !is_array($label_entries)) return false;

        $label_type = ['_wms_outward_parcels', '_wms_inward_parcels'];
        foreach ($label_entries as $one_label_entry) {

            if (empty($one_label_entry->order_id)) continue;
            $order_id = $one_label_entry->order_id;

            $shipment_data = get_post_meta($order_id, '_wms_chronopost_shipment_data', true);
            if (empty($shipment_data)) continue;

            foreach ($label_type as $one_label_type) {
                if (empty($shipment_data[$one_label_type]['_wms_parcels'])) continue;

                foreach ($shipment_data[$one_label_type]['_wms_parcels'] as $one_key => $one_parcel) {

                    if (in_array($one_parcel['_wms_parcel_skybill_number'], $tracking_numbers)) {

                        unset($shipment_data[$one_label_type]['_wms_parcels'][$one_key]);

                        if ('_wms_outward_parcels' == $one_label_type) unset($shipment_data['_wms_inward_parcels']['_wms_parcels'][$one_key]);
                    }
                    update_post_meta($order_id, '_wms_chronopost_shipment_data', $shipment_data);
                }
            }
        }

        return true;
    }

    public static function get_label_class()
    {
        return new chronopost_label();
    }

}