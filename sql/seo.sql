-- Worldwide Handyman — SEO landing page tables + settings
--
-- Additive migration: safe to run on a live install. It does NOT touch the
-- content tables (services, gallery, testimonials, faqs, quotes, messages).
--
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root --host=127.0.0.1 < sql/seo.sql
--
-- Then import the generated page content:
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root --host=127.0.0.1 < sql/seo-seed.sql

USE worldwidehandyman_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS seo_service_locations;
DROP TABLE IF EXISTS seo_locations;
DROP TABLE IF EXISTS seo_services;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------- seo_locations
-- One row per place we want to rank for: "handyman north york", etc.
-- Served at /handyman/{slug}
CREATE TABLE seo_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    region VARCHAR(80) NOT NULL DEFAULT '',
    tier TINYINT NOT NULL DEFAULT 2,               -- 1 = major city (gets service x city pages)
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    postal_prefixes VARCHAR(160) NOT NULL DEFAULT '',
    neighbourhoods TEXT NOT NULL,                  -- one per line
    landmarks TEXT NOT NULL,                       -- one per line
    nearby VARCHAR(500) NOT NULL DEFAULT '',       -- comma-separated location slugs
    meta_title VARCHAR(200) NOT NULL DEFAULT '',
    meta_description VARCHAR(255) NOT NULL DEFAULT '',
    h1 VARCHAR(200) NOT NULL DEFAULT '',
    intro TEXT NOT NULL,                           -- plain text, blank line = new paragraph
    body_html MEDIUMTEXT NOT NULL,                 -- h2/h3/p/ul/li/strong only
    local_notes TEXT NOT NULL,                     -- one plain-text paragraph
    common_jobs TEXT NOT NULL,                     -- one per line
    faqs_json MEDIUMTEXT NOT NULL,                 -- [{"q":"...","a":"..."}]
    hero_image VARCHAR(255) NOT NULL DEFAULT '',
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tier (tier, sort_order),
    INDEX idx_published (is_published)
) ENGINE=InnoDB;

-- -------------------------------------------------------- seo_services
-- One row per service keyword we want to rank for: "tv mounting", etc.
-- Served at /services/{slug}
CREATE TABLE seo_services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    h1 VARCHAR(200) NOT NULL DEFAULT '',
    icon VARCHAR(80) NOT NULL DEFAULT 'fa-solid fa-screwdriver-wrench',
    keywords VARCHAR(600) NOT NULL DEFAULT '',     -- comma-separated search phrases
    is_pillar TINYINT(1) NOT NULL DEFAULT 0,       -- 1 = gets service x city pages
    meta_title VARCHAR(200) NOT NULL DEFAULT '',
    meta_description VARCHAR(255) NOT NULL DEFAULT '',
    intro TEXT NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    jobs TEXT NOT NULL,                            -- one per line
    process_json MEDIUMTEXT NOT NULL,              -- [{"title":"...","text":"..."}]
    pricing_notes TEXT NOT NULL,
    faqs_json MEDIUMTEXT NOT NULL,                 -- [{"q":"...","a":"..."}]
    related VARCHAR(500) NOT NULL DEFAULT '',      -- comma-separated service slugs
    hero_image VARCHAR(255) NOT NULL DEFAULT '',
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pillar (is_pillar, sort_order),
    INDEX idx_published (is_published)
) ENGINE=InnoDB;

-- ----------------------------------------------- seo_service_locations
-- The service x city pages, e.g. /services/tv-mounting/north-york
--
-- A page exists ONLY when a row exists here. Nothing is auto-generated from
-- the cross product, so there is no way to accidentally publish hundreds of
-- thin near-duplicate pages — every combination is written by hand.
CREATE TABLE seo_service_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NOT NULL,
    h1 VARCHAR(200) NOT NULL DEFAULT '',
    meta_title VARCHAR(200) NOT NULL DEFAULT '',
    meta_description VARCHAR(255) NOT NULL DEFAULT '',
    intro TEXT NOT NULL,                           -- unique to this pair
    local_angle TEXT NOT NULL,                     -- unique to this pair
    common_jobs TEXT NOT NULL,                     -- one per line
    faq_q VARCHAR(300) NOT NULL DEFAULT '',
    faq_a TEXT NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pair (service_id, location_id),
    CONSTRAINT fk_sl_service  FOREIGN KEY (service_id)  REFERENCES seo_services(id)  ON DELETE CASCADE,
    CONSTRAINT fk_sl_location FOREIGN KEY (location_id) REFERENCES seo_locations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------- settings
-- INSERT IGNORE so re-running never clobbers values already set in the admin.
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
-- canonical / indexing
('site_url', ''),                                  -- e.g. https://worldwidehandyman.ca  (REQUIRED before launch)
('seo_og_image', 'assets/img/hero-home.jpg'),
('seo_home_title', 'Handyman in Toronto & the GTA'),
('seo_home_description', 'Reliable handyman services across Toronto and the GTA — repairs, TV mounting, furniture assembly, drywall, painting, plumbing fixtures and more. Free quotes.'),
('seo_robots_extra', ''),
('seo_google_verification', ''),
('seo_bing_verification', ''),
('ga4_id', ''),
('gtm_id', ''),
-- business schema
('seo_business_type', 'HomeAndConstructionBusiness'),
('seo_owner_name', 'Sercan'),
('seo_price_range', '$$'),
('seo_payment_accepted', 'Cash, Debit, Credit Card, e-Transfer'),
('seo_street_address', ''),
('seo_locality', 'Toronto'),
('seo_region', 'ON'),
('seo_postal_code', ''),
('seo_country', 'CA'),
('seo_geo_lat', '43.6532'),
('seo_geo_lng', '-79.3832'),
('seo_geo_radius_km', '60'),
('seo_founding_year', ''),
('seo_sameas', ''),
('seo_aggregate_rating', '0'),                     -- OFF by default: only enable with real, verifiable reviews
('seo_open_days', 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'),
('seo_open_time', '08:00'),
('seo_close_time', '20:00'),
-- service areas hub page
('seo_areas_title', 'Handyman Service Areas Across the GTA'),
('seo_areas_description', 'Worldwide Handyman serves Toronto, Mississauga, Brampton, Vaughan, Markham, Oakville, Pickering and communities right across the Greater Toronto Area.');

-- The Service Areas page is now a real, generated directory at /service-areas
-- instead of a hand-written entry in the `pages` table.
UPDATE nav_links SET url = 'service-areas' WHERE url IN ('page?slug=service-areas', 'page.php?slug=service-areas');
DELETE FROM pages WHERE slug = 'service-areas';

-- Keep navigation on each custom page's one canonical URL.
UPDATE nav_links
SET url = CONCAT('pages/', SUBSTRING_INDEX(url, 'slug=', -1))
WHERE url LIKE 'page?slug=%' OR url LIKE 'page.php?slug=%';
