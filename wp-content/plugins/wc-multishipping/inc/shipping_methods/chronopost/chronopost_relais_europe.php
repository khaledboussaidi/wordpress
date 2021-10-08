<?php


namespace WCMultiShipping\inc\shipping_methods\chronopost;

require_once __DIR__.DS.'chronopost_abstract_shipping.php';

class chronopost_relais_europe extends chronopost_abstract_shipping
{

    const ID = 'chronopost_relais_europe';

    public function __construct($instance_id = 0)
    {
        $this->id = self::ID;

        $this->method_title = __('Chronopost Relais Europe', 'wc-multishipping');

        $this->method_description = '';

        $this->product_code = '49';

        $this->return_product_code = '3T';

        parent::__construct($instance_id);
    }
}
