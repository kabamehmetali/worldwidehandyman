#!/usr/bin/env python3
"""
Worldwide Handyman — business card build.

Reads card.html (the master) and produces everything a printer or a preview
needs.  Run it from this folder:

    /opt/anaconda3/bin/python3 build.py

Outputs (into dist/):
    worldwide-handyman-card-PRINT.pdf   2 pages, 3.75x2.25in, bleed, no marks
    worldwide-handyman-card-PROOF.pdf   same + trim/safe guides — review only
    front-bleed.png / back-bleed.png    600 dpi, bleed included
    front-trim.png  / back-trim.png     600 dpi, cropped to the 3.5x2in trim
"""

import base64, json, pathlib, shutil, subprocess, sys
from PIL import Image

HERE   = pathlib.Path(__file__).resolve().parent
ASSETS = HERE / "assets"
DIST   = HERE / "dist"
TMP    = HERE / ".build"
SRCIMG = HERE.parent.parent / "assets" / "img"
CHROME = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"

QR_URL = "https://worldwidehandyman.ca/quote"
LOGOS  = {"badge": "logo-icon.png", "wordmark": "logo-nav.png"}

DPI        = 600
PAGE_W_IN, PAGE_H_IN = 3.75, 2.25      # trim + 0.125in bleed all round
BLEED_IN   = 0.125
CSS_W, CSS_H = int(PAGE_W_IN * 96), int(PAGE_H_IN * 96)   # 360 x 216
SCALE      = DPI / 96                                      # 6.25

SHOT_CSS = """
<style>
body.shot{margin:0;padding:0;background:#fff;display:block;gap:0}
body.shot .sheet{display:block}
body.shot .slot{display:block}
body.shot .slot__label,body.shot .hint{display:none}
body.shot .card{box-shadow:none;border-radius:0}
body.shot-front .slot:nth-of-type(2){display:none}
body.shot-back  .slot:nth-of-type(1){display:none}
</style>
"""


def make_assets():
    """Re-cut the logos, redraw the QR, and rebuild the base64 bundle."""
    import qrcode

    ASSETS.mkdir(exist_ok=True)

    qr = qrcode.QRCode(error_correction=qrcode.constants.ERROR_CORRECT_M, box_size=40, border=4)
    qr.add_data(QR_URL)
    qr.make(fit=True)
    qr.make_image(fill_color="#10203F", back_color="white").convert("RGB").save(ASSETS / "qr-quote.png")
    print(f"  qr    {QR_URL}  (version {qr.version}, {qr.modules_count} modules)")

    for name, src in LOGOS.items():
        im = Image.open(SRCIMG / src).convert("RGBA")
        im = im.crop(im.getchannel("A").getbbox())      # drop the transparent margin
        im.save(ASSETS / f"{name}.png")
        print(f"  logo  {name}.png  {im.size[0]} x {im.size[1]}  (from {src})")

    uris = {}
    for f in ["badge.png", "wordmark.png", "qr-quote.png"]:
        uris[f[:-4]] = "data:image/png;base64," + base64.b64encode((ASSETS / f).read_bytes()).decode()
    (ASSETS / "uris.json").write_text(json.dumps(uris))


def inline_assets(html: str) -> str:
    """Swap every assets/*.png reference for a base64 data URI."""
    uris = json.loads((ASSETS / "uris.json").read_text())
    for name, uri in uris.items():
        html = html.replace(f'src="assets/{name}.png"', f'src="{uri}"')
    # anything left behind means a rename slipped through
    if 'src="assets/' in html:
        sys.exit("!! un-inlined asset reference still in the HTML")
    return html


def variant(master: str, body_class: str) -> str:
    html = inline_assets(master).replace('<body class="preview">', f'<body class="{body_class}">')
    return html.replace("</head>", SHOT_CSS + "</head>")


def chrome(*args):
    subprocess.run(
        [CHROME, "--headless=new", "--disable-gpu", "--no-sandbox",
         "--hide-scrollbars", "--disable-lcd-text", "--default-background-color=00000000", *args],
        check=True, capture_output=True,
    )


def main():
    master = (HERE / "card.html").read_text()
    TMP.mkdir(exist_ok=True)
    DIST.mkdir(exist_ok=True)

    # --assets forces a re-cut; otherwise only build them if they're missing
    if "--assets" in sys.argv or not (ASSETS / "uris.json").exists():
        make_assets()

    pages = {
        "print": ("print",              DIST / "worldwide-handyman-card-PRINT.pdf"),
        "proof": ("print guides",       DIST / "worldwide-handyman-card-PROOF.pdf"),
    }
    shots = {
        "front": "shot shot-front",
        "back":  "shot shot-back",
    }

    for name, (cls, out) in pages.items():
        src = TMP / f"{name}.html"
        src.write_text(variant(master, cls))
        chrome("--no-pdf-header-footer", f"--print-to-pdf={out}", src.as_uri())
        print(f"  pdf   {out.name}  ({out.stat().st_size // 1024} KB)")

    for name, cls in shots.items():
        src = TMP / f"{name}.html"
        src.write_text(variant(master, cls))
        bleed = DIST / f"{name}-bleed.png"
        chrome(f"--screenshot={bleed}",
               f"--window-size={CSS_W},{CSS_H}",
               f"--force-device-scale-factor={SCALE}",
               src.as_uri())
        im = Image.open(bleed)
        b  = round(BLEED_IN * DPI)
        im.crop((b, b, im.width - b, im.height - b)).save(DIST / f"{name}-trim.png")
        print(f"  png   {name}: bleed {im.size}  trim {(im.width-2*b, im.height-2*b)}")

    shutil.rmtree(TMP)
    print(f"\nDone → {DIST}")


if __name__ == "__main__":
    main()
