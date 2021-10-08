<?php


namespace WCMultiShipping\inc\shipping_methods\chronopost;

require_once __DIR__.DS.'chronopost_abstract_shipping.php';

class chronopost_express extends chronopost_abstract_shipping
{

    const ID = 'chronopost_express';

    public function __construct($instance_id = 0)
    {
        $this->id = self::ID;

        $this->method_title = __('Chronopost Express', 'wc-multishipping');

        $this->method_description = '';

        $this->product_code = '17';

        $this->return_product_code = '01';

        parent::__construct($instance_id);
    }
}
