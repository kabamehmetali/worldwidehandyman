<?php
require_once __DIR__ . '/includes/seo.php';

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['website'] ?? '') !== '') {
        flash_set('success', 'Thanks! Your message has been received.');
        redirect('contact');
    }

    if (!csrf_check($_POST['_csrf'] ?? null)) {
        $errors[] = 'Your session expired. Please try submitting the form again.';
    }

    $old['name']    = trim($_POST['name'] ?? '');
    $old['email']   = trim($_POST['email'] ?? '');
    $old['phone']   = trim($_POST['phone'] ?? '');
    $old['subject'] = trim($_POST['subject'] ?? '');
    $old['message'] = trim($_POST['message'] ?? '');

    if ($old['name'] === '' || mb_strlen($old['name']) > 120) {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['message'] === '') {
        $errors[] = 'Please write a message.';
    }
    if (mb_strlen($old['subject']) > 200 || mb_strlen($old['phone']) > 40) {
        $errors[] = 'Please shorten the subject or phone number.';
    }

    if (!$errors) {
        db()->prepare(
            'INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)'
        )->execute([$old['name'], $old['email'], $old['phone'], $old['subject'], $old['message']]);

        flash_set('success', 'Thanks, ' . $old['name'] . '! Your message has been sent — I will get back to you soon.');
        redirect('contact');
    }
}

$pageTitle       = 'Contact Your GTA Handyman';
$metaDescription = 'Contact Worldwide Handyman — serving Toronto and the Greater Toronto Area. Call, text, email or send a message and get a free quote.';
$canonicalPath   = 'contact';
$ogImage         = 'assets/img/work-condo.jpg';
$breadcrumbs     = [['label' => 'Home', 'url' => ''], ['label' => 'Contact']];
$schemas         = [[
    '@type'      => 'ContactPage',
    '@id'        => canonical_url('contact') . '#contactpage',
    'name'       => 'Contact Worldwide Handyman',
    'mainEntity' => ['@id' => site_url() . '#business'],
]];
require __DIR__ . '/includes/header.php';

$mapKey = setting('google_maps_api_key');
$mapQuery = setting('map_query', 'Toronto, ON, Canada');
?>

<section class="page-banner" style="background-image: url('<?= esc(base_url('assets/img/work-condo.jpg')) ?>');">
    <div class="container">
        <h1>Contact Me</h1>
        <?= breadcrumb_html($breadcrumbs) ?>
    </div>
</section>

<section class="section section-light">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-md-6 col-lg-3 reveal">
                <div class="contact-info-card h-100">
                    <div class="ci-icon"><i class="fa-solid fa-phone"></i></div>
                    <div>
                        <h6>Call or Text</h6>
                        <a href="<?= esc(phone_link_href()) ?>"><?= esc(setting('phone')) ?></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="contact-info-card h-100">
                    <div class="ci-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <h6>Email</h6>
                        <a href="mailto:<?= esc(setting('email')) ?>"><?= esc(setting('email')) ?></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="contact-info-card h-100">
                    <div class="ci-icon"><i class="fa-solid fa-earth-americas"></i></div>
                    <div>
                        <h6>Service Area</h6>
                        <p><?= esc(setting('service_area')) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="contact-info-card h-100">
                    <div class="ci-icon"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        <h6>Hours</h6>
                        <p><?= esc(setting('hours_weekday')) ?><br><?= esc(setting('hours_weekend')) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 align-items-start">
            <div class="col-lg-7 reveal">
                <div class="ww-form-card">
                    <span class="section-eyebrow"><i class="fa-solid fa-envelope-open-text"></i> Send a Message</span>
                    <h2 class="section-title mb-2">How Can I Help?</h2>
                    <p class="mb-4">Questions, feedback or anything else — drop me a line below. For job pricing, the <a href="<?= esc(base_url('quote')) ?>">quote form</a> is the fastest route.</p>

                    <?= flash_render() ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger"><?= esc($error) ?></div>
                    <?php endforeach; ?>

                    <form method="post" action="<?= esc(base_url('contact')) ?>" class="needs-validation" novalidate>
                        <?= csrf_field() ?>
                        <div class="hp-field" aria-hidden="true">
                            <label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="c-name">Your Name *</label>
                                <input class="form-control" id="c-name" type="text" name="name" maxlength="120" required value="<?= esc($old['name']) ?>">
                                <div class="invalid-feedback">Please enter your name.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="c-email">Email *</label>
                                <input class="form-control" id="c-email" type="email" name="email" maxlength="150" required value="<?= esc($old['email']) ?>">
                                <div class="invalid-feedback">Please enter a valid email.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="c-phone">Phone <span class="text-muted fw-normal">(optional)</span></label>
                                <input class="form-control" id="c-phone" type="tel" name="phone" maxlength="40" value="<?= esc($old['phone']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="c-subject">Subject</label>
                                <input class="form-control" id="c-subject" type="text" name="subject" maxlength="200" value="<?= esc($old['subject']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="c-message">Message *</label>
                                <textarea class="form-control" id="c-message" name="message" rows="5" required><?= esc($old['message']) ?></textarea>
                                <div class="invalid-feedback">Please write a message.</div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-gold btn-lg" type="submit"><i class="fa-solid fa-paper-plane me-2"></i>Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-5 reveal">
                <div class="quote-side-card">
                    <h4 class="mb-3">Prefer a Quote?</h4>
                    <p>If you already know what needs doing, skip straight to the quote form — it asks exactly the right questions and I can usually price the job from your first message.</p>
                    <a class="btn btn-gold mt-2" href="<?= esc(base_url('quote')) ?>"><i class="fa-solid fa-file-signature me-2"></i>Get a Free Quote</a>
                    <img src="<?= esc(base_url('assets/img/work-kitchen-1.jpg')) ?>" alt="Handyman at work" class="mt-4">
                </div>
            </div>
        </div>

        <?php if ($mapKey !== ''): ?>
            <div class="map-wrap mt-5 reveal">
                <iframe title="Service area map" loading="lazy" allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                        src="https://www.google.com/maps/embed/v1/place?key=<?= rawurlencode($mapKey) ?>&amp;q=<?= rawurlencode($mapQuery) ?>&amp;zoom=9"></iframe>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
