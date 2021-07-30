<?php

$options = get_option('woocommerce_frisbee_settings');
if (!isset($options['save_data_after_uninstall']) || $options['save_data_after_uninstall'] == 'no') {
    delete_option('woocommerce_frisbee_settings');
}
