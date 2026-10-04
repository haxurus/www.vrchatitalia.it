<?php
/**
 * Plugin Name: VRC Italia Network Core
 * Description: Community management, owner dashboard, applications, events, moderation and GitHub design synchronization for VRC Italia Network.
 * Version: 0.1.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Haxurus
 * Text Domain: vrchat-italia
 */

defined( 'ABSPATH' ) || exit;

define( 'VRI_CORE_VERSION', '0.1.0' );
define( 'VRI_CORE_FILE', __FILE__ );
define( 'VRI_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VRI_CORE_URL', plugin_dir_url( __FILE__ ) );

foreach ( array( 'install', 'model', 'frontend', 'admin', 'design-sync' ) as $vri_module ) {
    require_once VRI_CORE_DIR . 'includes/class-vri-' . $vri_module . '.php';
}

final class VRI_Core {
    public static function boot() {
        VRI_Model::hooks();
        VRI_Frontend::hooks();
        VRI_Admin::hooks();
        VRI_Design_Sync::hooks();
    }
}

register_activation_hook( __FILE__, array( 'VRI_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'VRI_Install', 'deactivate' ) );
add_action( 'plugins_loaded', array( 'VRI_Core', 'boot' ) );

function vri_current_language() {
    $lang = get_query_var( 'vri_lang' );
    return 'it' === $lang ? 'it' : 'en';
}

function vri_get_communities() {
    return VRI_Model::get_communities();
}

function vri_get_home_banners() {
    return VRI_Model::get_home_banners();
}

function vri_get_home_gallery() {
    return VRI_Model::get_home_gallery();
}

function vri_render_application_popup( $lang = 'en' ) {
    VRI_Frontend::render_application_popup( $lang );
}

function vri_render_owner_dashboard( $lang = 'en' ) {
    VRI_Frontend::render_dashboard( $lang );
}

function vri_design_asset_url( $path, $fallback = '' ) {
    return VRI_Design_Sync::asset_url( $path, $fallback );
}
