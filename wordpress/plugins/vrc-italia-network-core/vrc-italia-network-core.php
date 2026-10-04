<?php
/**
 * Plugin Name: VRC Italia Network Core
 * Description: Community management, owner dashboard, applications, events, moderation and GitHub design synchronization for VRC Italia Network.
 * Version: 0.1.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Haxurus
 * Text Domain: vrc-italia-network
 */

defined( 'ABSPATH' ) || exit;

define( 'VRCIN_CORE_VERSION', '0.1.0' );
define( 'VRCIN_CORE_FILE', __FILE__ );
define( 'VRCIN_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VRCIN_CORE_URL', plugin_dir_url( __FILE__ ) );

foreach ( array( 'install', 'model', 'frontend', 'admin', 'design-sync' ) as $vrcin_module ) {
    require_once VRCIN_CORE_DIR . 'includes/class-vrcin-' . $vrcin_module . '.php';
}

final class VRCIN_Core {
    public static function boot() {
        VRCIN_Model::hooks();
        VRCIN_Frontend::hooks();
        VRCIN_Admin::hooks();
        VRCIN_Design_Sync::hooks();
    }
}

register_activation_hook( __FILE__, array( 'VRCIN_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'VRCIN_Install', 'deactivate' ) );
add_action( 'plugins_loaded', array( 'VRCIN_Core', 'boot' ) );

function vrcin_current_language() {
    $lang = get_query_var( 'vrcin_lang' );
    return 'it' === $lang ? 'it' : 'en';
}

function vrcin_get_communities() {
    return VRCIN_Model::get_communities();
}

function vrcin_get_home_banners() {
    return VRCIN_Model::get_home_banners();
}

function vrcin_get_home_gallery() {
    return VRCIN_Model::get_home_gallery();
}

function vrcin_render_application_popup( $lang = 'en' ) {
    VRCIN_Frontend::render_application_popup( $lang );
}

function vrcin_render_owner_dashboard( $lang = 'en' ) {
    VRCIN_Frontend::render_dashboard( $lang );
}

function vrcin_design_asset_url( $path, $fallback = '' ) {
    return VRCIN_Design_Sync::asset_url( $path, $fallback );
}
