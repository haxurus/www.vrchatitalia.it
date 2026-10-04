<?php
defined( 'ABSPATH' ) || exit;

final class VRI_Design_Sync {
    const OPTION = 'vri_design_source';
    const MAX_FILES = 500;
    const MAX_FILE_SIZE = 5242880;
    const MAX_TOTAL = 31457280;

    public static function hooks() {
        add_action( 'admin_init', array( __CLASS__, 'handle_admin_actions' ) );
    }

    private static function settings() {
        $value = get_option( self::OPTION, array() );
        return wp_parse_args(
            is_array( $value ) ? $value : array(),
            array(
                'repository' => 'haxurus/www.vrchatitalia.it',
                'branch' => 'main',
                'prefix' => 'wordpress/design',
                'active_commit' => '',
                'active_files' => array(),
            )
        );
    }

    private static function validate_repository( $value ) {
        $value = trim( (string) $value );
        $value = preg_replace( '~^https://github\.com/~i', '', $value );
        $value = preg_replace( '~\.git$~i', '', $value );
        if ( ! preg_match( '~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~D', $value ) ) {
            return new WP_Error( 'vri_repo', 'Use owner/repository or a public github.com repository URL.' );
        }
        return $value;
    }

    private static function validate_branch( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            $value = 'main';
        }
        if ( strlen( $value ) > 180 || ! preg_match( '~^[A-Za-z0-9._/-]+$~D', $value ) || false !== strpos( $value, '..' ) ) {
            return new WP_Error( 'vri_branch', 'Invalid branch.' );
        }
        return $value;
    }

    private static function validate_prefix( $value ) {
        $value = trim( str_replace( '\\', '/', (string) $value ), '/' );
        if ( ! preg_match( '~^[A-Za-z0-9._/-]+$~D', $value ) || false !== strpos( $value, '..' ) ) {
            return new WP_Error( 'vri_prefix', 'Invalid design source path.' );
        }
        return $value;
    }

    private static function api_get( $url ) {
        $response = wp_safe_remote_get(
            $url,
            array(
                'timeout' => 20,
                'redirection' => 2,
                'headers' => array(
                    'Accept' => 'application/vnd.github+json',
                    'User-Agent' => 'VRChat-Italia-Design-Sync/' . VRI_CORE_VERSION,
                ),
            )
        );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error( 'vri_github', 'GitHub returned HTTP ' . $code . '.' );
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return is_array( $data ) ? $data : new WP_Error( 'vri_github_json', 'Invalid GitHub response.' );
    }

    public static function manifest( $settings = null ) {
        $settings = $settings ?: self::settings();
        $repo = self::validate_repository( $settings['repository'] );
        $branch = self::validate_branch( $settings['branch'] );
        $prefix = self::validate_prefix( $settings['prefix'] );
        foreach ( array( $repo, $branch, $prefix ) as $check ) {
            if ( is_wp_error( $check ) ) {
                return $check;
            }
        }

        $commit = self::api_get( 'https://api.github.com/repos/' . rawurlencode( explode( '/', $repo )[0] ) . '/' . rawurlencode( explode( '/', $repo )[1] ) . '/commits/' . rawurlencode( $branch ) );
        if ( is_wp_error( $commit ) ) {
            return $commit;
        }
        $sha = $commit['sha'] ?? '';
        if ( ! preg_match( '/^[a-f0-9]{40}$/D', $sha ) ) {
            return new WP_Error( 'vri_commit', 'Could not resolve repository commit.' );
        }

        $tree = self::api_get( 'https://api.github.com/repos/' . $repo . '/git/trees/' . $sha . '?recursive=1' );
        if ( is_wp_error( $tree ) ) {
            return $tree;
        }
        if ( ! empty( $tree['truncated'] ) ) {
            return new WP_Error( 'vri_tree', 'Repository tree is too large or incomplete.' );
        }

        $allowed = array( 'css', 'js', 'svg', 'png', 'jpg', 'jpeg', 'webp', 'gif' );
        $files = array();
        $total = 0;
        $needle = $prefix . '/';

        foreach ( (array) ( $tree['tree'] ?? array() ) as $entry ) {
            if ( 'blob' !== ( $entry['type'] ?? '' ) ) {
                continue;
            }
            $path = (string) ( $entry['path'] ?? '' );
            if ( 0 !== strpos( $path, $needle ) ) {
                continue;
            }
            $relative = substr( $path, strlen( $needle ) );
            if ( ! $relative || false !== strpos( $relative, '..' ) || 0 === strpos( $relative, '/' ) ) {
                return new WP_Error( 'vri_path', 'Unsafe repository path.' );
            }
            $ext = strtolower( pathinfo( $relative, PATHINFO_EXTENSION ) );
            if ( ! in_array( $ext, $allowed, true ) ) {
                continue;
            }
            $size = (int) ( $entry['size'] ?? 0 );
            if ( $size < 0 || $size > self::MAX_FILE_SIZE ) {
                return new WP_Error( 'vri_size', 'A design asset exceeds the per-file limit.' );
            }
            $total += $size;
            if ( $total > self::MAX_TOTAL || count( $files ) >= self::MAX_FILES ) {
                return new WP_Error( 'vri_limit', 'Design source exceeds synchronization safety limits.' );
            }
            $files[ $relative ] = array(
                'repository_path' => $path,
                'size' => $size,
                'sha' => (string) ( $entry['sha'] ?? '' ),
            );
        }

        if ( ! isset( $files['site.css'] ) || ! isset( $files['site.js'] ) ) {
            return new WP_Error( 'vri_required', 'The design source must contain site.css and site.js.' );
        }

        return array(
            'commit' => $sha,
            'repository' => $repo,
            'branch' => $branch,
            'prefix' => $prefix,
            'files' => $files,
            'total' => $total,
        );
    }

    private static function roots() {
        $uploads = wp_upload_dir();
        $base = trailingslashit( $uploads['basedir'] ) . 'vrchat-italia-design';
        $url = trailingslashit( $uploads['baseurl'] ) . 'vrchat-italia-design';
        return array( $base, $url );
    }

    private static function safe_mkdir( $path ) {
        if ( is_link( $path ) ) {
            return false;
        }
        return is_dir( $path ) || wp_mkdir_p( $path );
    }

    private static function delete_tree( $path ) {
        if ( ! is_dir( $path ) || is_link( $path ) ) {
            return;
        }
        $items = scandir( $path );
        if ( ! is_array( $items ) ) {
            return;
        }
        foreach ( $items as $item ) {
            if ( '.' === $item || '..' === $item ) {
                continue;
            }
            $file = $path . '/' . $item;
            if ( is_dir( $file ) && ! is_link( $file ) ) {
                self::delete_tree( $file );
            } else {
                @unlink( $file );
            }
        }
        @rmdir( $path );
    }

    public static function synchronize() {
        $manifest = self::manifest();
        if ( is_wp_error( $manifest ) ) {
            return $manifest;
        }

        list( $root ) = self::roots();
        $snapshots = $root . '/snapshots';
        $stage = $root . '/stage-' . substr( $manifest['commit'], 0, 12 ) . '-' . wp_generate_password( 8, false, false );
        $final = $snapshots . '/' . $manifest['commit'];

        if ( ! self::safe_mkdir( $root ) || ! self::safe_mkdir( $snapshots ) || ! self::safe_mkdir( $stage ) ) {
            return new WP_Error( 'vri_write', 'Cannot create design synchronization directory.' );
        }

        foreach ( $manifest['files'] as $relative => $info ) {
            $target = $stage . '/' . $relative;
            $dir = dirname( $target );
            if ( ! self::safe_mkdir( $dir ) ) {
                self::delete_tree( $stage );
                return new WP_Error( 'vri_write', 'Cannot create a design asset directory.' );
            }

            $url = 'https://raw.githubusercontent.com/' . $manifest['repository'] . '/' . $manifest['commit'] . '/' . str_replace( '%2F', '/', rawurlencode( $info['repository_path'] ) );
            $response = wp_safe_remote_get(
                $url,
                array(
                    'timeout' => 30,
                    'redirection' => 2,
                    'limit_response_size' => self::MAX_FILE_SIZE + 1,
                    'headers' => array( 'User-Agent' => 'VRChat-Italia-Design-Sync/' . VRI_CORE_VERSION ),
                )
            );
            if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
                self::delete_tree( $stage );
                return is_wp_error( $response ) ? $response : new WP_Error( 'vri_download', 'Could not download ' . $relative );
            }
            $body = wp_remote_retrieve_body( $response );
            if ( strlen( $body ) > self::MAX_FILE_SIZE || ( $info['size'] && strlen( $body ) !== (int) $info['size'] ) ) {
                self::delete_tree( $stage );
                return new WP_Error( 'vri_integrity', 'Unexpected size for ' . $relative );
            }
            $blob_sha = sha1( 'blob ' . strlen( $body ) . "\0" . $body );
            if ( ! empty( $info['sha'] ) && ! hash_equals( strtolower( $info['sha'] ), strtolower( $blob_sha ) ) ) {
                self::delete_tree( $stage );
                return new WP_Error( 'vri_integrity', 'Git blob hash mismatch for ' . $relative );
            }
            if ( false === file_put_contents( $target, $body, LOCK_EX ) ) {
                self::delete_tree( $stage );
                return new WP_Error( 'vri_write', 'Could not write ' . $relative );
            }
        }

        if ( is_dir( $final ) ) {
            self::delete_tree( $stage );
        } elseif ( ! @rename( $stage, $final ) ) {
            self::delete_tree( $stage );
            return new WP_Error( 'vri_publish', 'Could not publish the staged design snapshot.' );
        }

        $settings = self::settings();
        $settings['active_commit'] = $manifest['commit'];
        $settings['active_files'] = array_keys( $manifest['files'] );
        update_option( self::OPTION, $settings, false );

        self::cleanup_snapshots( $final );
        return $manifest;
    }

    private static function cleanup_snapshots( $keep_path ) {
        list( $root ) = self::roots();
        $dir = $root . '/snapshots';
        if ( ! is_dir( $dir ) ) {
            return;
        }
        $items = array();
        foreach ( (array) scandir( $dir ) as $item ) {
            if ( preg_match( '/^[a-f0-9]{40}$/D', $item ) && is_dir( $dir . '/' . $item ) ) {
                $items[ $item ] = filemtime( $dir . '/' . $item );
            }
        }
        arsort( $items );
        $keep = 0;
        foreach ( $items as $item => $mtime ) {
            if ( $dir . '/' . $item === $keep_path || $keep < 2 ) {
                $keep++;
                continue;
            }
            self::delete_tree( $dir . '/' . $item );
        }
    }

    public static function asset_url( $path, $fallback = '' ) {
        $settings = self::settings();
        $path = ltrim( str_replace( '\\', '/', (string) $path ), '/' );
        if ( ! in_array( $path, (array) $settings['active_files'], true ) || ! preg_match( '~^[A-Za-z0-9._/-]+$~D', $path ) ) {
            return $fallback;
        }

        list( $root, $url ) = self::roots();
        $file = $root . '/snapshots/' . $settings['active_commit'] . '/' . $path;
        if ( ! is_file( $file ) ) {
            return $fallback;
        }
        return $url . '/snapshots/' . rawurlencode( $settings['active_commit'] ) . '/' . str_replace( '%2F', '/', rawurlencode( $path ) );
    }

    public static function handle_admin_actions() {
        if ( ! isset( $_POST['vri_design_action'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        check_admin_referer( 'vri_design_sync' );

        $action = sanitize_key( $_POST['vri_design_action'] );
        if ( 'save' === $action ) {
            $repo = self::validate_repository( wp_unslash( $_POST['repository'] ?? '' ) );
            $branch = self::validate_branch( wp_unslash( $_POST['branch'] ?? '' ) );
            $prefix = self::validate_prefix( wp_unslash( $_POST['prefix'] ?? '' ) );
            if ( is_wp_error( $repo ) || is_wp_error( $branch ) || is_wp_error( $prefix ) ) {
                $error = is_wp_error( $repo ) ? $repo : ( is_wp_error( $branch ) ? $branch : $prefix );
                set_transient( 'vri_design_notice_' . get_current_user_id(), array( 'error', $error->get_error_message() ), 60 );
            } else {
                $check = self::manifest( array( 'repository' => $repo, 'branch' => $branch, 'prefix' => $prefix ) );
                if ( is_wp_error( $check ) ) {
                    set_transient( 'vri_design_notice_' . get_current_user_id(), array( 'error', $check->get_error_message() ), 60 );
                } else {
                    $settings = self::settings();
                    $settings['repository'] = $repo;
                    $settings['branch'] = $branch;
                    $settings['prefix'] = $prefix;
                    update_option( self::OPTION, $settings, false );
                    set_transient( 'vri_design_notice_' . get_current_user_id(), array( 'success', 'Repository verified and saved. The live design has not changed.' ), 60 );
                }
            }
        } elseif ( 'sync' === $action ) {
            $result = self::synchronize();
            set_transient(
                'vri_design_notice_' . get_current_user_id(),
                is_wp_error( $result )
                    ? array( 'error', $result->get_error_message() )
                    : array( 'success', 'Design synchronized at commit ' . $result['commit'] . '.' ),
                60
            );
        } elseif ( 'check' === $action ) {
            $result = self::manifest();
            set_transient(
                'vri_design_notice_' . get_current_user_id(),
                is_wp_error( $result )
                    ? array( 'error', $result->get_error_message() )
                    : array( 'success', 'Available commit: ' . $result['commit'] . ' - ' . count( $result['files'] ) . ' assets.' ),
                60
            );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=vri-design' ) );
        exit;
    }

    public static function page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $settings = self::settings();
        $notice = get_transient( 'vri_design_notice_' . get_current_user_id() );
        delete_transient( 'vri_design_notice_' . get_current_user_id() );
        ?>
        <div class="wrap vri-admin">
            <h1>VRChat Italia - Design Sync</h1>
            <p>Sincronizza manualmente solo gli asset di design approvati dal repository GitHub. Database, utenti, community, voti, eventi e upload non vengono mai toccati.</p>

            <?php if ( is_array( $notice ) ) : ?>
                <div class="notice notice-<?php echo 'error' === $notice[0] ? 'error' : 'success'; ?> inline"><p><?php echo esc_html( $notice[1] ); ?></p></div>
            <?php endif; ?>

            <div class="vri-admin-panel">
                <h2>Repository source</h2>
                <form method="post">
                    <?php wp_nonce_field( 'vri_design_sync' ); ?>
                    <input type="hidden" name="vri_design_action" value="save">
                    <div class="vri-admin-form-grid">
                        <label>Public GitHub repository<input name="repository" value="<?php echo esc_attr( $settings['repository'] ); ?>" required></label>
                        <label>Branch<input name="branch" value="<?php echo esc_attr( $settings['branch'] ); ?>" required></label>
                        <label>Design path<input name="prefix" value="<?php echo esc_attr( $settings['prefix'] ); ?>" required></label>
                    </div>
                    <p><button class="button" type="submit">Controlla e salva repository</button></p>
                </form>
                <p><strong>Commit attivo:</strong> <code><?php echo esc_html( $settings['active_commit'] ?: 'nessuno - usa gli asset inclusi nel tema' ); ?></code></p>
            </div>

            <div class="vri-admin-panel">
                <h2>Aggiornamento manuale</h2>
                <div class="vri-row-actions">
                    <form method="post"><?php wp_nonce_field( 'vri_design_sync' ); ?><input type="hidden" name="vri_design_action" value="check"><button class="button" type="submit">Controlla aggiornamenti</button></form>
                    <form method="post" onsubmit="return confirm('Sincronizzare il nuovo design da GitHub?')"><?php wp_nonce_field( 'vri_design_sync' ); ?><input type="hidden" name="vri_design_action" value="sync"><button class="button button-primary" type="submit">Sincronizza design</button></form>
                </div>
                <p class="description">La sincronizzazione crea prima uno snapshot completo e lo rende attivo solo dopo aver scaricato e verificato tutti i file. Sono consentiti esclusivamente CSS, JavaScript e asset grafici nella cartella configurata. PHP remoto non viene mai eseguito o copiato.</p>
            </div>
        </div>
        <?php
    }
}
