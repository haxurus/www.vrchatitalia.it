<?php
defined( 'ABSPATH' ) || exit;

function vrcin_theme_lang() {
    $lang = get_query_var( 'vrcin_lang' );
    return 'it' === $lang ? 'it' : 'en';
}

function vrcin_theme_url( $view = 'home', $lang = null ) {
    $lang = $lang ?: vrcin_theme_lang();
    if ( 'events' === $view ) {
        return home_url( '/' . ( 'it' === $lang ? 'it/eventi/' : 'en/events/' ) );
    }
    if ( 'dashboard' === $view ) {
        return home_url( '/' . ( 'it' === $lang ? 'it/dashboard/' : 'en/dashboard/' ) );
    }
    return home_url( '/' . ( 'it' === $lang ? 'it/' : 'en/' ) );
}

function vrcin_theme_assets() {
    $version = wp_get_theme()->get( 'Version' );
    $css_fallback = get_template_directory_uri() . '/assets/css/site.css';
    $js_fallback = get_template_directory_uri() . '/assets/js/site.js';

    $css = function_exists( 'vrcin_design_asset_url' ) ? vrcin_design_asset_url( 'site.css', $css_fallback ) : $css_fallback;
    $js = function_exists( 'vrcin_design_asset_url' ) ? vrcin_design_asset_url( 'site.js', $js_fallback ) : $js_fallback;

    wp_enqueue_style( 'vrcin-site', $css, array(), $version );
    wp_enqueue_script( 'vrcin-site', $js, array(), $version, true );

    if ( 'events' === get_query_var( 'vrcin_view' ) ) {
        wp_enqueue_style( 'fullcalendar-skeleton', 'https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/skeleton.css', array(), '7.1.0' );
        wp_enqueue_style( 'fullcalendar-classic', 'https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/classic/theme.css', array( 'fullcalendar-skeleton' ), '7.1.0' );
        wp_enqueue_style( 'fullcalendar-palette', 'https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/classic/palette.css', array( 'fullcalendar-classic' ), '7.1.0' );

        wp_enqueue_script( 'temporal-polyfill', 'https://cdn.jsdelivr.net/npm/temporal-polyfill@1.0.3/global.min.js', array(), '1.0.3', true );
        wp_enqueue_script( 'fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/all/global.js', array( 'temporal-polyfill' ), '7.1.0', true );
        wp_enqueue_script( 'fullcalendar-classic', 'https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/classic/global.js', array( 'fullcalendar' ), '7.1.0', true );
        wp_enqueue_script( 'fullcalendar-locales', 'https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/locales-all/global.js', array( 'fullcalendar' ), '7.1.0', true );
        wp_enqueue_script( 'vrcin-events', get_template_directory_uri() . '/assets/js/events.js', array( 'fullcalendar-locales' ), $version, true );
        wp_localize_script(
            'vrcin-events',
            'VRCIN_EVENTS',
            array(
                'endpoint' => esc_url_raw( rest_url( 'vrcin/v1/events' ) ),
                'lang' => vrcin_theme_lang(),
            )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'vrcin_theme_assets', 5 );

add_action(
    'after_setup_theme',
    function() {
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
    }
);
