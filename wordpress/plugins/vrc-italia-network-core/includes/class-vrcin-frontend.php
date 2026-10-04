<?php
defined( 'ABSPATH' ) || exit;

final class VRCIN_Frontend {
    public static function hooks() {
        add_action( 'init', array( __CLASS__, 'rewrite_rules' ) );
        add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
        add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
        add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
        add_action( 'admin_init', array( __CLASS__, 'block_owner_admin' ) );
        add_filter( 'show_admin_bar', array( __CLASS__, 'admin_bar' ) );
        add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
        add_action( 'rest_api_init', array( __CLASS__, 'rest_routes' ) );

        add_action( 'admin_post_nopriv_vrcin_application_submit', array( __CLASS__, 'application_submit' ) );
        add_action( 'admin_post_vrcin_application_submit', array( __CLASS__, 'application_submit' ) );
        add_action( 'admin_post_vrcin_profile_update', array( __CLASS__, 'profile_update' ) );
        add_action( 'admin_post_vrcin_community_update', array( __CLASS__, 'community_update' ) );
        add_action( 'admin_post_vrcin_gallery_upload', array( __CLASS__, 'gallery_upload' ) );
        add_action( 'admin_post_vrcin_gallery_delete', array( __CLASS__, 'gallery_delete' ) );
        add_action( 'admin_post_vrcin_event_save', array( __CLASS__, 'event_save' ) );
        add_action( 'admin_post_vrcin_event_delete', array( __CLASS__, 'event_delete' ) );
        add_action( 'admin_post_vrcin_slot_request_create', array( __CLASS__, 'slot_request_create' ) );
        add_action( 'admin_post_vrcin_application_vote', array( __CLASS__, 'application_vote' ) );
        add_action( 'admin_post_vrcin_slot_vote', array( __CLASS__, 'slot_vote' ) );
    }

    public static function rewrite_rules() {
        add_rewrite_rule( '^it/?$', 'index.php?vrcin_view=home&vrcin_lang=it', 'top' );
        add_rewrite_rule( '^en/?$', 'index.php?vrcin_view=home&vrcin_lang=en', 'top' );
        add_rewrite_rule( '^it/eventi/?$', 'index.php?vrcin_view=events&vrcin_lang=it', 'top' );
        add_rewrite_rule( '^en/events/?$', 'index.php?vrcin_view=events&vrcin_lang=en', 'top' );
        add_rewrite_rule( '^it/dashboard/?$', 'index.php?vrcin_view=dashboard&vrcin_lang=it', 'top' );
        add_rewrite_rule( '^en/dashboard/?$', 'index.php?vrcin_view=dashboard&vrcin_lang=en', 'top' );
    }

    public static function query_vars( $vars ) {
        $vars[] = 'vrcin_view';
        $vars[] = 'vrcin_lang';
        return $vars;
    }

    public static function template_include( $template ) {
        $view = get_query_var( 'vrcin_view' );
        if ( ! $view ) {
            return $template;
        }

        $file = '';
        if ( 'home' === $view ) {
            $file = locate_template( 'front-page.php' );
        } elseif ( 'events' === $view ) {
            $file = locate_template( 'vrcin-events.php' );
        } elseif ( 'dashboard' === $view ) {
            $file = locate_template( 'vrcin-dashboard.php' );
        }

        return $file ? $file : $template;
    }

