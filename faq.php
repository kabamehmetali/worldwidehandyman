<?php
require_once __DIR__ . '/includes/seo.php';

$faqs = db()->query(
    'SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
)->fetchAll();

$pageTitle       = 'Handyman FAQ — Quotes, Pricing, Areas';
$metaDescription = 'Frequently asked questions about Worldwide Handyman — service areas, quotes, pricing, working hours and more.';
$canonicalPath   = 'faq';
$ogImage         = 'assets/img/work-install.jpg';
$breadcrumbs     = [['label' => 'Home', 'url' => ''], ['label' => 'FAQ']];
$schemas         = [schema_faq(array_map(static fn ($f) => [
    'q' => $f['question'], 'a' => $f['answer'],
], $faqs), canonical_url($canonicalPath))];

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner" style="background-image: url('<?= esc(base_url('assets/img/work-install.jpg')) ?>');">
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <?= breadcrumb_html($breadcrumbs) ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="text-center mb-5 reveal">
                    <span class="section-eyebrow"><i class="fa-solid fa-circle-question"></i> Good To Know</span>
                    <h2 class="section-title">Answers To Common Questions</h2>
                </div>
                <?php if ($faqs): ?>
                    <div class="accordion faq-accordion reveal" id="faqAccordion">
                        <?php foreach ($faqs as $i => $faq): ?>
                            <div class="accordion-item">
                                <h3 class="accordion-header">
                                    <button class="accordion-button<?= $i === 0 ? '' : ' collapsed' ?>" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#faq<?= (int) $faq['id'] ?>"
                                            aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="faq<?= (int) $faq['id'] ?>">
                                        <?= esc($faq['question']) ?>
                                    </button>
                                </h3>
                                <div id="faq<?= (int) $faq['id'] ?>" class="accordion-collapse collapse<?= $i === 0 ? ' show' : '' ?>" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body"><?= nl2br(esc($faq['answer'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">No FAQs yet — have a question? <a href="<?= esc(base_url('contact')) ?>">Contact me</a>.</p>
                <?php endif; ?>

                <div class="text-center mt-5 reveal">
                    <p class="mb-3">Still have a question?</p>
                    <a class="btn btn-navy me-2" href="<?= esc(base_url('contact')) ?>"><i class="fa-solid fa-envelope me-2"></i>Contact Me</a>
                    <a class="btn btn-gold" href="<?= esc(base_url('quote')) ?>"><i class="fa-solid fa-file-signature me-2"></i>Get a Quote</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
