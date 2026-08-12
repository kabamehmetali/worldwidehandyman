<?php
require_once __DIR__ . '/includes/seo.php';

$pageTitle       = 'About Sercan, Your GTA Handyman';
$metaDescription = 'Meet Sercan — a professional handyman serving homeowners and businesses across the Greater Toronto Area with reliable, high-quality workmanship.';
$canonicalPath   = 'about';
$ogImage         = 'assets/img/sercan.jpg';
$ogType          = 'profile';
$breadcrumbs     = [['label' => 'Home', 'url' => ''], ['label' => 'About']];
$schemas         = [[
    '@type'      => 'Person',
    '@id'        => canonical_url('about') . '#owner',
    'name'       => setting('seo_owner_name', 'Sercan'),
    'jobTitle'   => 'Handyman, Owner and Operator',
    'image'      => site_url('assets/img/sercan.jpg'),
    'telephone'  => setting('phone_link') !== '' ? setting('phone_link') : setting('phone'),
    'email'      => setting('email'),
    'worksFor'   => ['@id' => site_url() . '#business'],
    'areaServed' => ['@type' => 'AdministrativeArea', 'name' => 'Greater Toronto Area'],
]];
require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-portrait" style="background-image: url('<?= esc(base_url('assets/img/work-portrait.jpg')) ?>');">
    <div class="container">
        <h1>About Me</h1>
        <?= breadcrumb_html($breadcrumbs) ?>
    </div>
</section>

<!-- Meet Sercan -->
<section class="section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 reveal">
                <div class="about-photo-frame">
                    <img src="<?= esc(base_url('assets/img/sercan.jpg')) ?>" alt="Sercan — owner of Worldwide Handyman">
                </div>
            </div>
            <div class="col-lg-7 reveal">
                <span class="section-eyebrow"><i class="fa-solid fa-user-check"></i> Meet Sercan</span>
                <h2 class="section-title">Your Trusted Local Handyman<br>Across the Greater Toronto Area</h2>
                <p>Hi, I'm Sercan, a professional handyman proudly serving homeowners and businesses throughout the Greater Toronto Area. With years of hands-on experience, I provide reliable, high-quality solutions for a wide range of home repairs, maintenance, installations, and improvement projects.</p>
                <p>I believe every job — big or small — deserves careful attention, quality workmanship, and professional service. My goal is to make repairs and improvements simple and stress-free by showing up on time, communicating clearly, and completing every project with care.</p>
                <p>Whether you need something repaired, installed, assembled, maintained, or improved, you can count on me for dependable service and work done right.</p>
                <div class="d-flex align-items-center gap-3 mt-4">
                    <img src="<?= esc(base_url(setting('logo_icon'))) ?>" alt="" height="56">
                    <div class="signature-line">
                        Sercan
                        <small>Owner &amp; Operator, <?= esc(setting('site_name')) ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section class="section section-light">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-compass"></i> What Drives Me</span>
            <h2 class="section-title">Mission &amp; Vision</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-6 reveal">
                <div class="mv-card">
                    <div class="mv-icon"><i class="fa-solid fa-bullseye"></i></div>
                    <h4>Our Mission</h4>
                    <p>To provide reliable, professional, and high-quality handyman services throughout the Greater Toronto Area. We are committed to making home repairs, maintenance, installations, and improvements simple and stress-free through honest communication, dependable service, attention to detail, and workmanship our customers can trust.</p>
                    <a class="read-more collapsed" data-bs-toggle="collapse" href="#missionLong" role="button" aria-expanded="false" aria-controls="missionLong">
                        Read the full mission <i class="fa-solid fa-chevron-down ms-1"></i>
                    </a>
                    <div class="collapse" id="missionLong">
                        <hr>
                        <p>I understand that even a small repair can make a big difference in the comfort, appearance, safety, and functionality of a home or business. That is why I approach every project with the same level of responsibility and professionalism, whether it is a simple installation, furniture assembly, general maintenance, repair work, or a larger home improvement project.</p>
                        <p>My goal is not only to complete the job, but to complete it properly. Good service starts with clear communication, punctuality, honest recommendations, and an understanding of what the customer wants to achieve. I take the time to evaluate each project carefully, explain the available options, and provide solutions that are practical, efficient, and built to last.</p>
                        <p class="mb-0">Through years of hands-on experience, I have learned that trust is earned through consistency, reliability, and quality work. My mission is to become the handyman my customers feel comfortable calling whenever something needs to be repaired, installed, maintained, assembled, or improved.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 reveal">
                <div class="mv-card vision">
                    <div class="mv-icon"><i class="fa-solid fa-eye"></i></div>
                    <h4>Our Vision</h4>
                    <p>To become one of the most trusted handyman service providers in the Greater Toronto Area, known for quality workmanship, reliability, and exceptional customer service. We aim to build long-lasting relationships with our customers by becoming the first call they make whenever their home or property needs professional care.</p>
                    <a class="read-more collapsed" data-bs-toggle="collapse" href="#visionLong" role="button" aria-expanded="false" aria-controls="visionLong">
                        Read the full vision <i class="fa-solid fa-chevron-down ms-1"></i>
                    </a>
                    <div class="collapse" id="visionLong">
                        <hr>
                        <p>I want to create a handyman service that people can confidently recommend to their family, friends, neighbours, and businesses — a reputation based not only on the quality of the finished work, but also on honesty, communication, reliability, professionalism, and the overall customer experience.</p>
                        <p>I envision building a business where customers do not need to search for a new handyman every time something goes wrong. Instead, they can have one dependable professional they already know and trust for a wide variety of repair, maintenance, installation, assembly, and improvement needs.</p>
                        <p class="mb-0">Ultimately, my vision is to build more than a handyman service — a trusted local name throughout the Greater Toronto Area that represents quality workmanship, dependable service, honest relationships, and the confidence that when a customer needs help with their property, they know exactly who to call.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values -->
<section class="section">
    <div class="container">
        <div class="text-center mb-4 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-heart"></i> Core Values</span>
            <h2 class="section-title">The Values Behind Every Job</h2>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-3 reveal">
            <span class="value-pill"><i class="fa-solid fa-handshake"></i> Integrity</span>
            <span class="value-pill"><i class="fa-solid fa-clock"></i> Reliability</span>
            <span class="value-pill"><i class="fa-solid fa-magnifying-glass"></i> Attention to Detail</span>
            <span class="value-pill"><i class="fa-solid fa-house-chimney"></i> Respect for Your Property</span>
            <span class="value-pill"><i class="fa-solid fa-star"></i> Customer Satisfaction</span>
        </div>
        <div class="about-strip mt-5 reveal" style="background-image: url('<?= esc(base_url('assets/img/hero-about.jpg')) ?>');" role="img" aria-label="Sercan in front of the Toronto skyline"></div>
    </div>
</section>

<section class="cta-band" style="background-image: url('<?= esc(base_url(setting('cta_band_image'))) ?>');">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h2>Let's take care of your to-do list</h2>
                <p>One dependable professional for your home or business — from the first conversation to the finished job.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
                <a class="btn btn-gold btn-lg" href="<?= esc(base_url('quote')) ?>"><i class="fa-solid fa-file-signature me-2"></i>Get a Free Quote</a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
