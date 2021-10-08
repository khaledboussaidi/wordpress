<?php


namespace WCMultiShipping\inc\shipping_methods\chronopost;

require_once __DIR__.DS.'chronopost_abstract_shipping.php';

class chronopost_classic extends chronopost_abstract_shipping
{

    const ID = 'chronopost_classic';

    public function __construct($instance_id = 0)
    {
        $this->id = self::ID;

        $this->method_title = __('Chronopost Classic', 'wc-multishipping');

        $this->method_description = '';

        $this->product_code = '44';

        $this->return_product_code = '01';

        parent::__construct($instance_id);
    }
}
