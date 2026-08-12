<?php
$pageTitle = null; // header falls back to the tagline for the homepage
require __DIR__ . '/includes/header.php';

$services = db()->query(
    'SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 8'
)->fetchAll();
$testimonials = db()->query(
    'SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
)->fetchAll();

// hero_title may contain a <span> highlight — allow only that tag
$heroTitle = strip_tags(setting('hero_title'), '<span>');
?>

<!-- Hero -->
<header class="hero" style="background-image: url('<?= esc(base_url(setting('hero_image'))) ?>');">
    <div class="container hero-inner">
        <div class="hero-content">
            <h1 class="hero-title"><?= $heroTitle ?></h1>
            <p class="hero-sub"><?= esc(setting('hero_subtitle')) ?></p>
            <div class="d-flex flex-wrap gap-3">
                <a class="btn btn-gold btn-lg" href="<?= esc(base_url(setting('hero_btn1_url', 'quote.php'))) ?>">
                    <i class="fa-solid fa-file-signature me-2"></i><?= esc(setting('hero_btn1_text', 'Get a Free Quote')) ?>
                </a>
                <a class="btn btn-outline-light-ww btn-lg" href="<?= esc(base_url(setting('hero_btn2_url', 'services.php'))) ?>">
                    <?= esc(setting('hero_btn2_text', 'View Our Services')) ?>
                </a>
            </div>
            <div class="hero-chips">
                <span class="hero-chip"><i class="fa-solid fa-circle-check"></i> Reliable &amp; On Time</span>
                <span class="hero-chip"><i class="fa-solid fa-circle-check"></i> Quality Workmanship</span>
                <span class="hero-chip"><i class="fa-solid fa-circle-check"></i> Free Quotes</span>
            </div>
        </div>
    </div>
    <a class="hero-scroll" href="#features" aria-label="Scroll down"><i class="fa-solid fa-chevron-down"></i></a>
</header>

