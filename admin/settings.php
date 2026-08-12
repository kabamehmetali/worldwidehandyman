<?php
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/twilio.php';
require_admin();

$activeTab = $_GET['tab'] ?? 'general';
$validTabs = ['general', 'contact', 'homepage', 'colors', 'integrations', 'account'];
if (!in_array($activeTab, $validTabs, true)) {
    $activeTab = 'general';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $section = $_POST['section'] ?? '';
    $activeTab = in_array($section, $validTabs, true) ? $section : 'general';

    $collect = static function (array $keys): array {
        $pairs = [];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $pairs[$key] = trim((string) $_POST[$key]);
            }
        }
        return $pairs;
    };

    if ($section === 'general') {
        settings_save($collect(['site_name', 'tagline', 'meta_description', 'footer_about', 'designer_name', 'designer_url']));
        $uploadFailed = false;
        foreach (['logo_nav' => 'logo', 'logo_icon' => 'icon', 'logo_footer' => 'footerlogo'] as $key => $prefix) {
            if (!empty($_FILES[$key]['name'])) {
                [$path, $error] = handle_image_upload($_FILES[$key], 'branding', $prefix);
                if ($path === null) {
                    $uploadFailed = true;
                    flash_set('error', ucfirst($prefix) . ' upload failed: ' . $error);
                } else {
                    settings_save([$key => $path]);
                }
            }
        }
        flash_set('success', $uploadFailed ? 'Text settings saved — but the image was NOT updated (see error above).' : 'General settings saved.');
    } elseif ($section === 'contact') {
        settings_save($collect(['phone', 'phone_link', 'email', 'address', 'service_area',
            'hours_weekday', 'hours_weekend', 'facebook_url', 'instagram_url', 'tiktok_url']));
        flash_set('success', 'Contact settings saved.');
    } elseif ($section === 'homepage') {
        settings_save($collect(['hero_title', 'hero_subtitle', 'hero_btn1_text', 'hero_btn1_url',
            'hero_btn2_text', 'hero_btn2_url', 'home_services_title', 'home_services_subtitle',
            'home_about_title', 'home_gallery_title', 'home_testimonials_title',
            'cta_band_title', 'cta_band_subtitle',
            'stat1_number', 'stat1_label', 'stat2_number', 'stat2_label',
            'stat3_number', 'stat3_label', 'stat4_number', 'stat4_label']));
        $uploadFailed = false;
        if (!empty($_FILES['hero_image']['name'])) {
            [$path, $error] = handle_image_upload($_FILES['hero_image'], 'branding', 'hero');
            if ($path === null) {
                $uploadFailed = true;
                flash_set('error', 'Hero image upload failed: ' . $error);
            } else {
                settings_save(['hero_image' => $path]);
            }
        }
        flash_set('success', $uploadFailed ? 'Text settings saved — but the hero image was NOT updated (see error above).' : 'Homepage settings saved.');
    } elseif ($section === 'colors') {
        $colors = $collect(['color_primary', 'color_primary_dark', 'color_accent', 'color_accent_light', 'color_red', 'color_bg_light']);
        foreach ($colors as $key => $value) {
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                unset($colors[$key]);
            }
        }
        settings_save($colors);
        flash_set('success', 'Brand colors saved.');
    } elseif ($section === 'integrations') {
        $pairs = $collect(['twilio_sid', 'twilio_from', 'twilio_to', 'google_maps_api_key', 'map_query']);
        // keep the stored token when the field is left blank
        if (isset($_POST['twilio_token']) && trim((string) $_POST['twilio_token']) !== '') {
            $pairs['twilio_token'] = trim((string) $_POST['twilio_token']);
        }
        $pairs['sms_enabled'] = isset($_POST['sms_enabled']) ? '1' : '0';
        settings_save($pairs);

        if (isset($_POST['send_test_sms'])) {
            // re-read settings after save
            [$ok, $info] = twilio_send_sms(setting_fresh('twilio_to'), 'Test message from ' . setting_fresh('site_name') . ' admin panel — Twilio is working!');
            if ($ok) {
                flash_set('success', 'Integration settings saved. Test SMS sent successfully (SID ' . $info . ').');
            } else {
                flash_set('error', 'Settings saved, but the test SMS failed: ' . $info);
            }
        } else {
            flash_set('success', 'Integration settings saved.');
        }
    } elseif ($section === 'account') {
        $user = admin_user();
        $current = $_POST['current_password'] ?? '';
        $newUsername = trim($_POST['new_username'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            flash_set('error', 'Current password is incorrect.');
        } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
            flash_set('error', 'New password must be at least 8 characters.');
        } elseif ($newPassword !== $confirm) {
            flash_set('error', 'New password and confirmation do not match.');
        } elseif ($newUsername === '' || mb_strlen($newUsername) > 50) {
            flash_set('error', 'Username cannot be empty (max 50 characters).');
        } else {
            if ($newPassword !== '') {
                db()->prepare('UPDATE users SET username = ?, password_hash = ? WHERE id = ?')
                    ->execute([$newUsername, password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
            } else {
                db()->prepare('UPDATE users SET username = ? WHERE id = ?')->execute([$newUsername, $user['id']]);
            }
            flash_set('success', 'Account updated.');
        }
    }
    redirect('admin/settings.php?tab=' . $activeTab);
}

/** Read a setting directly from the DB (bypasses the per-request cache). */
function setting_fresh(string $key, string $default = ''): string
{
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : $value;
}

$adminTitle = 'Settings';
require __DIR__ . '/includes/header.php';

function tab_link(string $tab, string $icon, string $label, string $active): void
{
    $isActive = $tab === $active ? ' active' : '';
    echo '<li class="nav-item"><a class="nav-link' . $isActive . '" href="' . esc(base_url('admin/settings.php?tab=' . $tab)) . '"><i class="' . esc($icon) . ' me-1"></i>' . esc($label) . '</a></li>';
}
?>

<ul class="nav nav-pills mb-4 flex-wrap gap-1">
    <?php
    tab_link('general', 'fa-solid fa-globe', 'General', $activeTab);
    tab_link('contact', 'fa-solid fa-address-book', 'Contact Info', $activeTab);
    tab_link('homepage', 'fa-solid fa-house', 'Homepage', $activeTab);
    tab_link('colors', 'fa-solid fa-palette', 'Colors', $activeTab);
    tab_link('integrations', 'fa-solid fa-plug', 'SMS & Maps', $activeTab);
    tab_link('account', 'fa-solid fa-user-shield', 'Account', $activeTab);
    ?>
</ul>

<?php if ($activeTab === 'general'): ?>
<div class="admin-card">
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="general">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Site Name</label>
                <input class="form-control" type="text" name="site_name" value="<?= esc(setting('site_name')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Tagline</label>
                <input class="form-control" type="text" name="tagline" value="<?= esc(setting('tagline')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Meta Description <span class="text-muted fw-normal">(search engines)</span></label>
                <textarea class="form-control" name="meta_description" rows="2"><?= esc(setting('meta_description')) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Footer About Text</label>
                <textarea class="form-control" name="footer_about" rows="2"><?= esc(setting('footer_about')) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Navbar / Footer Logo</label>
                <div class="d-flex align-items-center gap-3 mb-2 p-2 rounded" style="background: #10203F;">
                    <img src="<?= esc(base_url(setting('logo_nav'))) ?>" alt="" style="height: 48px;">
                </div>
                <input class="form-control" type="file" name="logo_nav" accept="image/*">
            </div>
            <div class="col-md-6">
                <label class="form-label">Icon / Favicon (square)</label>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <img src="<?= esc(base_url(setting('logo_icon'))) ?>" alt="" style="height: 48px;">
                </div>
                <input class="form-control" type="file" name="logo_icon" accept="image/*">
            </div>
            <div class="col-md-6">
                <label class="form-label">Footer Logo (full logo with text)</label>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <img src="<?= esc(base_url(setting('logo_footer', setting('logo_nav')))) ?>" alt="" style="height: 64px; border-radius: .4rem;">
                </div>
                <input class="form-control" type="file" name="logo_footer" accept="image/*">
            </div>
            <div class="col-md-3">
                <label class="form-label">Designer Credit Name</label>
                <input class="form-control" type="text" name="designer_name" value="<?= esc(setting('designer_name')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Designer Credit Link</label>
                <input class="form-control" type="url" name="designer_url" value="<?= esc(setting('designer_url')) ?>" placeholder="https://…">
            </div>
            <div class="col-12">
                <button class="btn btn-gold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save General Settings</button>
            </div>
        </div>
    </form>
</div>

<?php elseif ($activeTab === 'contact'): ?>
<div class="admin-card">
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="contact">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Phone (as displayed)</label>
                <input class="form-control" type="text" name="phone" value="<?= esc(setting('phone')) ?>">
                <div class="form-text">Shown on the site — e.g. +1 (437) 929-3015</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone (for tap-to-call links)</label>
                <input class="form-control" type="text" name="phone_link" value="<?= esc(setting('phone_link')) ?>">
                <div class="form-text">Digits only with country code — e.g. +14379293015</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" value="<?= esc(setting('email')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Service Area</label>
                <input class="form-control" type="text" name="service_area" value="<?= esc(setting('service_area')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Address</label>
                <input class="form-control" type="text" name="address" value="<?= esc(setting('address')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Hours (weekdays)</label>
                <input class="form-control" type="text" name="hours_weekday" value="<?= esc(setting('hours_weekday')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Hours (weekend)</label>
                <input class="form-control" type="text" name="hours_weekend" value="<?= esc(setting('hours_weekend')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Facebook URL</label>
                <input class="form-control" type="url" name="facebook_url" value="<?= esc(setting('facebook_url')) ?>" placeholder="https://facebook.com/…">
            </div>
            <div class="col-md-4">
                <label class="form-label">Instagram URL</label>
                <input class="form-control" type="url" name="instagram_url" value="<?= esc(setting('instagram_url')) ?>" placeholder="https://instagram.com/…">
            </div>
            <div class="col-md-4">
                <label class="form-label">TikTok URL</label>
                <input class="form-control" type="url" name="tiktok_url" value="<?= esc(setting('tiktok_url')) ?>" placeholder="https://tiktok.com/@…">
            </div>
            <div class="col-12">
                <button class="btn btn-gold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save Contact Info</button>
            </div>
        </div>
    </form>
</div>

<?php elseif ($activeTab === 'homepage'): ?>
<div class="admin-card">
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="homepage">
        <h5 class="mb-3">Hero Section</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Hero Title</label>
                <input class="form-control" type="text" name="hero_title" value="<?= esc(setting('hero_title')) ?>">
                <div class="form-text">Wrap words in <code>&lt;span&gt;…&lt;/span&gt;</code> to color them gold.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Hero Subtitle</label>
                <textarea class="form-control" name="hero_subtitle" rows="2"><?= esc(setting('hero_subtitle')) ?></textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Button 1 Text</label>
                <input class="form-control" type="text" name="hero_btn1_text" value="<?= esc(setting('hero_btn1_text')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Button 1 Link</label>
                <input class="form-control" type="text" name="hero_btn1_url" value="<?= esc(setting('hero_btn1_url')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Button 2 Text</label>
                <input class="form-control" type="text" name="hero_btn2_text" value="<?= esc(setting('hero_btn2_text')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Button 2 Link</label>
                <input class="form-control" type="text" name="hero_btn2_url" value="<?= esc(setting('hero_btn2_url')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Hero Background Image</label>
                <div class="mb-2"><img src="<?= esc(base_url(setting('hero_image'))) ?>" alt="" style="height: 90px; border-radius: .5rem;"></div>
                <input class="form-control" type="file" name="hero_image" accept="image/*">
            </div>
        </div>
        <h5 class="mb-3">Section Titles</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Services Title</label>
                <input class="form-control" type="text" name="home_services_title" value="<?= esc(setting('home_services_title')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Services Subtitle</label>
                <input class="form-control" type="text" name="home_services_subtitle" value="<?= esc(setting('home_services_subtitle')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">About Title</label>
                <input class="form-control" type="text" name="home_about_title" value="<?= esc(setting('home_about_title')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Gallery Title</label>
                <input class="form-control" type="text" name="home_gallery_title" value="<?= esc(setting('home_gallery_title')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Testimonials Title</label>
                <input class="form-control" type="text" name="home_testimonials_title" value="<?= esc(setting('home_testimonials_title')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Bottom Call-to-Action Title</label>
                <input class="form-control" type="text" name="cta_band_title" value="<?= esc(setting('cta_band_title')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Bottom Call-to-Action Subtitle</label>
                <input class="form-control" type="text" name="cta_band_subtitle" value="<?= esc(setting('cta_band_subtitle')) ?>">
            </div>
        </div>
        <h5 class="mb-3">Statistics Counters</h5>
        <div class="row g-3 mb-4">
            <?php for ($n = 1; $n <= 4; $n++): ?>
                <div class="col-md-3 col-6">
                    <label class="form-label">Stat <?= $n ?></label>
                    <input class="form-control mb-1" type="number" name="stat<?= $n ?>_number" value="<?= esc(setting('stat' . $n . '_number')) ?>">
                    <input class="form-control" type="text" name="stat<?= $n ?>_label" value="<?= esc(setting('stat' . $n . '_label')) ?>">
                </div>
            <?php endfor; ?>
        </div>
        <button class="btn btn-gold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save Homepage Settings</button>
    </form>
</div>

<?php elseif ($activeTab === 'colors'): ?>
<div class="admin-card">
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="colors">
        <p class="text-muted small">These colors come from your logo and are used across the whole site. Changing them updates every page instantly.</p>
        <div class="row g-3">
            <?php
            $colorFields = [
                'color_primary' => 'Primary Navy (headings, navbar links)',
                'color_primary_dark' => 'Dark Navy (footer, top bar)',
                'color_accent' => 'Gold (buttons, accents)',
                'color_accent_light' => 'Light Gold (gradients)',
                'color_red' => 'Red (badges, highlights)',
                'color_bg_light' => 'Light Background (sections)',
            ];
            foreach ($colorFields as $key => $label): ?>
                <div class="col-md-6 d-flex align-items-center gap-3">
                    <input class="form-control color-swatch-input" type="color" name="<?= esc($key) ?>" value="<?= esc(setting($key)) ?>">
                    <label class="form-label mb-0"><?= esc($label) ?> <code class="text-muted"><?= esc(setting($key)) ?></code></label>
                </div>
            <?php endforeach; ?>
            <div class="col-12">
                <button class="btn btn-gold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save Colors</button>
            </div>
        </div>
    </form>
</div>

<?php elseif ($activeTab === 'integrations'): ?>
<div class="admin-card">
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="integrations">
        <h5 class="mb-3"><i class="fa-solid fa-comment-sms me-2 text-warning"></i>Twilio SMS Notifications</h5>
        <p class="text-muted small">When a customer submits the Get a Quote form, the details are texted to your phone.</p>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Account SID</label>
                <input class="form-control" type="text" name="twilio_sid" value="<?= esc(setting('twilio_sid')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Auth Token</label>
                <input class="form-control" type="password" name="twilio_token" value="" placeholder="•••••••• (leave blank to keep current)" autocomplete="new-password">
            </div>
            <div class="col-md-6">
                <label class="form-label">Twilio Phone Number (From)</label>
                <input class="form-control" type="text" name="twilio_from" value="<?= esc(setting('twilio_from')) ?>" placeholder="+17792092992">
            </div>
            <div class="col-md-6">
                <label class="form-label">Your Phone Number (To)</label>
                <input class="form-control" type="text" name="twilio_to" value="<?= esc(setting('twilio_to')) ?>" placeholder="+14379293015">
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center gap-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="sms-enabled" name="sms_enabled" value="1"<?= setting('sms_enabled') === '1' ? ' checked' : '' ?>>
                    <label class="form-check-label" for="sms-enabled">Send SMS for new quote requests</label>
                </div>
                <button class="btn btn-outline-secondary btn-sm" type="submit" name="send_test_sms" value="1"><i class="fa-solid fa-paper-plane me-1"></i>Save &amp; Send Test SMS</button>
            </div>
        </div>
        <hr>
        <h5 class="mb-3"><i class="fa-solid fa-map-location-dot me-2 text-warning"></i>Google Maps</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Google Maps API Key</label>
                <input class="form-control" type="text" name="google_maps_api_key" value="<?= esc(setting('google_maps_api_key')) ?>">
                <div class="form-text">Used for the map on the Contact page (Maps Embed API).</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Map Location</label>
                <input class="form-control" type="text" name="map_query" value="<?= esc(setting('map_query')) ?>" placeholder="Toronto, ON, Canada">
            </div>
            <div class="col-12">
                <button class="btn btn-gold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save Integration Settings</button>
            </div>
        </div>
    </form>
</div>

<?php elseif ($activeTab === 'account'): ?>
<div class="row">
    <div class="col-lg-6">
        <div class="admin-card">
            <h5 class="mb-3"><i class="fa-solid fa-user-shield me-2 text-warning"></i>Admin Account</h5>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="account">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input class="form-control" type="text" name="new_username" maxlength="50" value="<?= esc(admin_user()['username']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password <span class="text-muted fw-normal">(leave blank to keep current)</span></label>
                    <input class="form-control" type="password" name="new_password" autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input class="form-control" type="password" name="confirm_password" autocomplete="new-password">
                </div>
                <div class="mb-4">
                    <label class="form-label">Current Password *</label>
                    <input class="form-control" type="password" name="current_password" required autocomplete="current-password">
                    <div class="form-text">Required to confirm any account change.</div>
                </div>
                <button class="btn btn-gold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Update Account</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
