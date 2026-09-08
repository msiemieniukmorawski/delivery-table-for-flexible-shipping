# wordpress.org assets

These files belong in the **`assets/` folder of the plugin's SVN repository**, not in the
distributed ZIP; `.distignore` keeps this directory out of it.

| File | Purpose | Required size |
| --- | --- | --- |
| `screenshot-1.png` … | Screenshots, numbered to match the `== Screenshots ==` list in `readme.txt` | any, 2x welcome |
| `banner-772x250.png` | Plugin page banner | 772 × 250 |
| `banner-1544x500.png` | Retina banner | 1544 × 500 |
| `icon.svg` | Plugin icon, **preferred**: wordpress.org uses it ahead of the PNGs | square viewBox |
| `icon-128x128.png` | Plugin icon, and the fallback the SVG still requires | 128 × 128 |
| `icon-256x256.png` | Retina icon | 256 × 256 |

Screenshots are captured against a demo shop configured in English with neutral amounts, never
against a real client's store. See the plugin's own conventions on documentation examples.
