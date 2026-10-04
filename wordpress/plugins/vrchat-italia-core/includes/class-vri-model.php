<?php
defined( 'ABSPATH' ) || exit;

final class VRI_Model {
    const TZ = 'Europe/Rome';
    const APPLICATION_VERIFY_HOURS = 48;
    const SLOT_VOTE_HOURS = 48;

    public static function hooks() {
        add_action( 'vri_process_slot_timeouts', array( __CLASS__, 'process_slot_timeouts' ) );
    }

    private static function table( $name ) {
        global $wpdb;
        return $wpdb->prefix . 'vri_' . $name;
    }

    private static function now() {
        return current_time( 'mysql', true );
    }

    private static function json( $value ) {
        return wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    }

    private static function decode( $value, $fallback = array() ) {
        $decoded = json_decode( (string) $value, true );
        return is_array( $decoded ) ? $decoded : $fallback;
    }

    public static function get_communities() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM " . self::table( 'communities' ) . " WHERE status='approved' ORDER BY name ASC",
            ARRAY_A
        );
    }

    public static function get_community( $id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table( 'communities' ) . " WHERE id=%d", absint( $id ) ),
            ARRAY_A
        );
    }

    public static function get_owner_community( $user_id = 0 ) {
        global $wpdb;
        $user_id = $user_id ? absint( $user_id ) : get_current_user_id();
        if ( ! $user_id ) {
            return null;
        }
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::table( 'communities' ) . " WHERE owner_user_id=%d AND status='approved' LIMIT 1",
                $user_id
            ),
            ARRAY_A
        );
    }

    public static function approved_community_count() {
        global $wpdb;
        return max( 1, (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::table( 'communities' ) . " WHERE status='approved'" ) );
    }

    public static function owner_ids( $exclude_user_id = 0 ) {
        global $wpdb;
        $ids = $wpdb->get_col( "SELECT owner_user_id FROM " . self::table( 'communities' ) . " WHERE status='approved' AND owner_user_id IS NOT NULL ORDER BY id ASC" );
        $ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
        if ( $exclude_user_id ) {
            $ids = array_values( array_diff( $ids, array( absint( $exclude_user_id ) ) ) );
        }
        return $ids;
    }

    public static function get_home_banners() {
        $items = array();
        foreach ( self::get_communities() as $community ) {
            $id = absint( $community['banner_attachment_id'] );
            if ( ! $id ) {
                continue;
            }
            $url = wp_get_attachment_image_url( $id, 'full' );
            if ( $url ) {
                $items[] = array(
                    'url' => $url,
                    'name' => $community['name'],
                    'community_id' => absint( $community['id'] ),
                );
            }
        }
        if ( count( $items ) > 1 ) {
            shuffle( $items );
        }
        return $items;
    }

    public static function get_home_gallery() {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT i.*, c.name AS community_name
             FROM " . self::table( 'community_images' ) . " i
             INNER JOIN " . self::table( 'communities' ) . " c ON c.id=i.community_id
             WHERE i.status='approved' AND c.status='approved'
             ORDER BY RAND()
             LIMIT 60",
            ARRAY_A
        );
        $out = array();
        foreach ( $rows as $row ) {
            $url = wp_get_attachment_image_url( absint( $row['attachment_id'] ), 'large' );
            if ( $url ) {
                $out[] = array(
                    'url' => $url,
                    'community' => $row['community_name'],
                    'attachment_id' => absint( $row['attachment_id'] ),
                );
            }
        }
        return $out;
    }

    public static function update_community( $community_id, $user_id, $data ) {
        global $wpdb;
        $community = self::get_community( $community_id );
        if ( ! $community || (int) $community['owner_user_id'] !== (int) $user_id ) {
            return new WP_Error( 'vri_forbidden', 'Community not available.' );
        }

        $name = sanitize_text_field( $data['name'] ?? '' );
        if ( '' === $name ) {
            return new WP_Error( 'vri_name', 'Community name is required.' );
        }

        $description_it = mb_substr( sanitize_textarea_field( $data['description_it'] ?? '' ), 0, 250 );
        $description_en = mb_substr( sanitize_textarea_field( $data['description_en'] ?? '' ), 0, 250 );

        $update = array(
            'name' => $name,
            'discord_url' => esc_url_raw( $data['discord_url'] ?? '' ),
            'vrchat_group_url' => esc_url_raw( $data['vrchat_group_url'] ?? '' ),
            'vrchat_world_url' => esc_url_raw( $data['vrchat_world_url'] ?? '' ),
            'instagram_url' => esc_url_raw( $data['instagram_url'] ?? '' ),
            'website_url' => esc_url_raw( $data['website_url'] ?? '' ),
            'description_it' => $description_it,
            'description_en' => $description_en,
            'updated_at' => self::now(),
        );

        if ( isset( $data['banner_attachment_id'] ) ) {
            $update['banner_attachment_id'] = absint( $data['banner_attachment_id'] );
        }
        if ( isset( $data['logo_attachment_id'] ) ) {
            $update['logo_attachment_id'] = absint( $data['logo_attachment_id'] );
        }

        $ok = $wpdb->update( self::table( 'communities' ), $update, array( 'id' => absint( $community_id ) ) );
        return false === $ok ? new WP_Error( 'vri_db', 'Could not update the community.' ) : true;
    }

    public static function add_gallery_image( $community_id, $user_id, $attachment_id ) {
        global $wpdb;
        $community = self::get_community( $community_id );
        if ( ! $community || (int) $community['owner_user_id'] !== (int) $user_id ) {
            return new WP_Error( 'vri_forbidden', 'Community not available.' );
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::table( 'community_images' ) . " WHERE community_id=%d AND status IN ('pending','approved')",
                $community_id
            )
        );
        if ( $count >= 10 ) {
            return new WP_Error( 'vri_gallery_limit', 'Maximum 10 images per community.' );
        }

        $ok = $wpdb->insert(
            self::table( 'community_images' ),
            array(
                'community_id' => absint( $community_id ),
                'attachment_id' => absint( $attachment_id ),
                'status' => 'pending',
                'submitted_by' => absint( $user_id ),
                'created_at' => self::now(),
            )
        );
        return $ok ? true : new WP_Error( 'vri_db', 'Could not save the image.' );
    }

    public static function delete_gallery_image( $image_id, $user_id ) {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT i.*, c.owner_user_id FROM " . self::table( 'community_images' ) . " i
                 INNER JOIN " . self::table( 'communities' ) . " c ON c.id=i.community_id
                 WHERE i.id=%d",
                absint( $image_id )
            ),
            ARRAY_A
        );
        if ( ! $row || (int) $row['owner_user_id'] !== (int) $user_id ) {
            return new WP_Error( 'vri_forbidden', 'Image not available.' );
        }
        $wpdb->delete( self::table( 'community_images' ), array( 'id' => absint( $image_id ) ) );
        return true;
    }

    public static function gallery_images( $community_id ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table( 'community_images' ) . " WHERE community_id=%d AND status IN ('pending','approved') ORDER BY created_at DESC",
                absint( $community_id )
            ),
            ARRAY_A
        );
    }

    public static function moderate_gallery_image( $image_id, $approve, $admin_id ) {
        global $wpdb;
        return false !== $wpdb->update(
            self::table( 'community_images' ),
            array(
                'status' => $approve ? 'approved' : 'rejected',
                'reviewed_by' => absint( $admin_id ),
                'reviewed_at' => self::now(),
            ),
            array( 'id' => absint( $image_id ) )
        );
    }

    public static function create_application( $payload, $lang = 'en' ) {
        global $wpdb;

        $email = sanitize_email( $payload['owner_email'] ?? '' );
        $community_name = sanitize_text_field( $payload['community_name'] ?? '' );

        if ( ! is_email( $email ) ) {
            return new WP_Error( 'vri_email', 'Enter a valid email address.' );
        }
        if ( '' === $community_name ) {
            return new WP_Error( 'vri_name', 'Community name is required.' );
        }

        $members = isset( $payload['italian_members_percent'] ) ? (float) $payload['italian_members_percent'] : 0;
        $events = isset( $payload['italian_events_percent'] ) ? (float) $payload['italian_events_percent'] : 0;
        $lobbies = isset( $payload['lobbies_last_7_days'] ) ? (int) $payload['lobbies_last_7_days'] : 0;
        if ( $members < 80 || $events < 80 || $lobbies < 5 ) {
            return new WP_Error( 'vri_requirements', 'The minimum project requirements are not met.' );
        }

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM " . self::table( 'applications' ) . " WHERE (email=%s OR community_name=%s) AND status NOT IN ('rejected','approved') LIMIT 1",
                $email,
                $community_name
            )
        );
        if ( $exists ) {
            return new WP_Error( 'vri_duplicate', 'An application for this email or community is already active.' );
        }

        $token = wp_generate_password( 48, false, false );
        $hash = hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
        $expires = gmdate( 'Y-m-d H:i:s', time() + self::APPLICATION_VERIFY_HOURS * HOUR_IN_SECONDS );

        $ok = $wpdb->insert(
            self::table( 'applications' ),
            array(
                'email' => $email,
                'community_name' => $community_name,
                'payload' => self::json( $payload ),
                'status' => 'pending_email',
                'verification_token_hash' => $hash,
                'verification_expires_at' => $expires,
                'created_at' => self::now(),
            )
        );
        if ( ! $ok ) {
            return new WP_Error( 'vri_db', 'Could not save the application.' );
        }

        $id = (int) $wpdb->insert_id;
        $url = add_query_arg(
            array(
                'vri_verify' => rawurlencode( $token ),
                'vri_application' => $id,
                'lang' => 'it' === $lang ? 'it' : 'en',
            ),
            home_url( '/' )
        );

        $subject = 'it' === $lang ? 'Verifica candidatura VRChat Italia' : 'Verify your VRChat Italia application';
        $body = 'it' === $lang
            ? "Conferma il tuo indirizzo email per avviare la candidatura:\n\n" . $url
            : "Confirm your email address to start the application process:\n\n" . $url;

        wp_mail( $email, $subject, $body );
        return $id;
    }

    public static function verify_application( $application_id, $token ) {
        global $wpdb;
        $application = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table( 'applications' ) . " WHERE id=%d", absint( $application_id ) ),
            ARRAY_A
        );
        if ( ! $application || 'pending_email' !== $application['status'] ) {
            return new WP_Error( 'vri_application', 'Application is not available for verification.' );
        }
        if ( empty( $application['verification_expires_at'] ) || strtotime( $application['verification_expires_at'] . ' UTC' ) < time() ) {
            return new WP_Error( 'vri_expired', 'Verification link expired.' );
        }
        $hash = hash_hmac( 'sha256', (string) $token, wp_salt( 'auth' ) );
        if ( ! hash_equals( $application['verification_token_hash'], $hash ) ) {
            return new WP_Error( 'vri_token', 'Invalid verification link.' );
        }

        $voters = self::owner_ids();
        $threshold = count( $voters ) ? (int) ceil( count( $voters ) * 0.75 ) : 0;
        $status = count( $voters ) ? 'voting' : 'eligible_admin';

        $wpdb->update(
            self::table( 'applications' ),
            array(
                'status' => $status,
                'verification_token_hash' => '',
                'verification_expires_at' => null,
                'email_verified_at' => self::now(),
                'eligible_voters' => self::json( $voters ),
                'threshold' => $threshold,
                'vote_started_at' => self::now(),
            ),
            array( 'id' => absint( $application_id ) )
        );

        return true;
    }

    public static function pending_applications_for_owner( $user_id ) {
        global $wpdb;
        $applications = $wpdb->get_results(
            "SELECT * FROM " . self::table( 'applications' ) . " WHERE status='voting' ORDER BY created_at ASC",
            ARRAY_A
        );
        $out = array();
        foreach ( $applications as $application ) {
            $eligible = self::decode( $application['eligible_voters'] );
            if ( ! in_array( absint( $user_id ), array_map( 'absint', $eligible ), true ) ) {
                continue;
            }
            $voted = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM " . self::table( 'application_votes' ) . " WHERE application_id=%d AND voter_user_id=%d",
                    $application['id'],
                    $user_id
                )
            );
            if ( ! $voted ) {
                $application['payload_array'] = self::decode( $application['payload'] );
                $out[] = $application;
            }
        }
        return $out;
    }

    public static function cast_application_vote( $application_id, $user_id, $vote, $comment ) {
        global $wpdb;
        $vote = 'yes' === $vote ? 'yes' : ( 'no' === $vote ? 'no' : '' );
        $comment = trim( sanitize_textarea_field( $comment ) );
        $length = function_exists( 'mb_strlen' ) ? mb_strlen( $comment ) : strlen( $comment );

        if ( ! $vote || $length < 10 || $length > 250 ) {
            return new WP_Error( 'vri_vote', 'Vote and comment are required. Comment length: 10-250 characters.' );
        }

        $application = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table( 'applications' ) . " WHERE id=%d", absint( $application_id ) ),
            ARRAY_A
        );
        if ( ! $application || 'voting' !== $application['status'] ) {
            return new WP_Error( 'vri_application', 'Application is not open for voting.' );
        }

        $eligible = array_map( 'absint', self::decode( $application['eligible_voters'] ) );
        if ( ! in_array( absint( $user_id ), $eligible, true ) ) {
            return new WP_Error( 'vri_forbidden', 'You cannot vote on this application.' );
        }

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM " . self::table( 'application_votes' ) . " WHERE application_id=%d AND voter_user_id=%d",
                $application_id,
                $user_id
            )
        );
        if ( $exists ) {
            return new WP_Error( 'vri_voted', 'You have already voted.' );
        }

        $wpdb->insert(
            self::table( 'application_votes' ),
            array(
                'application_id' => absint( $application_id ),
                'voter_user_id' => absint( $user_id ),
                'vote' => $vote,
                'comment' => $comment,
                'created_at' => self::now(),
            )
        );

        self::recount_application( $application_id );
        return true;
    }

    private static function recount_application( $application_id ) {
        global $wpdb;
        $application = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table( 'applications' ) . " WHERE id=%d", absint( $application_id ) ),
            ARRAY_A
        );
        if ( ! $application || 'voting' !== $application['status'] ) {
            return;
        }

        $yes = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM " . self::table( 'application_votes' ) . " WHERE application_id=%d AND vote='yes'", $application_id )
        );
        $no = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM " . self::table( 'application_votes' ) . " WHERE application_id=%d AND vote='no'", $application_id )
        );
        $eligible = self::decode( $application['eligible_voters'] );
        $remaining = max( 0, count( $eligible ) - $yes - $no );
        $threshold = (int) $application['threshold'];
        $status = 'voting';

        if ( $yes >= $threshold ) {
            $status = 'eligible_admin';
        } elseif ( $yes + $remaining < $threshold ) {
            $status = 'rejected';
        }

        $wpdb->update(
            self::table( 'applications' ),
            array(
                'yes_count' => $yes,
                'no_count' => $no,
                'status' => $status,
                'decided_at' => 'rejected' === $status ? self::now() : null,
            ),
            array( 'id' => absint( $application_id ) )
        );

        if ( 'rejected' === $status ) {
            self::notify_application_result( $application_id, false );
        }
    }

    public static function admin_decide_application( $application_id, $approve, $admin_id ) {
        global $wpdb;
        $application = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table( 'applications' ) . " WHERE id=%d", absint( $application_id ) ),
            ARRAY_A
        );
        if ( ! $application || 'eligible_admin' !== $application['status'] ) {
            return new WP_Error( 'vri_application', 'Application is not ready for the administrator decision.' );
        }

        if ( ! $approve ) {
            $wpdb->update(
                self::table( 'applications' ),
                array(
                    'status' => 'rejected',
                    'decided_at' => self::now(),
                    'decided_by' => absint( $admin_id ),
                ),
                array( 'id' => absint( $application_id ) )
            );
            self::notify_application_result( $application_id, false );
            return true;
        }

        $payload = self::decode( $application['payload'] );
        $email = sanitize_email( $application['email'] );
        $existing_user = get_user_by( 'email', $email );

        if ( $existing_user && user_can( $existing_user, 'manage_options' ) ) {
            return new WP_Error( 'vri_admin_email', 'This email belongs to an administrator. Assign the community manually.' );
        }

        $new_user = false;
        if ( $existing_user ) {
            $user_id = (int) $existing_user->ID;
            $user = new WP_User( $user_id );
            $user->add_role( 'vri_community_owner' );
        } else {
            $base = sanitize_user( sanitize_title( $application['community_name'] ), true );
            if ( '' === $base ) {
                $base = 'community';
            }
            $login = $base;
            $n = 2;
            while ( username_exists( $login ) ) {
                $login = $base . $n;
                $n++;
            }

            $user_id = wp_insert_user(
                array(
                    'user_login' => $login,
                    'user_email' => $email,
                    'display_name' => $application['community_name'],
                    'user_pass' => wp_generate_password( 32, true, true ),
                    'role' => 'vri_community_owner',
                )
            );
            if ( is_wp_error( $user_id ) ) {
                return $user_id;
            }
            $new_user = true;
        }

        $slug = wp_unique_post_slug( sanitize_title( $application['community_name'] ), 0, 'publish', 'vri_community', 0 );
        if ( ! $slug ) {
            $slug = sanitize_title( $application['community_name'] ) . '-' . wp_generate_password( 6, false, false );
        }

        $ok = $wpdb->insert(
            self::table( 'communities' ),
            array(
                'owner_user_id' => absint( $user_id ),
                'name' => sanitize_text_field( $application['community_name'] ),
                'slug' => $slug,
                'discord_url' => esc_url_raw( $payload['discord_url'] ?? '' ),
                'vrchat_group_url' => esc_url_raw( $payload['vrchat_group_url'] ?? '' ),
                'vrchat_world_url' => esc_url_raw( $payload['vrchat_world_url'] ?? '' ),
                'instagram_url' => esc_url_raw( $payload['instagram_url'] ?? '' ),
                'website_url' => esc_url_raw( $payload['website_url'] ?? '' ),
                'status' => 'approved',
                'created_at' => self::now(),
                'updated_at' => self::now(),
            )
        );
        if ( ! $ok ) {
            return new WP_Error( 'vri_db', 'Could not create the community.' );
        }

        $wpdb->update(
            self::table( 'applications' ),
            array(
                'status' => 'approved',
                'decided_at' => self::now(),
                'decided_by' => absint( $admin_id ),
            ),
            array( 'id' => absint( $application_id ) )
        );

        if ( $new_user ) {
            wp_new_user_notification( $user_id, null, 'user' );
        }
        self::notify_application_result( $application_id, true );
        return true;
    }

    private static function notify_application_result( $application_id, $approved ) {
        global $wpdb;
        $application = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table( 'applications' ) . " WHERE id=%d", absint( $application_id ) ),
            ARRAY_A
        );
        if ( ! $application ) {
            return;
        }
        $settings = get_option( 'vri_form_settings', array() );
        $subject = $approved ? 'VRChat Italia - candidatura accettata' : 'VRChat Italia - esito candidatura';
        $message = $approved
            ? ( $settings['yes_it'] ?? 'La tua candidatura è stata accettata.' )
            : ( $settings['no_it'] ?? 'La tua candidatura non è stata accettata.' );
        wp_mail( $application['email'], $subject, wp_strip_all_tags( $message ) );
    }

    public static function application_votes( $application_id ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT v.*, u.display_name, u.user_email
                 FROM " . self::table( 'application_votes' ) . " v
                 LEFT JOIN {$wpdb->users} u ON u.ID=v.voter_user_id
                 WHERE v.application_id=%d ORDER BY v.created_at ASC",
                absint( $application_id )
            ),
            ARRAY_A
        );
    }

    private static function normalize_event_payload( $data ) {
        $tz = new DateTimeZone( self::TZ );
        try {
            $start = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', sanitize_text_field( $data['start_local'] ?? '' ), $tz );
            $end = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', sanitize_text_field( $data['end_local'] ?? '' ), $tz );
        } catch ( Exception $e ) {
            return new WP_Error( 'vri_date', 'Invalid event date.' );
        }

        if ( ! $start || ! $end || $end <= $start ) {
            return new WP_Error( 'vri_date', 'End time must be after start time.' );
        }
        if ( $end->getTimestamp() - $start->getTimestamp() > DAY_IN_SECONDS ) {
            return new WP_Error( 'vri_duration', 'Events may not exceed 24 hours.' );
        }

        $allowed_tags = array( 'gaming', 'drinking', 'world-exploration', 'dj-disco', 'hangout', 'age-gated', 'other' );
        $tags = array_values( array_intersect( $allowed_tags, array_map( 'sanitize_key', (array) ( $data['tags'] ?? array() ) ) ) );
        if ( ! $tags ) {
            $tags = array( 'other' );
        }

        $platforms = array_values( array_intersect( array( 'PC', 'Quest', 'Android' ), array_map( 'sanitize_text_field', (array) ( $data['platforms'] ?? array() ) ) ) );
        $access = sanitize_key( $data['access_type'] ?? 'group' );
        if ( ! in_array( $access, array( 'public', 'group', 'friends', 'private' ), true ) ) {
            $access = 'group';
        }

        return array(
            'title_it' => sanitize_text_field( $data['title_it'] ?? '' ),
            'title_en' => sanitize_text_field( $data['title_en'] ?? '' ),
            'description_it' => sanitize_textarea_field( $data['description_it'] ?? '' ),
            'description_en' => sanitize_textarea_field( $data['description_en'] ?? '' ),
            'start_at_utc' => $start->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
            'end_at_utc' => $end->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
            'world_name' => sanitize_text_field( $data['world_name'] ?? '' ),
            'access_type' => $access,
            'platforms' => implode( ',', $platforms ),
            'capacity' => isset( $data['capacity'] ) && '' !== $data['capacity'] ? max( 1, absint( $data['capacity'] ) ) : null,
            'registration_url' => esc_url_raw( $data['registration_url'] ?? '' ),
            'event_url' => esc_url_raw( $data['event_url'] ?? '' ),
            'tags' => implode( ',', $tags ),
        );
    }

    public static function save_event_draft( $community_id, $user_id, $event_id, $data ) {
        global $wpdb;
        $community = self::get_community( $community_id );
        if ( ! $community || (int) $community['owner_user_id'] !== (int) $user_id ) {
            return new WP_Error( 'vri_forbidden', 'Community not available.' );
        }

        $payload = self::normalize_event_payload( $data );
        if ( is_wp_error( $payload ) ) {
            return $payload;
        }
        if ( '' === $payload['title_it'] && '' === $payload['title_en'] ) {
            return new WP_Error( 'vri_title', 'At least one event title is required.' );
        }

        $event_id = absint( $event_id );
        $event = null;
        if ( $event_id ) {
            $event = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM " . self::table( 'events' ) . " WHERE id=%d AND community_id=%d", $event_id, $community_id ),
                ARRAY_A
            );
            if ( ! $event ) {
                return new WP_Error( 'vri_event', 'Event not available.' );
            }
        }

        $slot = self::slot_is_available(
            $community_id,
            $payload['start_at_utc'],
            $payload['end_at_utc'],
            $event_id
        );

        $pending_status = $slot['available'] ? 'admin_review' : 'blocked';
        $pending_slot_mode = $slot['available'] ? 'normal' : null;
        $rotation = self::approved_community_count();
        $now = self::now();

        if ( $event_id ) {
            $wpdb->update(
                self::table( 'events' ),
                array(
                    'pending_status' => $pending_status,
                    'pending_payload' => self::json( $payload ),
                    'pending_slot_mode' => $pending_slot_mode,
                    'pending_rotation_size' => $rotation,
                    'updated_at' => $now,
                ),
                array( 'id' => $event_id )
            );
        } else {
            $wpdb->insert(
                self::table( 'events' ),
                array(
                    'community_id' => absint( $community_id ),
                    'status' => 'pending',
                    'pending_status' => $pending_status,
                    'pending_payload' => self::json( $payload ),
                    'pending_slot_mode' => $pending_slot_mode,
                    'pending_rotation_size' => $rotation,
                    'created_by' => absint( $user_id ),
                    'created_at' => $now,
                    'updated_at' => $now,
                )
            );
            $event_id = (int) $wpdb->insert_id;
        }

        return array(
            'event_id' => $event_id,
            'blocked' => ! $slot['available'],
            'blocking_event_id' => $slot['blocking_event_id'] ?? 0,
        );
    }

    public static function owner_events( $community_id ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table( 'events' ) . " WHERE community_id=%d AND status<>'cancelled' ORDER BY COALESCE(start_at_utc,created_at) DESC",
                absint( $community_id )
            ),
            ARRAY_A
        );
    }

    public static function delete_event( $event_id, $user_id ) {
        global $wpdb;
        $community = self::get_owner_community( $user_id );
        if ( ! $community ) {
            return new WP_Error( 'vri_forbidden', 'Community not available.' );
        }
        $event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::table( 'events' ) . " WHERE id=%d AND community_id=%d",
                absint( $event_id ),
                $community['id']
            ),
            ARRAY_A
        );
        if ( ! $event ) {
            return new WP_Error( 'vri_event', 'Event not available.' );
        }
        $wpdb->update(
            self::table( 'events' ),
            array(
                'status' => 'cancelled',
                'pending_status' => null,
                'pending_payload' => null,
                'updated_at' => self::now(),
            ),
            array( 'id' => absint( $event_id ) )
        );
        return true;
    }

    public static function slot_is_available( $community_id, $start_utc, $end_utc, $exclude_event_id = 0 ) {
        global $wpdb;
        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table( 'events' ) . "
                 WHERE community_id=%d AND id<>%d AND status='approved' AND slot_mode='normal' AND start_at_utc<=%s
                 ORDER BY start_at_utc DESC",
                absint( $community_id ),
                absint( $exclude_event_id ),
                $start_utc
            ),
            ARRAY_A
        );

        foreach ( $events as $event ) {
            $rotation = max( 1, (int) $event['rotation_size'] );
            $distance = self::week_distance( $event['start_at_utc'], $start_utc );
            if ( $distance >= 0 && $distance < $rotation && self::weekly_overlap( $event['start_at_utc'], $event['end_at_utc'], $start_utc, $end_utc ) ) {
                return array( 'available' => false, 'blocking_event_id' => (int) $event['id'] );
            }
        }

        $pending = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id,pending_payload,pending_rotation_size FROM " . self::table( 'events' ) . "
                 WHERE community_id=%d AND id<>%d AND pending_status='admin_review' AND pending_slot_mode='normal'",
                absint( $community_id ),
                absint( $exclude_event_id )
            ),
            ARRAY_A
        );
        foreach ( $pending as $event ) {
            $payload = self::decode( $event['pending_payload'] );
            if ( empty( $payload['start_at_utc'] ) || empty( $payload['end_at_utc'] ) ) {
                continue;
            }
            $rotation = max( 1, (int) $event['pending_rotation_size'] );
            $distance = self::week_distance( $payload['start_at_utc'], $start_utc );
            if ( $distance >= 0 && $distance < $rotation && self::weekly_overlap( $payload['start_at_utc'], $payload['end_at_utc'], $start_utc, $end_utc ) ) {
                return array( 'available' => false, 'blocking_event_id' => (int) $event['id'] );
            }
        }

        return array( 'available' => true );
    }

    private static function local_dt( $utc_mysql ) {
        $utc = new DateTimeZone( 'UTC' );
        $local = new DateTimeZone( self::TZ );
        return ( new DateTimeImmutable( $utc_mysql, $utc ) )->setTimezone( $local );
    }

    private static function week_distance( $from_utc, $to_utc ) {
        $from = self::local_dt( $from_utc );
        $to = self::local_dt( $to_utc );

        $from_monday = $from->modify( 'monday this week' )->setTime( 0, 0 );
        $to_monday = $to->modify( 'monday this week' )->setTime( 0, 0 );
        $days = (int) $from_monday->diff( $to_monday )->format( '%r%a' );
        return (int) floor( $days / 7 );
    }

    private static function weekly_segments( $start_utc, $end_utc ) {
        $start = self::local_dt( $start_utc );
        $end = self::local_dt( $end_utc );
        $start_min = ( (int) $start->format( 'N' ) - 1 ) * 1440 + (int) $start->format( 'H' ) * 60 + (int) $start->format( 'i' );
        $duration = max( 1, (int) round( ( strtotime( $end_utc . ' UTC' ) - strtotime( $start_utc . ' UTC' ) ) / 60 ) );
        $end_min = $start_min + $duration;
        if ( $end_min <= 10080 ) {
            return array( array( $start_min, $end_min ) );
        }
        return array(
            array( $start_min, 10080 ),
            array( 0, $end_min - 10080 ),
        );
    }

    private static function weekly_overlap( $a_start, $a_end, $b_start, $b_end ) {
        $a = self::weekly_segments( $a_start, $a_end );
        $b = self::weekly_segments( $b_start, $b_end );
        foreach ( $a as $aa ) {
            foreach ( $b as $bb ) {
                if ( max( $aa[0], $bb[0] ) < min( $aa[1], $bb[1] ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function create_slot_request( $event_id, $user_id ) {
        global $wpdb;
        $community = self::get_owner_community( $user_id );
        if ( ! $community ) {
            return new WP_Error( 'vri_forbidden', 'Community not available.' );
        }
        $event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::table( 'events' ) . " WHERE id=%d AND community_id=%d",
                absint( $event_id ),
                $community['id']
            ),
            ARRAY_A
        );
        if ( ! $event || ! in_array( $event['pending_status'], array( 'blocked', 'slot_rejected' ), true ) ) {
            return new WP_Error( 'vri_event', 'This event cannot request the date.' );
        }

        $existing = $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM " . self::table( 'slot_requests' ) . " WHERE event_id=%d", $event_id )
        );
        if ( $existing ) {
            $wpdb->delete( self::table( 'slot_votes' ), array( 'request_id' => absint( $existing ) ) );
            $wpdb->delete( self::table( 'slot_requests' ), array( 'id' => absint( $existing ) ) );
        }

        $voters = self::owner_ids( $user_id );
        if ( ! $voters ) {
            $wpdb->update(
                self::table( 'events' ),
                array(
                    'pending_status' => 'admin_review',
                    'pending_slot_mode' => 'override',
                    'pending_rotation_size' => 1,
                ),
                array( 'id' => absint( $event_id ) )
            );
            return true;
        }

        $wpdb->insert(
            self::table( 'slot_requests' ),
            array(
                'event_id' => absint( $event_id ),
                'community_id' => absint( $community['id'] ),
                'eligible_voters' => self::json( $voters ),
                'status' => 'voting',
                'expires_at' => gmdate( 'Y-m-d H:i:s', time() + self::SLOT_VOTE_HOURS * HOUR_IN_SECONDS ),
                'created_at' => self::now(),
            )
        );
        $wpdb->update(
            self::table( 'events' ),
            array( 'pending_status' => 'slot_vote' ),
            array( 'id' => absint( $event_id ) )
        );
        return true;
    }

    public static function pending_slot_requests_for_owner( $user_id ) {
        global $wpdb;
        $requests = $wpdb->get_results(
            "SELECT r.*, e.pending_payload, c.name AS community_name
             FROM " . self::table( 'slot_requests' ) . " r
             INNER JOIN " . self::table( 'events' ) . " e ON e.id=r.event_id
             INNER JOIN " . self::table( 'communities' ) . " c ON c.id=r.community_id
             WHERE r.status='voting'
             ORDER BY r.created_at ASC",
            ARRAY_A
        );

        $out = array();
        foreach ( $requests as $request ) {
            $eligible = array_map( 'absint', self::decode( $request['eligible_voters'] ) );
            if ( ! in_array( absint( $user_id ), $eligible, true ) ) {
                continue;
            }
            $voted = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM " . self::table( 'slot_votes' ) . " WHERE request_id=%d AND voter_user_id=%d",
                    $request['id'],
                    $user_id
                )
            );
            if ( ! $voted ) {
                $request['payload_array'] = self::decode( $request['pending_payload'] );
                $out[] = $request;
            }
        }
        return $out;
    }

    public static function cast_slot_vote( $request_id, $user_id, $vote ) {
        global $wpdb;
        $vote = 'yes' === $vote ? 'yes' : ( 'no' === $vote ? 'no' : '' );
        if ( ! $vote ) {
            return new WP_Error( 'vri_vote', 'Invalid vote.' );
        }

        $request = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table( 'slot_requests' ) . " WHERE id=%d", absint( $request_id ) ),
            ARRAY_A
        );
        if ( ! $request || 'voting' !== $request['status'] ) {
            return new WP_Error( 'vri_request', 'Request is not open.' );
        }

        $eligible = array_map( 'absint', self::decode( $request['eligible_voters'] ) );
        if ( ! in_array( absint( $user_id ), $eligible, true ) ) {
            return new WP_Error( 'vri_forbidden', 'You cannot vote on this request.' );
        }
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM " . self::table( 'slot_votes' ) . " WHERE request_id=%d AND voter_user_id=%d",
                $request_id,
                $user_id
            )
        );
        if ( $exists ) {
            return new WP_Error( 'vri_voted', 'You have already voted.' );
        }

        $wpdb->insert(
            self::table( 'slot_votes' ),
            array(
                'request_id' => absint( $request_id ),
                'voter_user_id' => absint( $user_id ),
                'vote' => $vote,
                'is_auto' => 0,
                'created_at' => self::now(),
            )
        );

        if ( 'no' === $vote ) {
            self::reject_slot_request( $request_id );
            return true;
        }

        $yes = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM " . self::table( 'slot_votes' ) . " WHERE request_id=%d AND vote='yes'", $request_id )
        );
        if ( $yes >= count( $eligible ) ) {
            self::approve_slot_request( $request_id );
        }
        return true;
    }

    private static function approve_slot_request( $request_id ) {
        global $wpdb;
        $request = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table( 'slot_requests' ) . " WHERE id=%d", absint( $request_id ) ),
            ARRAY_A
        );
        if ( ! $request || 'voting' !== $request['status'] ) {
            return;
        }
        $wpdb->update(
            self::table( 'slot_requests' ),
            array( 'status' => 'approved', 'resolved_at' => self::now() ),
            array( 'id' => absint( $request_id ) )
        );
        $wpdb->update(
            self::table( 'events' ),
            array(
                'pending_status' => 'admin_review',
                'pending_slot_mode' => 'override',
                'pending_rotation_size' => 1,
            ),
            array( 'id' => absint( $request['event_id'] ) )
        );
    }

    private static function reject_slot_request( $request_id ) {
        global $wpdb;
        $request = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table( 'slot_requests' ) . " WHERE id=%d", absint( $request_id ) ),
            ARRAY_A
        );
        if ( ! $request || 'voting' !== $request['status'] ) {
            return;
        }
        $wpdb->update(
            self::table( 'slot_requests' ),
            array( 'status' => 'rejected', 'resolved_at' => self::now() ),
            array( 'id' => absint( $request_id ) )
        );
        $wpdb->update(
            self::table( 'events' ),
            array( 'pending_status' => 'slot_rejected' ),
            array( 'id' => absint( $request['event_id'] ) )
        );
    }

    public static function process_slot_timeouts() {
        global $wpdb;
        $requests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table( 'slot_requests' ) . " WHERE status='voting' AND expires_at<=%s",
                self::now()
            ),
            ARRAY_A
        );

        foreach ( $requests as $request ) {
            $eligible = array_map( 'absint', self::decode( $request['eligible_voters'] ) );
            $votes = $wpdb->get_results(
                $wpdb->prepare( "SELECT voter_user_id,vote FROM " . self::table( 'slot_votes' ) . " WHERE request_id=%d", $request['id'] ),
                ARRAY_A
            );
            $voted = array();
            $has_no = false;
            foreach ( $votes as $vote ) {
                $voted[] = absint( $vote['voter_user_id'] );
                if ( 'no' === $vote['vote'] ) {
                    $has_no = true;
                }
            }
            if ( $has_no ) {
                self::reject_slot_request( $request['id'] );
                continue;
            }
            foreach ( array_diff( $eligible, $voted ) as $user_id ) {
                $wpdb->insert(
                    self::table( 'slot_votes' ),
                    array(
                        'request_id' => absint( $request['id'] ),
                        'voter_user_id' => absint( $user_id ),
                        'vote' => 'yes',
                        'is_auto' => 1,
                        'created_at' => self::now(),
                    )
                );
            }
            self::approve_slot_request( $request['id'] );
        }
    }

    public static function admin_decide_event( $event_id, $approve, $admin_id ) {
        global $wpdb;
        $event = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table( 'events' ) . " WHERE id=%d", absint( $event_id ) ),
            ARRAY_A
        );
        if ( ! $event || 'admin_review' !== $event['pending_status'] || empty( $event['pending_payload'] ) ) {
            return new WP_Error( 'vri_event', 'Event is not ready for moderation.' );
        }

        if ( ! $approve ) {
            $data = array(
                'pending_status' => null,
                'pending_payload' => null,
                'pending_slot_mode' => null,
                'pending_rotation_size' => null,
                'updated_at' => self::now(),
            );
            if ( 'approved' !== $event['status'] ) {
                $data['status'] = 'rejected';
            }
            $wpdb->update( self::table( 'events' ), $data, array( 'id' => absint( $event_id ) ) );
            return true;
        }

        $payload = self::decode( $event['pending_payload'] );
        $required = array( 'start_at_utc', 'end_at_utc', 'title_it', 'title_en' );
        foreach ( $required as $key ) {
            if ( ! array_key_exists( $key, $payload ) ) {
                return new WP_Error( 'vri_payload', 'Invalid pending event data.' );
            }
        }

        $update = $payload;
        $update['status'] = 'approved';
        $update['slot_mode'] = 'override' === $event['pending_slot_mode'] ? 'override' : 'normal';
        $update['rotation_size'] = 'override' === $update['slot_mode'] ? 1 : max( 1, (int) $event['pending_rotation_size'] );
        $update['pending_status'] = null;
        $update['pending_payload'] = null;
        $update['pending_slot_mode'] = null;
        $update['pending_rotation_size'] = null;
        $update['approved_by'] = absint( $admin_id );
        $update['approved_at'] = self::now();
        $update['updated_at'] = self::now();

        $wpdb->update( self::table( 'events' ), $update, array( 'id' => absint( $event_id ) ) );
        return true;
    }

    public static function event_local_input( $utc_mysql ) {
        if ( ! $utc_mysql ) {
            return '';
        }
        return self::local_dt( $utc_mysql )->format( 'Y-m-d\\TH:i' );
    }

    public static function event_local_display( $utc_mysql, $lang = 'en' ) {
        if ( ! $utc_mysql ) {
            return '';
        }
        $dt = self::local_dt( $utc_mysql );
        return 'it' === $lang ? $dt->format( 'd/m/Y H:i' ) : $dt->format( 'Y-m-d H:i' );
    }

    public static function public_events( $from = '', $to = '' ) {
        global $wpdb;
        $where = "status='approved'";
        $args = array();
        if ( $from ) {
            $where .= ' AND end_at_utc>=%s';
            $args[] = gmdate( 'Y-m-d H:i:s', strtotime( $from ) );
        }
        if ( $to ) {
            $where .= ' AND start_at_utc<=%s';
            $args[] = gmdate( 'Y-m-d H:i:s', strtotime( $to ) );
        }
        $sql = "SELECT e.*, c.name AS community_name, c.vrchat_group_url
                FROM " . self::table( 'events' ) . " e
                INNER JOIN " . self::table( 'communities' ) . " c ON c.id=e.community_id
                WHERE {$where}
                ORDER BY e.start_at_utc ASC";
        if ( $args ) {
            $sql = $wpdb->prepare( $sql, $args );
        }
        return $wpdb->get_results( $sql, ARRAY_A );
    }

    public static function pending_admin_events() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT e.*, c.name AS community_name
             FROM " . self::table( 'events' ) . " e
             INNER JOIN " . self::table( 'communities' ) . " c ON c.id=e.community_id
             WHERE e.pending_status='admin_review'
             ORDER BY e.updated_at ASC",
            ARRAY_A
        );
    }

    public static function applications_for_admin() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM " . self::table( 'applications' ) . " ORDER BY created_at DESC",
            ARRAY_A
        );
    }

    public static function images_for_admin() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT i.*, c.name AS community_name
             FROM " . self::table( 'community_images' ) . " i
             INNER JOIN " . self::table( 'communities' ) . " c ON c.id=i.community_id
             WHERE i.status='pending'
             ORDER BY i.created_at ASC",
            ARRAY_A
        );
    }

    public static function slot_requests_for_admin() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT r.*, c.name AS community_name
             FROM " . self::table( 'slot_requests' ) . " r
             INNER JOIN " . self::table( 'communities' ) . " c ON c.id=r.community_id
             ORDER BY r.created_at DESC",
            ARRAY_A
        );
    }

    public static function slot_votes( $request_id ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT v.*, u.display_name, u.user_email
                 FROM " . self::table( 'slot_votes' ) . " v
                 LEFT JOIN {$wpdb->users} u ON u.ID=v.voter_user_id
                 WHERE v.request_id=%d ORDER BY v.created_at ASC",
                absint( $request_id )
            ),
            ARRAY_A
        );
    }
}
