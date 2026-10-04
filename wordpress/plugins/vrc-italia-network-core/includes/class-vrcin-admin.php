<?php
defined( 'ABSPATH' ) || exit;

final class VRCIN_Admin {
    public static function hooks() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );

        add_action( 'admin_post_vrcin_admin_application_decide', array( __CLASS__, 'application_decide' ) );
        add_action( 'admin_post_vrcin_admin_event_decide', array( __CLASS__, 'event_decide' ) );
        add_action( 'admin_post_vrcin_admin_image_decide', array( __CLASS__, 'image_decide' ) );
        add_action( 'admin_post_vrcin_admin_form_save', array( __CLASS__, 'form_save' ) );
    }

    public static function menu() {
        add_menu_page(
            'VRC Italia Network',
            'VRC Italia Network',
            'manage_options',
            'vri',
            array( __CLASS__, 'dashboard_page' ),
            'dashicons-groups',
            58
        );
        add_submenu_page( 'vri', 'Community', 'Community', 'manage_options', 'vrcin-communities', array( __CLASS__, 'communities_page' ) );
        add_submenu_page( 'vri', 'Candidature', 'Candidature', 'manage_options', 'vrcin-applications', array( __CLASS__, 'applications_page' ) );
        add_submenu_page( 'vri', 'Eventi', 'Eventi', 'manage_options', 'vrcin-events', array( __CLASS__, 'events_page' ) );
        add_submenu_page( 'vri', 'Immagini', 'Immagini', 'manage_options', 'vrcin-images', array( __CLASS__, 'images_page' ) );
        add_submenu_page( 'vri', 'Modulo candidatura', 'Modulo candidatura', 'manage_options', 'vrcin-form', array( __CLASS__, 'form_page' ) );
        add_submenu_page( 'vri', 'Design Sync', 'Design Sync', 'manage_options', 'vrcin-design', array( 'VRCIN_Design_Sync', 'page' ) );
    }

    public static function assets( $hook ) {
        if ( false === strpos( $hook, 'vri' ) ) {
            return;
        }
        wp_enqueue_style( 'vrcin-admin', VRCIN_CORE_URL . 'assets/admin.css', array(), VRCIN_CORE_VERSION );
        wp_enqueue_script( 'vrcin-admin', VRCIN_CORE_URL . 'assets/admin.js', array(), VRCIN_CORE_VERSION, true );
    }

    private static function guard() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Not authorized.', 'vrc-italia-network' ), 403 );
        }
    }

    private static function admin_url( $page, $args = array() ) {
        return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
    }

    public static function dashboard_page() {
        self::guard();
        $applications = VRCIN_Model::applications_for_admin();
        $events = VRCIN_Model::pending_admin_events();
        $images = VRCIN_Model::images_for_admin();
        $communities = VRCIN_Model::get_communities();
        $eligible = 0;
        foreach ( $applications as $application ) {
            if ( 'eligible_admin' === $application['status'] ) {
                $eligible++;
            }
        }
        ?>
        <div class="wrap vrcin-admin">
            <h1>VRC Italia Network</h1>
            <div class="vrcin-admin-stats">
                <a href="<?php echo esc_url( self::admin_url( 'vrcin-communities' ) ); ?>"><strong><?php echo count( $communities ); ?></strong><span>Community approvate</span></a>
                <a href="<?php echo esc_url( self::admin_url( 'vrcin-applications' ) ); ?>"><strong><?php echo absint( $eligible ); ?></strong><span>Candidature pronte</span></a>
                <a href="<?php echo esc_url( self::admin_url( 'vrcin-events' ) ); ?>"><strong><?php echo count( $events ); ?></strong><span>Eventi da moderare</span></a>
                <a href="<?php echo esc_url( self::admin_url( 'vrcin-images' ) ); ?>"><strong><?php echo count( $images ); ?></strong><span>Immagini da moderare</span></a>
            </div>
            <div class="vrcin-admin-panel">
                <h2>Regole attive</h2>
                <ul>
                    <li>Nuove community: almeno il 75% di Sì dagli attuali proprietari, poi decisione finale admin.</li>
                    <li>Commento obbligatorio per ogni voto community: 10-250 caratteri, visibile solo agli amministratori.</li>
                    <li>Eventi: ogni creazione o modifica richiede approvazione admin.</li>
                    <li>Rotazione fascia: una community riusa liberamente la stessa fascia dopo N settimane, con N = community presenti al momento dell'evento normale.</li>
                    <li>Deroga fascia: 100% di Sì dagli altri proprietari; i non votanti dopo 48 ore diventano Sì automatici.</li>
                </ul>
            </div>
        </div>
        <?php
    }

    public static function communities_page() {
        self::guard();
        $communities = VRCIN_Model::get_communities();
        ?>
        <div class="wrap vrcin-admin">
            <h1>Community</h1>
            <table class="widefat striped">
                <thead><tr><th>Nome</th><th>Proprietario</th><th>Email</th><th>VRChat</th><th>Aggiornata</th></tr></thead>
                <tbody>
                <?php foreach ( $communities as $community ) :
                    $owner = get_user_by( 'id', $community['owner_user_id'] );
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html( $community['name'] ); ?></strong></td>
                        <td><?php echo esc_html( $owner ? $owner->display_name : '-' ); ?></td>
                        <td><?php echo esc_html( $owner ? $owner->user_email : '-' ); ?></td>
                        <td><?php if ( $community['vrchat_group_url'] ) : ?><a href="<?php echo esc_url( $community['vrchat_group_url'] ); ?>" target="_blank" rel="noopener">Apri</a><?php else : ?>-<?php endif; ?></td>
                        <td><?php echo esc_html( $community['updated_at'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function applications_page() {
        self::guard();
        global $wpdb;
        $applications = VRCIN_Model::applications_for_admin();
        $selected = absint( $_GET['application_id'] ?? 0 );
        ?>
        <div class="wrap vrcin-admin">
            <h1>Candidature</h1>
            <div class="vrcin-admin-split">
                <div class="vrcin-admin-panel">
                    <table class="widefat striped">
                        <thead><tr><th>Community</th><th>Email</th><th>Stato</th><th>Voti</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $applications as $application ) : ?>
                            <tr>
                                <td><?php echo esc_html( $application['community_name'] ); ?></td>
                                <td><?php echo esc_html( $application['email'] ); ?></td>
                                <td><code><?php echo esc_html( $application['status'] ); ?></code></td>
                                <td><?php echo absint( $application['yes_count'] ); ?> Sì / <?php echo absint( $application['no_count'] ); ?> No - soglia <?php echo absint( $application['threshold'] ); ?></td>
                                <td><a class="button" href="<?php echo esc_url( self::admin_url( 'vrcin-applications', array( 'application_id' => $application['id'] ) ) ); ?>">Dettagli</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ( $selected ) :
                    $application = null;
                    foreach ( $applications as $item ) {
                        if ( (int) $item['id'] === $selected ) { $application = $item; break; }
                    }
                    if ( $application ) :
                        $payload = json_decode( $application['payload'], true );
                        $votes = VRCIN_Model::application_votes( $selected );
                        ?>
                        <aside class="vrcin-admin-panel">
                            <h2><?php echo esc_html( $application['community_name'] ); ?></h2>
                            <dl class="vrcin-admin-details">
                                <?php foreach ( (array) $payload as $key => $value ) : ?>
                                    <div><dt><?php echo esc_html( str_replace( '_', ' ', $key ) ); ?></dt><dd><?php echo esc_html( is_array( $value ) ? implode( ', ', $value ) : $value ); ?></dd></div>
                                <?php endforeach; ?>
                            </dl>
                            <h3>Voti e commenti - solo admin</h3>
                            <?php if ( $votes ) : ?>
                                <?php foreach ( $votes as $vote ) : ?>
                                    <div class="vrcin-admin-vote"><strong><?php echo esc_html( $vote['display_name'] ?: $vote['user_email'] ); ?> - <?php echo esc_html( strtoupper( $vote['vote'] ) ); ?></strong><p><?php echo esc_html( $vote['comment'] ); ?></p></div>
                                <?php endforeach; ?>
                            <?php else : ?><p>Nessun voto.</p><?php endif; ?>

                            <?php if ( 'eligible_admin' === $application['status'] ) : ?>
                                <form class="vrcin-row-actions" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                    <input type="hidden" name="action" value="vrcin_admin_application_decide">
                                    <input type="hidden" name="application_id" value="<?php echo esc_attr( $selected ); ?>">
                                    <?php wp_nonce_field( 'vrcin_admin_application_decide' ); ?>
                                    <button class="button button-primary" name="decision" value="approve" type="submit">Approva e crea account</button>
                                    <button class="button" name="decision" value="reject" type="submit">Rifiuta</button>
                                </form>
                            <?php endif; ?>
                        </aside>
                    <?php endif;
                endif; ?>
            </div>
        </div>
        <?php
    }

    public static function events_page() {
        self::guard();
        $events = VRCIN_Model::pending_admin_events();
        $slots = VRCIN_Model::slot_requests_for_admin();
        ?>
        <div class="wrap vrcin-admin">
            <h1>Moderazione eventi</h1>
            <div class="vrcin-admin-panel">
                <h2>In approvazione</h2>
                <?php if ( ! $events ) : ?><p>Nessun evento da moderare.</p><?php endif; ?>
                <?php foreach ( $events as $event ) :
                    $payload = json_decode( $event['pending_payload'], true );
                    ?>
                    <article class="vrcin-admin-moderation">
                        <div>
                            <span><?php echo esc_html( $event['community_name'] ); ?></span>
                            <h3><?php echo esc_html( $payload['title_it'] ?: $payload['title_en'] ); ?></h3>
                            <p><?php echo esc_html( $payload['description_it'] ?: $payload['description_en'] ); ?></p>
                            <code><?php echo esc_html( $payload['start_at_utc'] . ' UTC -> ' . $payload['end_at_utc'] . ' UTC' ); ?></code>
                            <p>Modalità fascia: <strong><?php echo esc_html( $event['pending_slot_mode'] ); ?></strong></p>
                        </div>
                        <form class="vrcin-row-actions" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="vrcin_admin_event_decide">
                            <input type="hidden" name="event_id" value="<?php echo esc_attr( $event['id'] ); ?>">
                            <?php wp_nonce_field( 'vrcin_admin_event_decide' ); ?>
                            <button class="button button-primary" name="decision" value="approve" type="submit">Approva</button>
                            <button class="button" name="decision" value="reject" type="submit">Rifiuta</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="vrcin-admin-panel">
                <h2>Richieste deroga fascia</h2>
                <?php foreach ( $slots as $slot ) :
                    $votes = VRCIN_Model::slot_votes( $slot['id'] );
                    ?>
                    <details>
                        <summary><strong><?php echo esc_html( $slot['community_name'] ); ?></strong> - <?php echo esc_html( $slot['status'] ); ?> - scadenza <?php echo esc_html( $slot['expires_at'] ); ?> UTC</summary>
                        <?php foreach ( $votes as $vote ) : ?>
                            <p><?php echo esc_html( $vote['display_name'] ?: $vote['user_email'] ); ?>: <strong><?php echo esc_html( strtoupper( $vote['vote'] ) ); ?></strong><?php echo $vote['is_auto'] ? ' (automatico)' : ''; ?></p>
                        <?php endforeach; ?>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public static function images_page() {
        self::guard();
        $images = VRCIN_Model::images_for_admin();
        ?>
        <div class="wrap vrcin-admin">
            <h1>Moderazione immagini</h1>
            <div class="vrcin-admin-gallery">
                <?php foreach ( $images as $image ) : ?>
                    <article>
                        <?php echo wp_get_attachment_image( $image['attachment_id'], 'medium' ); ?>
                        <strong><?php echo esc_html( $image['community_name'] ); ?></strong>
                        <form class="vrcin-row-actions" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="vrcin_admin_image_decide">
                            <input type="hidden" name="image_id" value="<?php echo esc_attr( $image['id'] ); ?>">
                            <?php wp_nonce_field( 'vrcin_admin_image_decide' ); ?>
                            <button class="button button-primary" name="decision" value="approve" type="submit">Approva</button>
                            <button class="button" name="decision" value="reject" type="submit">Rifiuta</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public static function form_page() {
        self::guard();
        $fields = get_option( 'vrcin_form_fields', array() );
        $settings = get_option( 'vrcin_form_settings', array() );
        ?>
        <div class="wrap vrcin-admin">
            <h1>Modulo candidatura</h1>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="vrcin_admin_form_save">
                <?php wp_nonce_field( 'vrcin_admin_form_save' ); ?>

                <div class="vrcin-admin-panel">
                    <h2>Contenuti e design popup</h2>
                    <div class="vrcin-admin-form-grid">
                        <label>Titolo IT<input name="settings[title_it]" value="<?php echo esc_attr( $settings['title_it'] ?? '' ); ?>"></label>
                        <label>Title EN<input name="settings[title_en]" value="<?php echo esc_attr( $settings['title_en'] ?? '' ); ?>"></label>
                        <label>Intro IT<textarea name="settings[intro_it]"><?php echo esc_textarea( $settings['intro_it'] ?? '' ); ?></textarea></label>
                        <label>Intro EN<textarea name="settings[intro_en]"><?php echo esc_textarea( $settings['intro_en'] ?? '' ); ?></textarea></label>
                        <label>Pulsante IT<input name="settings[submit_it]" value="<?php echo esc_attr( $settings['submit_it'] ?? '' ); ?>"></label>
                        <label>Button EN<input name="settings[submit_en]" value="<?php echo esc_attr( $settings['submit_en'] ?? '' ); ?>"></label>
                        <label>Esito Sì IT<textarea name="settings[yes_it]"><?php echo esc_textarea( $settings['yes_it'] ?? '' ); ?></textarea></label>
                        <label>Yes result EN<textarea name="settings[yes_en]"><?php echo esc_textarea( $settings['yes_en'] ?? '' ); ?></textarea></label>
                        <label>Esito No IT<textarea name="settings[no_it]"><?php echo esc_textarea( $settings['no_it'] ?? '' ); ?></textarea></label>
                        <label>No result EN<textarea name="settings[no_en]"><?php echo esc_textarea( $settings['no_en'] ?? '' ); ?></textarea></label>
                        <label>Accent<input type="color" name="settings[accent]" value="<?php echo esc_attr( $settings['accent'] ?? '#138a4b' ); ?>"></label>
                        <label>Dark background<input type="color" name="settings[background_dark]" value="<?php echo esc_attr( $settings['background_dark'] ?? '#0d110e' ); ?>"></label>
                        <label>Dark text<input type="color" name="settings[text_dark]" value="<?php echo esc_attr( $settings['text_dark'] ?? '#f4f1e8' ); ?>"></label>
                        <label>Light background<input type="color" name="settings[background_light]" value="<?php echo esc_attr( $settings['background_light'] ?? '#f4f1e8' ); ?>"></label>
                        <label>Light text<input type="color" name="settings[text_light]" value="<?php echo esc_attr( $settings['text_light'] ?? '#172019' ); ?>"></label>
                        <label>Radius px<input type="number" min="0" max="60" name="settings[radius]" value="<?php echo esc_attr( $settings['radius'] ?? 20 ); ?>"></label>
                        <label>Max width px<input type="number" min="420" max="1400" name="settings[max_width]" value="<?php echo esc_attr( $settings['max_width'] ?? 760 ); ?>"></label>
                        <label class="vrcin-admin-wide">Custom CSS<textarea name="settings[custom_css]" rows="8"><?php echo esc_textarea( $settings['custom_css'] ?? '' ); ?></textarea></label>
                    </div>
                </div>

                <div class="vrcin-admin-panel">
                    <div class="vrcin-admin-panel-head"><h2>Campi</h2><button type="button" class="button" id="vrcin-add-field">Aggiungi campo</button></div>
                    <div id="vrcin-form-fields">
                        <?php foreach ( $fields as $i => $field ) : self::field_row( $i, $field ); endforeach; ?>
                    </div>
                </div>

                <p><button class="button button-primary button-large" type="submit">Salva modulo</button></p>
            </form>
        </div>
        <?php
    }

    private static function field_row( $i, $field ) {
        $locked = ! empty( $field['locked'] );
        ?>
        <div class="vrcin-form-field-row">
            <span class="vrcin-drag">↕</span>
            <input name="field_key[]" placeholder="key" value="<?php echo esc_attr( $field['key'] ?? '' ); ?>" <?php disabled( $locked ); ?>>
            <?php if ( $locked ) : ?><input type="hidden" name="field_key[]" value="<?php echo esc_attr( $field['key'] ?? '' ); ?>"><?php endif; ?>
            <select name="field_type[]">
                <?php foreach ( array( 'text', 'textarea', 'email', 'url', 'number', 'select', 'checkbox' ) as $type ) : ?>
                    <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $field['type'] ?? 'text', $type ); ?>><?php echo esc_html( $type ); ?></option>
                <?php endforeach; ?>
            </select>
            <input name="field_label_it[]" placeholder="Label IT" value="<?php echo esc_attr( $field['label_it'] ?? '' ); ?>">
            <input name="field_label_en[]" placeholder="Label EN" value="<?php echo esc_attr( $field['label_en'] ?? '' ); ?>">
            <input name="field_options[]" placeholder="Opzioni separate da |" value="<?php echo esc_attr( implode( '|', (array) ( $field['options'] ?? array() ) ) ); ?>">
            <label><input type="checkbox" name="field_required[<?php echo absint( $i ); ?>]" value="1" <?php checked( ! empty( $field['required'] ) ); ?> <?php disabled( $locked ); ?>> required</label>
            <input type="hidden" name="field_locked[]" value="<?php echo $locked ? '1' : '0'; ?>">
            <?php if ( ! $locked ) : ?><button type="button" class="button-link-delete vrcin-remove-field">Rimuovi</button><?php endif; ?>
        </div>
        <?php
    }

    public static function application_decide() {
        self::guard();
        check_admin_referer( 'vrcin_admin_application_decide' );
        $result = VRCIN_Model::admin_decide_application(
            absint( $_POST['application_id'] ?? 0 ),
            'approve' === ( $_POST['decision'] ?? '' ),
            get_current_user_id()
        );
        wp_safe_redirect( self::admin_url( 'vrcin-applications', array( 'vrcin_status' => is_wp_error( $result ) ? $result->get_error_code() : 'saved' ) ) );
        exit;
    }

    public static function event_decide() {
        self::guard();
        check_admin_referer( 'vrcin_admin_event_decide' );
        $result = VRCIN_Model::admin_decide_event(
            absint( $_POST['event_id'] ?? 0 ),
            'approve' === ( $_POST['decision'] ?? '' ),
            get_current_user_id()
        );
        wp_safe_redirect( self::admin_url( 'vrcin-events', array( 'vrcin_status' => is_wp_error( $result ) ? $result->get_error_code() : 'saved' ) ) );
        exit;
    }

    public static function image_decide() {
        self::guard();
        check_admin_referer( 'vrcin_admin_image_decide' );
        VRCIN_Model::moderate_gallery_image(
            absint( $_POST['image_id'] ?? 0 ),
            'approve' === ( $_POST['decision'] ?? '' ),
            get_current_user_id()
        );
        wp_safe_redirect( self::admin_url( 'vrcin-images', array( 'vrcin_status' => 'saved' ) ) );
        exit;
    }

    public static function form_save() {
        self::guard();
        check_admin_referer( 'vrcin_admin_form_save' );

        $keys = isset( $_POST['field_key'] ) ? (array) wp_unslash( $_POST['field_key'] ) : array();
        $types = isset( $_POST['field_type'] ) ? (array) wp_unslash( $_POST['field_type'] ) : array();
        $labels_it = isset( $_POST['field_label_it'] ) ? (array) wp_unslash( $_POST['field_label_it'] ) : array();
        $labels_en = isset( $_POST['field_label_en'] ) ? (array) wp_unslash( $_POST['field_label_en'] ) : array();
        $options = isset( $_POST['field_options'] ) ? (array) wp_unslash( $_POST['field_options'] ) : array();
        $locked = isset( $_POST['field_locked'] ) ? (array) wp_unslash( $_POST['field_locked'] ) : array();
        $required = isset( $_POST['field_required'] ) ? (array) $_POST['field_required'] : array();

        $fields = array();
        $allowed = array( 'text', 'textarea', 'email', 'url', 'number', 'select', 'checkbox' );
        $row_count = min( count( $types ), count( $labels_it ), count( $labels_en ) );

        for ( $i = 0; $i < $row_count; $i++ ) {
            $key = sanitize_key( $keys[ $i ] ?? '' );
            if ( ! $key ) {
                continue;
            }
            $is_locked = ! empty( $locked[ $i ] );
            $locked_keys = array( 'community_name', 'owner_email', 'italian_members_percent', 'italian_events_percent', 'lobbies_last_7_days' );
            if ( $is_locked && ! in_array( $key, $locked_keys, true ) ) {
                $is_locked = false;
            }
            $type = sanitize_key( $types[ $i ] ?? 'text' );
            if ( ! in_array( $type, $allowed, true ) ) {
                $type = 'text';
            }
            $fields[] = array(
                'key' => $key,
                'type' => $type,
                'label_it' => sanitize_text_field( $labels_it[ $i ] ?? $key ),
                'label_en' => sanitize_text_field( $labels_en[ $i ] ?? $key ),
                'required' => $is_locked ? true : ! empty( $required[ $i ] ),
                'locked' => $is_locked,
                'options' => array_values( array_filter( array_map( 'sanitize_text_field', explode( '|', $options[ $i ] ?? '' ) ) ) ),
            );
        }

        foreach ( array(
            array( 'key' => 'community_name', 'type' => 'text', 'label_it' => 'Nome community', 'label_en' => 'Community name' ),
            array( 'key' => 'owner_email', 'type' => 'email', 'label_it' => 'Email del proprietario', 'label_en' => 'Owner email' ),
            array( 'key' => 'italian_members_percent', 'type' => 'number', 'label_it' => '% utenti italiani', 'label_en' => '% Italian members' ),
            array( 'key' => 'italian_events_percent', 'type' => 'number', 'label_it' => '% eventi in italiano', 'label_en' => '% events held in Italian' ),
            array( 'key' => 'lobbies_last_7_days', 'type' => 'number', 'label_it' => 'Lobby aperte negli ultimi 7 giorni', 'label_en' => 'Lobbies opened in the last 7 days' ),
        ) as $system ) {
            $found = false;
            foreach ( $fields as &$field ) {
                if ( $field['key'] === $system['key'] ) {
                    $field['locked'] = true;
                    $field['required'] = true;
                    $field['type'] = $system['type'];
                    $found = true;
                    break;
                }
            }
            unset( $field );
            if ( ! $found ) {
                array_unshift( $fields, array_merge( $system, array( 'required' => true, 'locked' => true, 'options' => array() ) ) );
            }
        }

        $settings_in = isset( $_POST['settings'] ) ? (array) wp_unslash( $_POST['settings'] ) : array();
        $settings = array(
            'title_it' => sanitize_text_field( $settings_in['title_it'] ?? '' ),
            'title_en' => sanitize_text_field( $settings_in['title_en'] ?? '' ),
            'intro_it' => sanitize_textarea_field( $settings_in['intro_it'] ?? '' ),
            'intro_en' => sanitize_textarea_field( $settings_in['intro_en'] ?? '' ),
            'submit_it' => sanitize_text_field( $settings_in['submit_it'] ?? '' ),
            'submit_en' => sanitize_text_field( $settings_in['submit_en'] ?? '' ),
            'yes_it' => sanitize_textarea_field( $settings_in['yes_it'] ?? '' ),
            'yes_en' => sanitize_textarea_field( $settings_in['yes_en'] ?? '' ),
            'no_it' => sanitize_textarea_field( $settings_in['no_it'] ?? '' ),
            'no_en' => sanitize_textarea_field( $settings_in['no_en'] ?? '' ),
            'accent' => sanitize_hex_color( $settings_in['accent'] ?? '#138a4b' ) ?: '#138a4b',
            'background_dark' => sanitize_hex_color( $settings_in['background_dark'] ?? '#0d110e' ) ?: '#0d110e',
            'text_dark' => sanitize_hex_color( $settings_in['text_dark'] ?? '#f4f1e8' ) ?: '#f4f1e8',
            'background_light' => sanitize_hex_color( $settings_in['background_light'] ?? '#f4f1e8' ) ?: '#f4f1e8',
            'text_light' => sanitize_hex_color( $settings_in['text_light'] ?? '#172019' ) ?: '#172019',
            'radius' => min( 60, absint( $settings_in['radius'] ?? 20 ) ),
            'max_width' => min( 1400, max( 420, absint( $settings_in['max_width'] ?? 760 ) ) ),
            'custom_css' => wp_strip_all_tags( $settings_in['custom_css'] ?? '' ),
        );

        update_option( 'vrcin_form_fields', $fields, false );
        update_option( 'vrcin_form_settings', $settings, false );

        wp_safe_redirect( self::admin_url( 'vrcin-form', array( 'vrcin_status' => 'saved' ) ) );
        exit;
    }
}
