<?php
$pageTitle = 'FAQ';
$metaDescription = 'Frequently asked questions about Worldwide Handyman — service areas, quotes, pricing, working hours and more.';
require __DIR__ . '/includes/header.php';

$faqs = db()->query(
    'SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
)->fetchAll();
?>

<section class="page-banner" style="background-image: url('<?= esc(base_url('assets/img/work-install.jpg')) ?>');">
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= esc(base_url()) ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">FAQ</li>
            </ol>
        </nav>
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