<!-- Feature strip -->
<section class="section section-light" id="features">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6 col-lg-3 reveal">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-clock"></i></div>
                    <h5>On Time, Every Time</h5>
                    <p>I show up when I say I will and respect your schedule — no waiting around all day.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-comments"></i></div>
                    <h5>Clear Communication</h5>
                    <p>Honest recommendations, clear pricing, and updates at every step of the job.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-medal"></i></div>
                    <h5>Quality Workmanship</h5>
                    <p>Every job — big or small — gets careful attention and work that is built to last.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-broom"></i></div>
                    <h5>Tidy &amp; Respectful</h5>
                    <p>Your home is treated with care, and the work area is left clean when I am done.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-screwdriver-wrench"></i> Services</span>
            <h2 class="section-title"><?= esc(setting('home_services_title')) ?></h2>
            <p class="section-sub mx-auto"><?= esc(setting('home_services_subtitle')) ?></p>
        </div>
        <div class="row g-4">
            <?php foreach ($services as $service): ?>
                <div class="col-md-6 col-lg-3 reveal">
                    <div class="service-card">
                        <div class="service-icon"><i class="<?= esc($service['icon']) ?>"></i></div>
                        <h5><?= esc($service['title']) ?></h5>
                        <p><?= esc($service['short_desc']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5 reveal">
            <a class="btn btn-navy" href="<?= esc(base_url('services')) ?>">All Services <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
    </div>
</section>

<!-- About preview -->
<section class="section section-light">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 reveal">
                <div class="about-media">
                    <img src="<?= esc(base_url(setting('home_about_image'))) ?>" alt="Sercan — Worldwide Handyman">
                    <div class="about-badge">
                        <div class="num"><?= esc(setting('stat3_number')) ?>+</div>
                        <div class="lbl">Years of<br>Experience</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 reveal">
                <span class="section-eyebrow"><i class="fa-solid fa-user-check"></i> About Me</span>
                <h2 class="section-title"><?= esc(setting('home_about_title')) ?></h2>
                <p>Hi, I'm <strong>Sercan</strong> — a professional handyman proudly serving homeowners and businesses throughout the Greater Toronto Area. I make repairs and improvements simple and stress-free by showing up on time, communicating clearly, and completing every project with care.</p>
                <ul class="check-list">
                    <li><i class="fa-solid fa-circle-check"></i> One trusted professional for repairs, installations, assembly &amp; maintenance</li>
                    <li><i class="fa-solid fa-circle-check"></i> Honest recommendations and pricing agreed before work begins</li>
                    <li><i class="fa-solid fa-circle-check"></i> Careful attention to detail and respect for your property</li>
                </ul>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-navy" href="<?= esc(base_url('about')) ?>">More About Me</a>
                    <a class="btn btn-gold" href="<?= esc(base_url('quote')) ?>">Get a Free Quote</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-list-check"></i> Simple Process</span>
            <h2 class="section-title">How It Works</h2>
        </div>
        <div class="row g-4 steps-row">
            <div class="col-md-4 reveal">
                <div class="step-card">
                    <div class="step-num">1</div>
                    <h5>Tell Me What You Need</h5>
                    <p>Request a quote online or call. Describe the job — photos help me quote faster and more accurately.</p>
                </div>
            </div>
            <div class="col-md-4 reveal">
                <div class="step-card">
                    <div class="step-num">2</div>
                    <h5>Get a Clear Quote</h5>
                    <p>You get a free, no-obligation quote and a scheduled time that works for you — no surprises.</p>
                </div>
            </div>
            <div class="col-md-4 reveal">
                <div class="step-card">
                    <div class="step-num">3</div>
                    <h5>We Fix. You Relax.</h5>
                    <p>The job gets done properly, the work area is left clean, and your to-do list gets shorter.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="stats-band">
    <div class="container">
        <div class="row g-4">
            <div class="col-6 col-lg-3 stat-item reveal">
                <div class="stat-number" data-count="<?= (int) setting('stat1_number') ?>" data-suffix="+">0</div>
                <div class="stat-label"><?= esc(setting('stat1_label')) ?></div>
            </div>
            <div class="col-6 col-lg-3 stat-item reveal">
                <div class="stat-number" data-count="<?= (int) setting('stat2_number') ?>" data-suffix="+">0</div>
                <div class="stat-label"><?= esc(setting('stat2_label')) ?></div>
            </div>
            <div class="col-6 col-lg-3 stat-item reveal">
                <div class="stat-number" data-count="<?= (int) setting('stat3_number') ?>" data-suffix="+">0</div>
                <div class="stat-label"><?= esc(setting('stat3_label')) ?></div>
            </div>
            <div class="col-6 col-lg-3 stat-item reveal">
                <div class="stat-number" data-count="<?= (int) setting('stat4_number') ?>" data-suffix="+">0</div>
                <div class="stat-label"><?= esc(setting('stat4_label')) ?></div>
            </div>
        </div>
    </div>
</section>

<?php if ($testimonials): ?>
<!-- Testimonials -->
<section class="section section-light">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-star"></i> Reviews</span>
            <h2 class="section-title"><?= esc(setting('home_testimonials_title')) ?></h2>
        </div>
        <div class="row g-4">
            <?php foreach (array_slice($testimonials, 0, 4) as $t): ?>
                <div class="col-md-6 col-lg-3 reveal">
                    <div class="testimonial-card">
                        <i class="fa-solid fa-quote-right quote-mark"></i>
                        <div class="testimonial-stars">
                            <?php for ($i = 0; $i < min(5, max(0, (int) $t['rating'])); $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                        </div>
                        <p class="testimonial-text">&ldquo;<?= esc($t['quote_text']) ?>&rdquo;</p>
                        <div class="d-flex align-items-center gap-3 mt-3">
                            <div class="testimonial-avatar"><?= esc(mb_strtoupper(mb_substr($t['author'], 0, 1))) ?></div>
                            <div>
                                <p class="testimonial-author"><?= esc($t['author']) ?></p>
                                <span class="testimonial-location"><?= esc($t['location']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA band -->
<section class="cta-band" style="background-image: url('<?= esc(base_url(setting('cta_band_image'))) ?>');">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h2><?= esc(setting('cta_band_title')) ?></h2>
                <p><?= esc(setting('cta_band_subtitle')) ?></p>
            </div>
            <div class="col-lg-5">
                <div class="d-flex flex-wrap align-items-center gap-4 justify-content-lg-end">
                    <a class="cta-phone" href="<?= esc(phone_link_href()) ?>">
                        <span class="icon-circle"><i class="fa-solid fa-phone"></i></span>
                        <?= esc(setting('phone')) ?>
                    </a>
                    <a class="btn btn-gold btn-lg" href="<?= esc(base_url('quote')) ?>">Get a Free Quote</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
