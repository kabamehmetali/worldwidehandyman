# Worldwide Handyman — business card

Standard North American card: **trim 3.5″ × 2″**, landscape, double-sided.
Everything here is generated from one master file, `card.html`.

## What to send the printer

| File | Use |
|---|---|
| `dist/worldwide-handyman-card-PRINT.pdf` | **This is the artwork.** 2 pages (p1 front, p2 back), 3.75″ × 2.25″ — trim size plus 0.125″ bleed on all four edges. No crop marks, which is what online printers want. |
| `dist/front-bleed.png`, `dist/back-bleed.png` | 600 dpi rasters with bleed, if a printer asks for images instead of a PDF. |
| `dist/front-trim.png`, `dist/back-trim.png` | 600 dpi, cropped to the finished 3.5″ × 2″ — for the website, email signature, mockups. |
| `dist/worldwide-handyman-card-PROOF.pdf` | **Do not print this one.** Same artwork with the trim line (pink) and safe margin (cyan) drawn on, so you can check nothing important is near the cut. |

## Geometry

```
┌─────────────────────────────────────────┐  ← bleed edge   3.75 × 2.25 in
│  ┌───────────────────────────────────┐  │  ← trim (cut)   3.50 × 2.00 in
│  │   ┌───────────────────────────┐   │  │  ← safe margin  3.25 × 1.75 in
│  │   │  nothing important        │   │  │
│  │   │  crosses this line        │   │  │
│  │   └───────────────────────────┘   │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
   0.125in bleed        0.125in safe margin
```

Backgrounds that reach an edge (the navy field, the gold contact bar, the navy header band
and the grey service strip on the back) all run the full 0.125″ into the bleed, so a cutting
shift can't reveal a white sliver.

## Colour

The PDF is in sRGB. Most online printers convert it themselves and the result is fine. If
your printer asks for CMYK, these are safe starting builds for **coated** stock — total ink
stays under 300 % in every case:

| Brand colour | Hex | CMYK (coated) |
|---|---|---|
| Navy | `#10203F` | 100 / 85 / 40 / 35 |
| Navy deep | `#0A1428` | 100 / 88 / 45 / 55 |
| Gold | `#F5A800` | 0 / 32 / 100 / 0 |
| Gold light | `#FFC933` | 0 / 22 / 85 / 0 |
| Red (logo only) | `#D2382C` | 0 / 85 / 85 / 5 |

The gold is a bright orange-yellow that CMYK can't hit exactly — it will print very slightly
duller than it looks on screen. That is normal and it still reads as the logo gold. If you
ever want it dead-on, ask for **Pantone 137 C** as a spot colour (costs more).

## Stock and finish

The front is a solid dark navy, which shows fingerprints and scuffs on a gloss finish.

- **16 pt / 350 gsm** minimum — anything thinner feels cheap in the hand.
- **Matte or soft-touch laminate** on both sides. This is the single biggest upgrade you can
  make and it fixes the fingerprint problem on the navy.
- Avoid uncoated stock: the navy will look washed out and the gold will go muddy.
- Optional upgrade that suits this logo well: **spot gloss UV** on the character badge only.

## The QR code

Encodes `https://worldwidehandyman.ca/quote` and is printed at 0.84″. It has been decode-tested
down to a simulated 0.45″, so there is plenty of margin. **It only works once the site is live
at that domain** — until then it will 404.

To point it somewhere else, edit the `url` in the QR block of `assets/` generation (see below)
and rebuild.

## Editing and rebuilding

`card.html` is the master — open it in a browser to see both sides. All dimensions are in
inches and points so what you see is physically what prints.

```bash
cd print/business-card
/opt/anaconda3/bin/python3 build.py              # rebuild everything in dist/
/opt/anaconda3/bin/python3 build.py --assets     # also re-cut the logos and redraw the QR
```

`build.py` takes `card.html`, inlines the images as data URIs, and drives headless Chrome to
produce the two PDFs and the four PNGs.

To change where the QR points, edit `QR_URL` at the top of `build.py` and run it with
`--assets`. To swap a logo, edit `LOGOS` in the same block — it re-crops straight from
`assets/img/` and trims the transparent margin for you.

## Source assets

| File | From |
|---|---|
| `assets/badge.png` | `assets/img/logo-icon.png`, transparent edges trimmed (674 × 834) |
| `assets/wordmark.png` | `assets/img/logo-nav.png`, transparent edges trimmed (582 × 276) |
| `assets/qr-quote.png` | generated, 1480 px, error-correction M |

The wordmark is 582 px used at 1.92″ → **303 dpi**, right at the print minimum. It is fine as
is, but if you ever get a vector (SVG/AI/EPS) version of the logo from whoever drew it, drop it
in and the card becomes resolution-independent. That is the one genuine upgrade left in this file.
