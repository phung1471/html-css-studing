<?php
function thachpham_plugin_activation() {

    // Khai bao plugin can cai dat
    $plugins = array(
        array(
            'name' => 'Redux Framework',
            'slug' => 'redux-framework',
            'required' => true
        )
    );

    // Thiet lap TGM
    $configs = array(
        'menu' => 'tp_plugin_install',
        'has_notice' => true,
        'dismissable' => false,
        'is_automatic' => true
    );
    tgmpa( $plugins, $configs );

}
add_action('tgmpa_register', 'thachpham_plugin_activation');
?>