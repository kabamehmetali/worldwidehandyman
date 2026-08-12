<?php
/** Shared full-width call to action shown directly above every public footer. */
?>
<section class="cta-band" style="background-image: url('<?= esc(base_url(setting('cta_band_image'))) ?>');">
    <div class="container">
        <div class="row justify-content-lg-end">
            <div class="col-12 col-lg-6 text-center">
                <h2><?= esc(setting('cta_band_title')) ?></h2>
                <p class="mx-auto"><?= esc(setting('cta_band_subtitle')) ?></p>
                <div class="d-flex flex-wrap align-items-center justify-content-center gap-4 mt-4">
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
