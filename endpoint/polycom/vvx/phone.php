<?php

/**
 * Phone Base File
 *
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */
class endpoint_polycom_vvx_phone extends endpoint_polycom_base {
    public function __construct(&$config_manager) {
        parent::__construct($config_manager);
header('Content-type: text/xml');
    }

    function prepareConfig() {
        parent::prepareConfig();
    }
}
