<?php
defined( 'ABSPATH' ) || exit;

final class VRI_Install {
    public static function activate() {
        self::schema();
        self::roles();
        self::defaults();
        VRI_Frontend::rewrite_rules();
        flush_rewrite_rules();

        if ( ! wp_next_scheduled( 'vri_process_slot_timeouts' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'vri_process_slot_timeouts' );
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'vri_process_slot_timeouts' );
        flush_rewrite_rules();
    }

    private static function schema() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;

        $sql = array();

        $sql[] = "CREATE TABLE {$p}vri_communities (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            owner_user_id bigint(20) unsigned DEFAULT NULL,
            name varchar(191) NOT NULL,
            slug varchar(191) NOT NULL,
            discord_url varchar(500) NOT NULL DEFAULT '',
            vrchat_group_url varchar(500) NOT NULL DEFAULT '',
            vrchat_world_url varchar(500) NOT NULL DEFAULT '',
            instagram_url varchar(500) NOT NULL DEFAULT '',
            website_url varchar(500) NOT NULL DEFAULT '',
            description_it varchar(250) NOT NULL DEFAULT '',
            description_en varchar(250) NOT NULL DEFAULT '',
            banner_attachment_id bigint(20) unsigned DEFAULT NULL,
            logo_attachment_id bigint(20) unsigned DEFAULT NULL,
            status varchar(30) NOT NULL DEFAULT 'approved',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY owner_user_id (owner_user_id),
            UNIQUE KEY slug (slug),
            KEY status (status)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vri_community_images (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            community_id bigint(20) unsigned NOT NULL,
            attachment_id bigint(20) unsigned NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'pending',
            submitted_by bigint(20) unsigned NOT NULL,
            reviewed_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            reviewed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY community_attachment (community_id,attachment_id),
            KEY community_status (community_id,status)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vri_applications (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(190) NOT NULL,
            community_name varchar(191) NOT NULL,
            payload longtext NOT NULL,
            status varchar(40) NOT NULL DEFAULT 'pending_email',
            verification_token_hash char(64) NOT NULL DEFAULT '',
            verification_expires_at datetime DEFAULT NULL,
            email_verified_at datetime DEFAULT NULL,
            eligible_voters longtext DEFAULT NULL,
            threshold smallint unsigned NOT NULL DEFAULT 0,
            yes_count smallint unsigned NOT NULL DEFAULT 0,
            no_count smallint unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            vote_started_at datetime DEFAULT NULL,
            decided_at datetime DEFAULT NULL,
            decided_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY email (email),
            KEY status (status)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vri_application_votes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            application_id bigint(20) unsigned NOT NULL,
            voter_user_id bigint(20) unsigned NOT NULL,
            vote varchar(3) NOT NULL,
            comment varchar(250) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY application_voter (application_id,voter_user_id),
            KEY application_id (application_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vri_events (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            community_id bigint(20) unsigned NOT NULL,
            title_it varchar(191) NOT NULL DEFAULT '',
            title_en varchar(191) NOT NULL DEFAULT '',
            description_it text NOT NULL,
            description_en text NOT NULL,
            start_at_utc datetime DEFAULT NULL,
            end_at_utc datetime DEFAULT NULL,
            world_name varchar(191) NOT NULL DEFAULT '',
            access_type varchar(30) NOT NULL DEFAULT 'group',
            platforms varchar(255) NOT NULL DEFAULT '',
            capacity int unsigned DEFAULT NULL,
            registration_url varchar(500) NOT NULL DEFAULT '',
            event_url varchar(500) NOT NULL DEFAULT '',
            tags varchar(500) NOT NULL DEFAULT '',
            slot_mode varchar(20) NOT NULL DEFAULT 'normal',
            rotation_size smallint unsigned NOT NULL DEFAULT 1,
            status varchar(30) NOT NULL DEFAULT 'pending',
            pending_status varchar(30) DEFAULT NULL,
            pending_payload longtext DEFAULT NULL,
            pending_slot_mode varchar(20) DEFAULT NULL,
            pending_rotation_size smallint unsigned DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL,
            approved_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            approved_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY community_status (community_id,status),
            KEY start_at_utc (start_at_utc),
            KEY pending_status (pending_status)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vri_slot_requests (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            community_id bigint(20) unsigned NOT NULL,
            eligible_voters longtext NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'voting',
            expires_at datetime NOT NULL,
            created_at datetime NOT NULL,
            resolved_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY event_id (event_id),
            KEY status_expires (status,expires_at)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vri_slot_votes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            request_id bigint(20) unsigned NOT NULL,
            voter_user_id bigint(20) unsigned NOT NULL,
            vote varchar(3) NOT NULL,
            is_auto tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY request_voter (request_id,voter_user_id),
            KEY request_id (request_id)
        ) {$charset};";

        foreach ( $sql as $statement ) {
            dbDelta( $statement );
        }

        update_option( 'vri_db_version', VRI_CORE_VERSION, false );
    }

    private static function roles() {
        add_role(
            'vri_community_owner',
            'VRC Italia Network - Community Owner',
            array(
                'read' => true,
                'vri_manage_community' => true,
                'vri_manage_events' => true,
                'vri_vote_applications' => true,
                'vri_vote_slots' => true,
            )
        );

        $owner = get_role( 'vri_community_owner' );
        if ( $owner ) {
            $owner->remove_cap( 'upload_files' );
            foreach ( array(
                'read',
                'vri_manage_community',
                'vri_manage_events',
                'vri_vote_applications',
                'vri_vote_slots',
            ) as $cap ) {
                $owner->add_cap( $cap );
            }
        }

        $admin = get_role( 'administrator' );
        if ( $admin ) {
            foreach ( array(
                'vri_manage_community',
                'vri_manage_events',
                'vri_vote_applications',
                'vri_vote_slots',
                'vri_moderate',
                'vri_manage_form',
                'vri_sync_design',
            ) as $cap ) {
                $admin->add_cap( $cap );
            }
        }
    }

    private static function defaults() {
        if ( false === get_option( 'vri_form_fields', false ) ) {
            update_option(
                'vri_form_fields',
                array(
                    array( 'key' => 'community_name', 'type' => 'text', 'label_it' => 'Nome community', 'label_en' => 'Community name', 'required' => true, 'locked' => true ),
                    array( 'key' => 'owner_email', 'type' => 'email', 'label_it' => 'Email del proprietario', 'label_en' => 'Owner email', 'required' => true, 'locked' => true ),
                    array( 'key' => 'discord_url', 'type' => 'url', 'label_it' => 'Discord', 'label_en' => 'Discord', 'required' => false ),
                    array( 'key' => 'vrchat_group_url', 'type' => 'url', 'label_it' => 'Gruppo VRChat', 'label_en' => 'VRChat group', 'required' => false ),
                    array( 'key' => 'vrchat_world_url', 'type' => 'url', 'label_it' => 'Mappa / World VRChat', 'label_en' => 'VRChat world', 'required' => false ),
                    array( 'key' => 'instagram_url', 'type' => 'url', 'label_it' => 'Instagram', 'label_en' => 'Instagram', 'required' => false ),
                    array( 'key' => 'website_url', 'type' => 'url', 'label_it' => 'Sito web', 'label_en' => 'Website', 'required' => false ),
                    array( 'key' => 'italian_members_percent', 'type' => 'number', 'label_it' => '% utenti italiani', 'label_en' => '% Italian members', 'required' => true, 'locked' => true ),
                    array( 'key' => 'italian_events_percent', 'type' => 'number', 'label_it' => '% eventi in italiano', 'label_en' => '% events held in Italian', 'required' => true, 'locked' => true ),
                    array( 'key' => 'lobbies_last_7_days', 'type' => 'number', 'label_it' => 'Lobby aperte negli ultimi 7 giorni', 'label_en' => 'Lobbies opened in the last 7 days', 'required' => true, 'locked' => true ),
                    array( 'key' => 'notes', 'type' => 'textarea', 'label_it' => 'Presentazione', 'label_en' => 'Introduction', 'required' => false ),
                ),
                false
            );
        }

        if ( false === get_option( 'vri_form_settings', false ) ) {
            update_option(
                'vri_form_settings',
                array(
                    'title_it' => 'Candidatura community',
                    'title_en' => 'Community application',
                    'intro_it' => 'Compila il modulo per candidare la tua community a VRC Italia Network.',
                    'intro_en' => 'Complete the form to apply your community to VRC Italia Network.',
                    'submit_it' => 'Invia candidatura',
                    'submit_en' => 'Submit application',
                    'yes_it' => 'La tua candidatura è stata accettata.',
                    'yes_en' => 'Your application has been accepted.',
                    'no_it' => 'La tua candidatura non è stata accettata.',
                    'no_en' => 'Your application has not been accepted.',
                    'accent' => '#138a4b',
                    'background_dark' => '#0d110e',
                    'text_dark' => '#f4f1e8',
                    'background_light' => '#f4f1e8',
                    'text_light' => '#172019',
                    'radius' => 20,
                    'max_width' => 760,
                    'custom_css' => '',
                ),
                false
            );
        }

        if ( false === get_option( 'vri_design_source', false ) ) {
            update_option(
                'vri_design_source',
                array(
                    'repository' => 'haxurus/www.vrchatitalia.it',
                    'branch' => 'main',
                    'prefix' => 'wordpress/design',
                    'active_commit' => '',
                    'active_files' => array(),
                ),
                false
            );
        }
    }
}