    public static function template_redirect() {
        if ( isset( $_GET['vrcin_verify'], $_GET['vrcin_application'] ) ) {
            $token = sanitize_text_field( wp_unslash( $_GET['vrcin_verify'] ) );
            $id = absint( $_GET['vrcin_application'] );
            $lang = isset( $_GET['lang'] ) && 'it' === $_GET['lang'] ? 'it' : 'en';
            $result = VRCIN_Model::verify_application( $id, $token );
            $status = is_wp_error( $result ) ? 'verify-error' : 'verified';
            wp_safe_redirect( home_url( '/' . $lang . '/?vrcin_application_status=' . $status ) );
            exit;
        }

        $home_path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
        $request_path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ), '/' );
        if ( $request_path === $home_path ) {
            if ( is_admin() || wp_doing_ajax() || defined( 'REST_REQUEST' ) ) {
                return;
            }
            $accept = strtolower( sanitize_text_field( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '' ) );
            $target = preg_match( '/(^|,|;)\s*it(?:-|,|;|$)/', $accept ) ? 'it' : 'en';
            wp_safe_redirect( home_url( '/' . $target . '/' ) );
            exit;
        }
    }

    public static function block_owner_admin() {
        if ( ! is_user_logged_in() || current_user_can( 'manage_options' ) ) {
            return;
        }
        $user = wp_get_current_user();
        if ( ! in_array( 'vrcin_community_owner', (array) $user->roles, true ) ) {
            return;
        }

        global $pagenow;
        if ( in_array( $pagenow, array( 'admin-post.php', 'admin-ajax.php', 'async-upload.php' ), true ) ) {
            return;
        }

        wp_safe_redirect( home_url( '/it/dashboard/' ) );
        exit;
    }

    public static function admin_bar( $show ) {
        if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
            $user = wp_get_current_user();
            if ( in_array( 'vrcin_community_owner', (array) $user->roles, true ) ) {
                return false;
            }
        }
        return $show;
    }

    public static function login_redirect( $redirect_to, $requested, $user ) {
        if ( $user instanceof WP_User && in_array( 'vrcin_community_owner', (array) $user->roles, true ) ) {
            return home_url( '/it/dashboard/' );
        }
        return $redirect_to;
    }

    public static function assets() {
        $view = get_query_var( 'vrcin_view' );
        if ( 'dashboard' === $view ) {
            wp_enqueue_style( 'vrcin-dashboard', VRCIN_CORE_URL . 'assets/frontend.css', array(), VRCIN_CORE_VERSION );
            wp_enqueue_script( 'vrcin-dashboard', VRCIN_CORE_URL . 'assets/frontend.js', array(), VRCIN_CORE_VERSION, true );
        }

        if ( 'home' === $view ) {
            wp_enqueue_style( 'vrcin-application', VRCIN_CORE_URL . 'assets/frontend.css', array(), VRCIN_CORE_VERSION );
            wp_enqueue_script( 'vrcin-application', VRCIN_CORE_URL . 'assets/frontend.js', array(), VRCIN_CORE_VERSION, true );
        }
    }

    public static function rest_routes() {
        register_rest_route(
            'vrcin/v1',
            '/events',
            array(
                'methods' => WP_REST_Server::READABLE,
                'permission_callback' => '__return_true',
                'callback' => array( __CLASS__, 'rest_events' ),
                'args' => array(
                    'start' => array( 'sanitize_callback' => 'sanitize_text_field' ),
                    'end' => array( 'sanitize_callback' => 'sanitize_text_field' ),
                    'lang' => array( 'sanitize_callback' => 'sanitize_key' ),
                ),
            )
        );
    }

    public static function rest_events( WP_REST_Request $request ) {
        $lang = 'it' === $request->get_param( 'lang' ) ? 'it' : 'en';
        $rows = VRCIN_Model::public_events( $request->get_param( 'start' ), $request->get_param( 'end' ) );
        $events = array();

        foreach ( $rows as $row ) {
            $title = 'it' === $lang ? $row['title_it'] : $row['title_en'];
            if ( '' === $title ) {
                $title = $row['title_it'] ?: $row['title_en'];
            }
            $description = 'it' === $lang ? $row['description_it'] : $row['description_en'];
            if ( '' === $description ) {
                $description = $row['description_it'] ?: $row['description_en'];
            }

            $events[] = array(
                'id' => (int) $row['id'],
                'title' => $title,
                'start' => mysql2date( 'c', $row['start_at_utc'], false ),
                'end' => mysql2date( 'c', $row['end_at_utc'], false ),
                'classNames' => array( 'vrcin-calendar-event', 'event-tag-' . ( explode( ',', $row['tags'] )[0] ?? 'other' ) ),
                'extendedProps' => array(
                    'community' => $row['community_name'],
                    'description' => $description,
                    'world' => $row['world_name'],
                    'access' => $row['access_type'],
                    'platforms' => array_values( array_filter( explode( ',', $row['platforms'] ) ) ),
                    'capacity' => $row['capacity'] ? (int) $row['capacity'] : null,
                    'registrationUrl' => $row['registration_url'],
                    'eventUrl' => $row['event_url'],
                    'groupUrl' => $row['vrchat_group_url'],
                    'tags' => array_values( array_filter( explode( ',', $row['tags'] ) ) ),
                ),
            );
        }

        return rest_ensure_response( $events );
    }

    private static function dashboard_url( $lang = 'it', $args = array() ) {
        $url = home_url( '/' . ( 'it' === $lang ? 'it/dashboard/' : 'en/dashboard/' ) );
        return $args ? add_query_arg( $args, $url ) : $url;
    }

    private static function current_lang() {
        return 'it' === get_query_var( 'vrcin_lang' ) ? 'it' : 'en';
    }

    private static function redirect_dashboard( $status, $tab = '' ) {
        $lang = self::current_lang();
        $args = array( 'vrcin_status' => rawurlencode( $status ) );
        if ( $tab ) {
            $args['tab'] = sanitize_key( $tab );
        }
        wp_safe_redirect( self::dashboard_url( $lang, $args ) );
        exit;
    }

    private static function require_owner() {
        if ( ! is_user_logged_in() || ! current_user_can( 'vrcin_manage_community' ) ) {
            wp_die( esc_html__( 'Not authorized.', 'vrc-italia-network' ), '', array( 'response' => 403 ) );
        }
    }

    public static function application_submit() {
        check_admin_referer( 'vrcin_application_submit' );
        $lang = isset( $_POST['lang'] ) && 'it' === $_POST['lang'] ? 'it' : 'en';
        $fields = get_option( 'vrcin_form_fields', array() );
        $payload = array();

        foreach ( $fields as $field ) {
            $key = sanitize_key( $field['key'] ?? '' );
            if ( ! $key ) {
                continue;
            }
            $type = sanitize_key( $field['type'] ?? 'text' );
            $raw = $_POST[ $key ] ?? '';

            if ( 'checkbox' === $type ) {
                $value = empty( $raw ) ? '0' : '1';
            } elseif ( 'email' === $type ) {
                $value = sanitize_email( wp_unslash( $raw ) );
            } elseif ( 'url' === $type ) {
                $value = esc_url_raw( wp_unslash( $raw ) );
            } elseif ( 'number' === $type ) {
                $value = is_numeric( $raw ) ? (string) (float) $raw : '';
            } else {
                $value = 'textarea' === $type ? sanitize_textarea_field( wp_unslash( $raw ) ) : sanitize_text_field( wp_unslash( $raw ) );
            }

            if ( ! empty( $field['required'] ) && '' === (string) $value ) {
                wp_safe_redirect( home_url( '/' . $lang . '/?vrcin_application_status=missing' ) );
                exit;
            }
            $payload[ $key ] = $value;
        }

        $result = VRCIN_Model::create_application( $payload, $lang );
        $status = is_wp_error( $result ) ? $result->get_error_code() : 'check-email';
        wp_safe_redirect( home_url( '/' . $lang . '/?vrcin_application_status=' . rawurlencode( $status ) ) );
        exit;
    }

    public static function profile_update() {
        self::require_owner();
        check_admin_referer( 'vrcin_profile_update' );
        $user_id = get_current_user_id();

        $args = array(
            'ID' => $user_id,
            'display_name' => sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) ),
            'user_email' => sanitize_email( wp_unslash( $_POST['user_email'] ?? '' ) ),
        );

        if ( ! empty( $_POST['new_password'] ) ) {
            $password = (string) wp_unslash( $_POST['new_password'] );
            if ( strlen( $password ) < 12 ) {
                self::redirect_dashboard( 'password-short', 'profile' );
            }
            $args['user_pass'] = $password;
        }

        $result = wp_update_user( $args );
        self::redirect_dashboard( is_wp_error( $result ) ? $result->get_error_code() : 'profile-saved', 'profile' );
    }

    private static function upload_image( $field ) {
        if ( empty( $_FILES[ $field ]['name'] ) ) {
            return 0;
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $id = media_handle_upload( $field, 0 );
        if ( is_wp_error( $id ) ) {
            return $id;
        }
        $mime = get_post_mime_type( $id );
        if ( 0 !== strpos( (string) $mime, 'image/' ) ) {
            wp_delete_attachment( $id, true );
            return new WP_Error( 'vrcin_image', 'Only images are allowed.' );
        }
        return (int) $id;
    }

    public static function community_update() {
        self::require_owner();
        check_admin_referer( 'vrcin_community_update' );
        $community = VRCIN_Model::get_owner_community();
        if ( ! $community ) {
            self::redirect_dashboard( 'community-missing', 'community' );
        }

        $data = array(
            'name' => wp_unslash( $_POST['name'] ?? '' ),
            'discord_url' => wp_unslash( $_POST['discord_url'] ?? '' ),
            'vrchat_group_url' => wp_unslash( $_POST['vrchat_group_url'] ?? '' ),
            'vrchat_world_url' => wp_unslash( $_POST['vrchat_world_url'] ?? '' ),
            'instagram_url' => wp_unslash( $_POST['instagram_url'] ?? '' ),
            'website_url' => wp_unslash( $_POST['website_url'] ?? '' ),
            'description_it' => wp_unslash( $_POST['description_it'] ?? '' ),
            'description_en' => wp_unslash( $_POST['description_en'] ?? '' ),
        );

        $banner = self::upload_image( 'banner' );
        if ( is_wp_error( $banner ) ) {
            self::redirect_dashboard( $banner->get_error_code(), 'community' );
        }
        if ( $banner ) {
            $data['banner_attachment_id'] = $banner;
        }

        $logo = self::upload_image( 'logo' );
        if ( is_wp_error( $logo ) ) {
            self::redirect_dashboard( $logo->get_error_code(), 'community' );
        }
        if ( $logo ) {
            $data['logo_attachment_id'] = $logo;
        }

        $result = VRCIN_Model::update_community( $community['id'], get_current_user_id(), $data );
        self::redirect_dashboard( is_wp_error( $result ) ? $result->get_error_code() : 'community-saved', 'community' );
    }

    public static function gallery_upload() {
        self::require_owner();
        check_admin_referer( 'vrcin_gallery_upload' );
        $community = VRCIN_Model::get_owner_community();
        $image = self::upload_image( 'community_image' );
        if ( is_wp_error( $image ) ) {
            self::redirect_dashboard( $image->get_error_code(), 'gallery' );
        }
        if ( ! $image ) {
            self::redirect_dashboard( 'image-missing', 'gallery' );
        }

        $result = VRCIN_Model::add_gallery_image( $community['id'], get_current_user_id(), $image );
        self::redirect_dashboard( is_wp_error( $result ) ? $result->get_error_code() : 'image-pending', 'gallery' );
    }

    public static function gallery_delete() {
        self::require_owner();
        check_admin_referer( 'vrcin_gallery_delete' );
        $result = VRCIN_Model::delete_gallery_image( absint( $_POST['image_id'] ?? 0 ), get_current_user_id() );
        self::redirect_dashboard( is_wp_error( $result ) ? $result->get_error_code() : 'image-deleted', 'gallery' );
    }

    public static function event_save() {
        self::require_owner();
        check_admin_referer( 'vrcin_event_save' );
        $community = VRCIN_Model::get_owner_community();
        $data = array(
            'title_it' => wp_unslash( $_POST['title_it'] ?? '' ),
            'title_en' => wp_unslash( $_POST['title_en'] ?? '' ),
            'description_it' => wp_unslash( $_POST['description_it'] ?? '' ),
            'description_en' => wp_unslash( $_POST['description_en'] ?? '' ),
            'start_local' => wp_unslash( $_POST['start_local'] ?? '' ),
            'end_local' => wp_unslash( $_POST['end_local'] ?? '' ),
            'world_name' => wp_unslash( $_POST['world_name'] ?? '' ),
            'access_type' => wp_unslash( $_POST['access_type'] ?? 'group' ),
            'platforms' => isset( $_POST['platforms'] ) ? (array) wp_unslash( $_POST['platforms'] ) : array(),
            'capacity' => wp_unslash( $_POST['capacity'] ?? '' ),
            'registration_url' => wp_unslash( $_POST['registration_url'] ?? '' ),
            'event_url' => wp_unslash( $_POST['event_url'] ?? '' ),
            'tags' => isset( $_POST['tags'] ) ? (array) wp_unslash( $_POST['tags'] ) : array(),
        );

        $result = VRCIN_Model::save_event_draft(
            $community['id'],
            get_current_user_id(),
            absint( $_POST['event_id'] ?? 0 ),
            $data
        );

        if ( is_wp_error( $result ) ) {
            self::redirect_dashboard( $result->get_error_code(), 'events' );
        }
        self::redirect_dashboard( ! empty( $result['blocked'] ) ? 'event-slot-blocked' : 'event-pending-admin', 'events' );
    }

    public static function event_delete() {
        self::require_owner();
        check_admin_referer( 'vrcin_event_delete' );
        $result = VRCIN_Model::delete_event( absint( $_POST['event_id'] ?? 0 ), get_current_user_id() );
        self::redirect_dashboard( is_wp_error( $result ) ? $result->get_error_code() : 'event-deleted', 'events' );
    }

    public static function slot_request_create() {
        self::require_owner();
        check_admin_referer( 'vrcin_slot_request_create' );
        $result = VRCIN_Model::create_slot_request( absint( $_POST['event_id'] ?? 0 ), get_current_user_id() );
        self::redirect_dashboard( is_wp_error( $result ) ? $result->get_error_code() : 'slot-requested', 'events' );
    }

    public static function application_vote() {
        self::require_owner();
        check_admin_referer( 'vrcin_application_vote' );
        $result = VRCIN_Model::cast_application_vote(
            absint( $_POST['application_id'] ?? 0 ),
            get_current_user_id(),
            sanitize_key( $_POST['vote'] ?? '' ),
            wp_unslash( $_POST['comment'] ?? '' )
        );
        self::redirect_dashboard( is_wp_error( $result ) ? $result->get_error_code() : 'vote-saved', 'votes' );
    }

    public static function slot_vote() {
        self::require_owner();
        check_admin_referer( 'vrcin_slot_vote' );
        $result = VRCIN_Model::cast_slot_vote(
            absint( $_POST['request_id'] ?? 0 ),
            get_current_user_id(),
            sanitize_key( $_POST['vote'] ?? '' )
        );
        self::redirect_dashboard( is_wp_error( $result ) ? $result->get_error_code() : 'slot-vote-saved', 'votes' );
    }

    public static function render_application_popup( $lang = 'en' ) {
        $lang = 'it' === $lang ? 'it' : 'en';
        $fields = get_option( 'vrcin_form_fields', array() );
        $settings = get_option( 'vrcin_form_settings', array() );
        $title = $settings[ 'title_' . $lang ] ?? '';
        $intro = $settings[ 'intro_' . $lang ] ?? '';
        $submit = $settings[ 'submit_' . $lang ] ?? 'Submit';
        $style = sprintf(
            '--vrcin-form-accent:%s;--vrcin-form-bg-dark:%s;--vrcin-form-text-dark:%s;--vrcin-form-bg-light:%s;--vrcin-form-text-light:%s;--vrcin-form-radius:%dpx;--vrcin-form-width:%dpx;',
            esc_attr( $settings['accent'] ?? '#138a4b' ),
            esc_attr( $settings['background_dark'] ?? '#0d110e' ),
            esc_attr( $settings['text_dark'] ?? '#f4f1e8' ),
            esc_attr( $settings['background_light'] ?? '#f4f1e8' ),
            esc_attr( $settings['text_light'] ?? '#172019' ),
            absint( $settings['radius'] ?? 20 ),
            absint( $settings['max_width'] ?? 760 )
        );
        ?>
        <div class="vrcin-modal" id="vrcin-application-modal" hidden style="<?php echo esc_attr( $style ); ?>">
            <button class="vrcin-modal__backdrop" type="button" data-vrcin-close aria-label="Close"></button>
            <section class="vrcin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="vrcin-application-title">
                <button class="vrcin-modal__close" type="button" data-vrcin-close aria-label="Close">×</button>
                <h2 id="vrcin-application-title"><?php echo esc_html( $title ); ?></h2>
                <p><?php echo esc_html( $intro ); ?></p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="vrcin_application_submit">
                    <input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>">
                    <?php wp_nonce_field( 'vrcin_application_submit' ); ?>
                    <div class="vrcin-form-grid">
                        <?php foreach ( $fields as $field ) :
                            $key = sanitize_key( $field['key'] ?? '' );
                            if ( ! $key ) { continue; }
                            $type = sanitize_key( $field['type'] ?? 'text' );
                            $label = $field[ 'label_' . $lang ] ?? $key;
                            $required = ! empty( $field['required'] );
                            ?>
                            <label class="vrcin-form-field vrcin-form-field--<?php echo esc_attr( $type ); ?>">
                                <span><?php echo esc_html( $label ); ?><?php echo $required ? ' *' : ''; ?></span>
                                <?php if ( 'textarea' === $type ) : ?>
                                    <textarea name="<?php echo esc_attr( $key ); ?>" <?php required( $required ); ?>></textarea>
                                <?php elseif ( 'checkbox' === $type ) : ?>
                                    <input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" <?php required( $required ); ?>>
                                <?php elseif ( 'select' === $type ) : ?>
                                    <select name="<?php echo esc_attr( $key ); ?>" <?php required( $required ); ?>>
                                        <?php foreach ( (array) ( $field['options'] ?? array() ) as $option ) : ?>
                                            <option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else : ?>
                                    <input type="<?php echo esc_attr( in_array( $type, array( 'email', 'url', 'number' ), true ) ? $type : 'text' ); ?>" name="<?php echo esc_attr( $key ); ?>" <?php required( $required ); ?>>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button class="button button-primary vrcin-form-submit" type="submit"><?php echo esc_html( $submit ); ?></button>
                </form>
            </section>
        </div>
        <?php if ( ! empty( $settings['custom_css'] ) ) : ?>
            <style><?php echo wp_strip_all_tags( $settings['custom_css'] ); ?></style>
        <?php endif;
    }

    public static function render_dashboard( $lang = 'en' ) {
        $lang = 'it' === $lang ? 'it' : 'en';
        if ( ! is_user_logged_in() ) {
            echo '<div class="vrcin-dashboard-login">';
            echo '<h1>' . esc_html( 'it' === $lang ? 'Dashboard community' : 'Community dashboard' ) . '</h1>';
            wp_login_form(
                array(
                    'redirect' => self::dashboard_url( $lang ),
                    'remember' => true,
                )
            );
            echo '</div>';
            return;
        }

        if ( ! current_user_can( 'vrcin_manage_community' ) ) {
            echo '<div class="vrcin-dashboard-login"><p>' . esc_html( 'it' === $lang ? 'Questo account non gestisce una community.' : 'This account does not manage a community.' ) . '</p></div>';
            return;
        }

        $user = wp_get_current_user();
        $community = VRCIN_Model::get_owner_community( $user->ID );
        if ( ! $community ) {
            echo '<div class="vrcin-dashboard-login"><p>Community not found.</p></div>';
            return;
        }

        $tab = sanitize_key( $_GET['tab'] ?? 'overview' );
        $tabs = array(
            'overview' => 'it' === $lang ? 'Panoramica' : 'Overview',
            'community' => 'Community',
            'events' => 'it' === $lang ? 'Eventi' : 'Events',
            'gallery' => 'Gallery',
            'votes' => 'it' === $lang ? 'Votazioni' : 'Votes',
            'profile' => 'it' === $lang ? 'Profilo' : 'Profile',
        );

        $application_alerts = VRCIN_Model::pending_applications_for_owner( $user->ID );
        $slot_alerts = VRCIN_Model::pending_slot_requests_for_owner( $user->ID );
        $alert_count = count( $application_alerts ) + count( $slot_alerts );

        echo '<div class="vrcin-dashboard">';
        echo '<header class="vrcin-dashboard__header"><div><span>VRC Italia Network</span><h1>' . esc_html( $community['name'] ) . '</h1></div><div class="vrcin-dashboard__user">' . esc_html( $user->display_name ) . ' · <a href="' . esc_url( wp_logout_url( home_url( '/' . $lang . '/' ) ) ) . '">' . esc_html( 'it' === $lang ? 'Esci' : 'Log out' ) . '</a></div></header>';

        if ( isset( $_GET['vrcin_status'] ) ) {
            echo '<div class="vrcin-dashboard__notice">' . esc_html( sanitize_text_field( wp_unslash( $_GET['vrcin_status'] ) ) ) . '</div>';
        }

        echo '<nav class="vrcin-dashboard__tabs">';
        foreach ( $tabs as $key => $label ) {
            $url = self::dashboard_url( $lang, array( 'tab' => $key ) );
            $badge = 'votes' === $key && $alert_count ? '<b>' . absint( $alert_count ) . '</b>' : '';
            echo '<a class="' . esc_attr( $tab === $key ? 'is-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . $badge . '</a>';
        }
        echo '</nav>';

        echo '<main class="vrcin-dashboard__content">';
        if ( 'community' === $tab ) {
            self::render_community_tab( $community, $lang );
        } elseif ( 'events' === $tab ) {
            self::render_events_tab( $community, $lang );
        } elseif ( 'gallery' === $tab ) {
            self::render_gallery_tab( $community, $lang );
        } elseif ( 'votes' === $tab ) {
            self::render_votes_tab( $application_alerts, $slot_alerts, $lang );
        } elseif ( 'profile' === $tab ) {
            self::render_profile_tab( $user, $lang );
        } else {
            self::render_overview_tab( $community, $alert_count, $lang );
        }
        echo '</main></div>';
    }

    private static function render_overview_tab( $community, $alert_count, $lang ) {
        $events = VRCIN_Model::owner_events( $community['id'] );
        $images = VRCIN_Model::gallery_images( $community['id'] );
        echo '<div class="vrcin-dashboard-grid">';
        echo '<article class="vrcin-dashboard-card"><span>Community</span><strong>' . esc_html( $community['name'] ) . '</strong><p>' . esc_html( 'it' === $lang ? 'Gestisci dati pubblici, logo e banner.' : 'Manage public information, logo and banner.' ) . '</p></article>';
        echo '<article class="vrcin-dashboard-card"><span>' . esc_html( 'it' === $lang ? 'Eventi' : 'Events' ) . '</span><strong>' . count( $events ) . '</strong><p>' . esc_html( 'it' === $lang ? 'Eventi creati o in moderazione.' : 'Created events or events under moderation.' ) . '</p></article>';
        echo '<article class="vrcin-dashboard-card"><span>Gallery</span><strong>' . count( $images ) . '/10</strong><p>' . esc_html( 'it' === $lang ? 'Immagini approvate o in revisione.' : 'Approved images or images under review.' ) . '</p></article>';
        echo '<article class="vrcin-dashboard-card"><span>' . esc_html( 'it' === $lang ? 'Azioni richieste' : 'Actions required' ) . '</span><strong>' . absint( $alert_count ) . '</strong><p>' . esc_html( 'it' === $lang ? 'Candidature o richieste data da votare.' : 'Applications or date requests waiting for your vote.' ) . '</p></article>';
        echo '</div>';
    }

    private static function render_community_tab( $community, $lang ) {
        ?>
        <form class="vrcin-dashboard-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="vrcin_community_update">
            <?php wp_nonce_field( 'vrcin_community_update' ); ?>
            <h2><?php echo esc_html( 'it' === $lang ? 'Informazioni community' : 'Community information' ); ?></h2>
            <div class="vrcin-form-grid">
                <label><span><?php echo esc_html( 'it' === $lang ? 'Nome' : 'Name' ); ?></span><input name="name" required value="<?php echo esc_attr( $community['name'] ); ?>"></label>
                <label><span>Discord</span><input type="url" name="discord_url" value="<?php echo esc_attr( $community['discord_url'] ); ?>"></label>
                <label><span>VRChat Group</span><input type="url" name="vrchat_group_url" value="<?php echo esc_attr( $community['vrchat_group_url'] ); ?>"></label>
                <label><span>VRChat World</span><input type="url" name="vrchat_world_url" value="<?php echo esc_attr( $community['vrchat_world_url'] ); ?>"></label>
                <label><span>Instagram</span><input type="url" name="instagram_url" value="<?php echo esc_attr( $community['instagram_url'] ); ?>"></label>
                <label><span>Website</span><input type="url" name="website_url" value="<?php echo esc_attr( $community['website_url'] ); ?>"></label>
                <label class="vrcin-form-field--wide"><span>Descrizione IT - max 250</span><textarea name="description_it" maxlength="250"><?php echo esc_textarea( $community['description_it'] ); ?></textarea></label>
                <label class="vrcin-form-field--wide"><span>Description EN - max 250</span><textarea name="description_en" maxlength="250"><?php echo esc_textarea( $community['description_en'] ); ?></textarea></label>
                <label><span>Banner</span><input type="file" name="banner" accept="image/*"></label>
                <label><span>Logo / Icon</span><input type="file" name="logo" accept="image/*"></label>
            </div>
            <button class="button button-primary" type="submit"><?php echo esc_html( 'it' === $lang ? 'Salva modifiche' : 'Save changes' ); ?></button>
        </form>
        <?php
    }

    private static function render_events_tab( $community, $lang ) {
        $events = VRCIN_Model::owner_events( $community['id'] );
        $edit_id = absint( $_GET['edit_event'] ?? 0 );
        $edit = null;
        foreach ( $events as $event ) {
            if ( (int) $event['id'] === $edit_id ) {
                $edit = $event;
                break;
            }
        }
        $payload = $edit && $edit['pending_payload'] ? json_decode( $edit['pending_payload'], true ) : null;
        if ( ! $payload && $edit ) {
            $payload = $edit;
        }
        $payload = is_array( $payload ) ? $payload : array();
        $start_local = ! empty( $payload['start_at_utc'] ) ? VRCIN_Model::event_local_input( $payload['start_at_utc'] ) : '';
        $end_local = ! empty( $payload['end_at_utc'] ) ? VRCIN_Model::event_local_input( $payload['end_at_utc'] ) : '';
        $selected_tags = array_filter( explode( ',', $payload['tags'] ?? '' ) );
        $selected_platforms = array_filter( explode( ',', $payload['platforms'] ?? '' ) );
        ?>
        <div class="vrcin-events-manager">
            <form class="vrcin-dashboard-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="vrcin_event_save">
                <input type="hidden" name="event_id" value="<?php echo esc_attr( $edit_id ); ?>">
                <?php wp_nonce_field( 'vrcin_event_save' ); ?>
                <h2><?php echo esc_html( $edit ? ( 'it' === $lang ? 'Modifica evento' : 'Edit event' ) : ( 'it' === $lang ? 'Nuovo evento' : 'New event' ) ); ?></h2>
                <div class="vrcin-form-grid">
                    <label><span>Titolo IT</span><input name="title_it" value="<?php echo esc_attr( $payload['title_it'] ?? '' ); ?>"></label>
                    <label><span>Title EN</span><input name="title_en" value="<?php echo esc_attr( $payload['title_en'] ?? '' ); ?>"></label>
                    <label class="vrcin-form-field--wide"><span>Descrizione IT</span><textarea name="description_it"><?php echo esc_textarea( $payload['description_it'] ?? '' ); ?></textarea></label>
                    <label class="vrcin-form-field--wide"><span>Description EN</span><textarea name="description_en"><?php echo esc_textarea( $payload['description_en'] ?? '' ); ?></textarea></label>
                    <label><span><?php echo esc_html( 'it' === $lang ? 'Inizio' : 'Start' ); ?></span><input type="datetime-local" name="start_local" required value="<?php echo esc_attr( $start_local ); ?>"></label>
                    <label><span><?php echo esc_html( 'it' === $lang ? 'Fine' : 'End' ); ?></span><input type="datetime-local" name="end_local" required value="<?php echo esc_attr( $end_local ); ?>"></label>
                    <label><span>World</span><input name="world_name" value="<?php echo esc_attr( $payload['world_name'] ?? '' ); ?>"></label>
                    <label><span><?php echo esc_html( 'it' === $lang ? 'Accesso' : 'Access' ); ?></span>
                        <select name="access_type">
                            <?php foreach ( array( 'public', 'group', 'friends', 'private' ) as $access ) : ?>
                                <option value="<?php echo esc_attr( $access ); ?>" <?php selected( $payload['access_type'] ?? 'group', $access ); ?>><?php echo esc_html( ucfirst( $access ) ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <fieldset class="vrcin-form-field--wide"><legend>Platform</legend>
                        <?php foreach ( array( 'PC', 'Quest', 'Android' ) as $platform ) : ?>
                            <label class="vrcin-inline-check"><input type="checkbox" name="platforms[]" value="<?php echo esc_attr( $platform ); ?>" <?php checked( in_array( $platform, $selected_platforms, true ) ); ?>> <?php echo esc_html( $platform ); ?></label>
                        <?php endforeach; ?>
                    </fieldset>
                    <fieldset class="vrcin-form-field--wide"><legend>Tag</legend>
                        <?php
                        $tags = array(
                            'gaming' => 'Gaming Night',
                            'drinking' => 'Drinking Night',
                            'world-exploration' => 'it' === $lang ? 'Esplorazione Mondi' : 'World Exploration',
                            'dj-disco' => 'DJ Set / Disco Night',
                            'hangout' => 'Relax / Hangout',
                            'age-gated' => 'it' === $lang ? 'Solo 18+' : 'Age Gated (18+)',
                            'other' => 'it' === $lang ? 'Altro' : 'Other',
                        );
                        foreach ( $tags as $key => $label ) : ?>
                            <label class="vrcin-inline-check"><input type="checkbox" name="tags[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $selected_tags, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
                        <?php endforeach; ?>
                    </fieldset>
                    <label><span><?php echo esc_html( 'it' === $lang ? 'Capienza' : 'Capacity' ); ?></span><input type="number" min="1" name="capacity" value="<?php echo esc_attr( $payload['capacity'] ?? '' ); ?>"></label>
                    <label><span>Registration URL</span><input type="url" name="registration_url" value="<?php echo esc_attr( $payload['registration_url'] ?? '' ); ?>"></label>
                    <label class="vrcin-form-field--wide"><span>Event URL</span><input type="url" name="event_url" value="<?php echo esc_attr( $payload['event_url'] ?? '' ); ?>"></label>
                </div>
                <button class="button button-primary" type="submit"><?php echo esc_html( 'it' === $lang ? 'Invia in approvazione' : 'Submit for approval' ); ?></button>
            </form>

            <div class="vrcin-dashboard-list">
                <h2><?php echo esc_html( 'it' === $lang ? 'I tuoi eventi' : 'Your events' ); ?></h2>
                <?php foreach ( $events as $event ) :
                    $state = $event['pending_status'] ?: $event['status'];
                    ?>
                    <article>
                        <div><strong><?php echo esc_html( $event['title_it'] ?: $event['title_en'] ?: '#' . $event['id'] ); ?></strong><span><?php echo esc_html( $state ); ?></span></div>
                        <div class="vrcin-row-actions">
                            <a class="button button-secondary" href="<?php echo esc_url( self::dashboard_url( $lang, array( 'tab' => 'events', 'edit_event' => $event['id'] ) ) ); ?>"><?php echo esc_html( 'it' === $lang ? 'Modifica' : 'Edit' ); ?></a>
                            <?php if ( in_array( $event['pending_status'], array( 'blocked', 'slot_rejected' ), true ) ) : ?>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                    <input type="hidden" name="action" value="vrcin_slot_request_create"><input type="hidden" name="event_id" value="<?php echo esc_attr( $event['id'] ); ?>">
                                    <?php wp_nonce_field( 'vrcin_slot_request_create' ); ?>
                                    <button class="button button-primary" type="submit"><?php echo esc_html( 'it' === $lang ? 'Richiedi data' : 'Request date' ); ?></button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Delete event?')">
                                <input type="hidden" name="action" value="vrcin_event_delete"><input type="hidden" name="event_id" value="<?php echo esc_attr( $event['id'] ); ?>">
                                <?php wp_nonce_field( 'vrcin_event_delete' ); ?>
                                <button class="button button-secondary" type="submit"><?php echo esc_html( 'it' === $lang ? 'Elimina' : 'Delete' ); ?></button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private static function render_gallery_tab( $community, $lang ) {
        $images = VRCIN_Model::gallery_images( $community['id'] );
        ?>
        <form class="vrcin-dashboard-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="vrcin_gallery_upload">
            <?php wp_nonce_field( 'vrcin_gallery_upload' ); ?>
            <h2><?php echo esc_html( 'it' === $lang ? 'Immagini community' : 'Community images' ); ?> <small><?php echo count( $images ); ?>/10</small></h2>
            <p><?php echo esc_html( 'it' === $lang ? 'Le nuove immagini devono essere approvate da un amministratore prima di comparire in home.' : 'New images must be approved by an administrator before appearing on the homepage.' ); ?></p>
            <input type="file" name="community_image" accept="image/*" required>
            <button class="button button-primary" type="submit"><?php echo esc_html( 'it' === $lang ? 'Carica immagine' : 'Upload image' ); ?></button>
        </form>
        <div class="vrcin-gallery-manager">
            <?php foreach ( $images as $image ) : ?>
                <article>
                    <?php echo wp_get_attachment_image( $image['attachment_id'], 'medium' ); ?>
                    <span><?php echo esc_html( $image['status'] ); ?></span>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="vrcin_gallery_delete"><input type="hidden" name="image_id" value="<?php echo esc_attr( $image['id'] ); ?>">
                        <?php wp_nonce_field( 'vrcin_gallery_delete' ); ?>
                        <button class="button button-secondary" type="submit"><?php echo esc_html( 'it' === $lang ? 'Elimina' : 'Delete' ); ?></button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private static function render_votes_tab( $applications, $slots, $lang ) {
        echo '<h2>' . esc_html( 'it' === $lang ? 'Votazioni richieste' : 'Votes required' ) . '</h2>';
        if ( ! $applications && ! $slots ) {
            echo '<p>' . esc_html( 'it' === $lang ? 'Nessuna votazione in attesa.' : 'No votes are waiting for you.' ) . '</p>';
        }

        foreach ( $applications as $application ) {
            echo '<article class="vrcin-vote-card"><span>' . esc_html( 'it' === $lang ? 'Nuova community' : 'New community' ) . '</span><h3>' . esc_html( $application['community_name'] ) . '</h3>';
            echo '<dl>';
            foreach ( $application['payload_array'] as $key => $value ) {
                if ( 'owner_email' === $key ) { continue; }
                echo '<div><dt>' . esc_html( str_replace( '_', ' ', $key ) ) . '</dt><dd>' . esc_html( is_array( $value ) ? implode( ', ', $value ) : $value ) . '</dd></div>';
            }
            echo '</dl>';
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="vrcin_application_vote"><input type="hidden" name="application_id" value="' . esc_attr( $application['id'] ) . '">';
            wp_nonce_field( 'vrcin_application_vote' );
            echo '<label><span>' . esc_html( 'it' === $lang ? 'Commento obbligatorio (10-250 caratteri)' : 'Required comment (10-250 characters)' ) . '</span><textarea name="comment" minlength="10" maxlength="250" required></textarea></label>';
            echo '<div class="vrcin-row-actions"><button class="button button-primary" name="vote" value="yes" type="submit">Sì / Yes</button><button class="button button-secondary" name="vote" value="no" type="submit">No</button></div></form></article>';
        }

        foreach ( $slots as $slot ) {
            $payload = $slot['payload_array'];
            $start = ! empty( $payload['start_at_utc'] ) ? VRCIN_Model::event_local_display( $payload['start_at_utc'], $lang ) : '';
            $end = ! empty( $payload['end_at_utc'] ) ? VRCIN_Model::event_local_display( $payload['end_at_utc'], $lang ) : '';
            echo '<article class="vrcin-vote-card"><span>' . esc_html( 'it' === $lang ? 'Richiesta data' : 'Date request' ) . '</span><h3>' . esc_html( $slot['community_name'] ) . '</h3><p>' . esc_html( $start . ' - ' . $end ) . '</p>';
            echo '<p>' . esc_html( 'it' === $lang ? 'Il voto è anonimo verso gli altri proprietari. Chi non vota entro 48 ore viene conteggiato automaticamente come Sì.' : 'The vote is anonymous to other owners. Missing votes become Yes automatically after 48 hours.' ) . '</p>';
            echo '<form class="vrcin-row-actions" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="vrcin_slot_vote"><input type="hidden" name="request_id" value="' . esc_attr( $slot['id'] ) . '">';
            wp_nonce_field( 'vrcin_slot_vote' );
            echo '<button class="button button-primary" name="vote" value="yes" type="submit">Sì / Yes</button><button class="button button-secondary" name="vote" value="no" type="submit">No</button></form></article>';
        }
    }

    private static function render_profile_tab( $user, $lang ) {
        ?>
        <form class="vrcin-dashboard-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="vrcin_profile_update">
            <?php wp_nonce_field( 'vrcin_profile_update' ); ?>
            <h2><?php echo esc_html( 'it' === $lang ? 'Impostazioni account' : 'Account settings' ); ?></h2>
            <div class="vrcin-form-grid">
                <label><span>Display name</span><input name="display_name" required value="<?php echo esc_attr( $user->display_name ); ?>"></label>
                <label><span>Email</span><input type="email" name="user_email" required value="<?php echo esc_attr( $user->user_email ); ?>"></label>
                <label class="vrcin-form-field--wide"><span><?php echo esc_html( 'it' === $lang ? 'Nuova password - lascia vuoto per non cambiarla' : 'New password - leave empty to keep the current one' ); ?></span><input type="password" minlength="12" name="new_password"></label>
            </div>
            <button class="button button-primary" type="submit"><?php echo esc_html( 'it' === $lang ? 'Salva profilo' : 'Save profile' ); ?></button>
        </form>
        <?php
    }
}
