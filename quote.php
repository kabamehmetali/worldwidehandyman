<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/twilio.php';

$errors = [];
$old = ['name' => '', 'phone' => '', 'email' => '', 'service' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot — bots fill every field; humans never see this one.
    if (($_POST['website'] ?? '') !== '') {
        flash_set('success', 'Thanks! Your request has been received.');
        redirect('quote');
    }

    if (!csrf_check($_POST['_csrf'] ?? null)) {
        $errors[] = 'Your session expired. Please try submitting the form again.';
    }

    $old['name']    = trim($_POST['name'] ?? '');
    $old['phone']   = trim($_POST['phone'] ?? '');
    $old['email']   = trim($_POST['email'] ?? '');
    $old['service'] = trim($_POST['service'] ?? '');
    $old['message'] = trim($_POST['message'] ?? '');

    if ($old['name'] === '' || mb_strlen($old['name']) > 120) {
        $errors[] = 'Please enter your name.';
    }
    if (strlen(preg_replace('/\D/', '', $old['phone'])) < 7 || mb_strlen($old['phone']) > 40) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address (or leave it empty).';
    }
    if ($old['message'] === '') {
        $errors[] = 'Please describe the job so I can prepare your quote.';
    }
    if (mb_strlen($old['service']) > 150) {
        $errors[] = 'Selected service is invalid.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO quotes (name, phone, email, service, message) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$old['name'], $old['phone'], $old['email'], $old['service'], $old['message']]);
        $quoteId = (int) db()->lastInsertId();

        // Notify the owner by SMS; failure never blocks the customer.
        [$smsOk, $smsInfo] = twilio_notify_quote($old);
        db()->prepare('UPDATE quotes SET sms_sent = ?, sms_error = ? WHERE id = ?')
            ->execute([$smsOk ? 1 : 0, $smsOk ? '' : mb_substr($smsInfo, 0, 500), $quoteId]);

        flash_set('success', 'Thanks, ' . $old['name'] . '! Your quote request has been received — I usually reply within one business day.');
        redirect('quote');
    }
}

$pageTitle = 'Get a Quote';
$metaDescription = 'Request a free, no-obligation handyman quote for your home or business anywhere in the Greater Toronto Area.';
require __DIR__ . '/includes/header.php';

$serviceOptions = db()->query(
    'SELECT title FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
)->fetchAll(PDO::FETCH_COLUMN);
?>

<section class="page-banner" style="background-image: url('<?= esc(base_url('assets/img/work-kitchen-2.jpg')) ?>');">
    <div class="container">
        <h1>Get a Free Quote</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= esc(base_url()) ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Get a Quote</li>
            </ol>
        </nav>
    </div>
</section>

<section class="section section-light">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7 reveal">
                <div class="ww-form-card">
                    <span class="section-eyebrow"><i class="fa-solid fa-file-signature"></i> Free &amp; No-Obligation</span>
                    <h2 class="section-title mb-2">Tell Me About Your Project</h2>
                    <p class="mb-4">Fill in the form and I'll get back to you with a clear quote — usually within one business day.</p>

                    <?= flash_render() ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger"><?= esc($error) ?></div>
                    <?php endforeach; ?>

                    <form method="post" action="<?= esc(base_url('quote')) ?>" class="needs-validation" novalidate>
                        <?= csrf_field() ?>
                        <div class="hp-field" aria-hidden="true">
                            <label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="q-name">Your Name *</label>
                                <input class="form-control" id="q-name" type="text" name="name" maxlength="120" required value="<?= esc($old['name']) ?>">
                                <div class="invalid-feedback">Please enter your name.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="q-phone">Phone Number *</label>
                                <input class="form-control" id="q-phone" type="tel" name="phone" maxlength="40" required value="<?= esc($old['phone']) ?>" placeholder="(647) 000-0000">
                                <div class="invalid-feedback">Please enter your phone number.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="q-email">Email <span class="text-muted fw-normal">(optional)</span></label>
                                <input class="form-control" id="q-email" type="email" name="email" maxlength="150" value="<?= esc($old['email']) ?>">
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="q-service">Service Needed</label>
                                <select class="form-select" id="q-service" name="service">
                                    <option value="">— Select a service —</option>
                                    <?php foreach ($serviceOptions as $option): ?>
                                        <option value="<?= esc($option) ?>"<?= $old['service'] === $option ? ' selected' : '' ?>><?= esc($option) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other / Not sure"<?= $old['service'] === 'Other / Not sure' ? ' selected' : '' ?>>Other / Not sure</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="q-message">Describe the Job *</label>
                                <textarea class="form-control" id="q-message" name="message" rows="5" required
                                          placeholder="What needs fixing or installing? Where in the home? Any details or measurements help me quote faster."><?= esc($old['message']) ?></textarea>
                                <div class="invalid-feedback">Please describe the job.</div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-gold btn-lg" type="submit"><i class="fa-solid fa-paper-plane me-2"></i>Request My Quote</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-5 reveal">
                <div class="quote-side-card mb-4">
                    <h4 class="mb-3">What Happens Next?</h4>
                    <ul class="check-list">
                        <li><i class="fa-solid fa-circle-check"></i> I read your request and reply, usually within one business day</li>
                        <li><i class="fa-solid fa-circle-check"></i> You get a clear quote — fixed price or hourly, agreed up front</li>
                        <li><i class="fa-solid fa-circle-check"></i> We pick a time that works for you</li>
                        <li><i class="fa-solid fa-circle-check"></i> We fix. You relax.</li>
                    </ul>
                    <img src="<?= esc(base_url('assets/img/work-install.jpg')) ?>" alt="Handyman installing a shelf" class="mt-2">
                </div>
                <div class="response-note mb-4">
                    <i class="fa-solid fa-bolt me-1"></i> <strong>Prefer to talk?</strong> Call or text
                    <a href="<?= esc(phone_link_href()) ?>"><strong><?= esc(setting('phone')) ?></strong></a>
                    — <?= esc(setting('hours_weekday')) ?>.
                </div>
                <div class="contact-info-card">
                    <div class="ci-icon"><i class="fa-solid fa-earth-americas"></i></div>
                    <div>
                        <h6>Service Area</h6>
                        <p><?= esc(setting('service_area')) ?> — Toronto, Mississauga, Brampton, Vaughan, Markham &amp; more.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
