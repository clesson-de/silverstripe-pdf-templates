# silverstripe-pdf-templates

PDF template management for Silverstripe CMS 6.1.

## Overview

This module lets editors define reusable PDF page layouts in the SiteConfig.
Each template specifies page dimensions (width × height in mm) and a set of
pages with individual margin, background and font-colour settings.

PDFs are generated server-side via **TCPDF** — no external service required.
Content blocks accept plain text or **HTML** (h1–h6, p, b, i, ul, table, …).

### Features

- Manage PDF templates in **Settings → PDF Templates**
- Each template auto-creates a **LEFT** page (hole-punch margin left) and a **RIGHT** page on first save
- Optional **COVER** page (added manually, always rendered first)
- Per-page settings: margins (T/R/B/L in mm), background PDF asset, font colour (RGB)
- SVG preview of each page with margin visualisation in the CMS
- Drag-and-drop page ordering
- `PdfCreator` service class for generating PDFs programmatically

---

## Developer Documentation

### Requirements

- PHP 8.1 or newer
- SilverStripe 6 (CMS 6.1; framework `^6`)
- PHP `iconv` extension

### Installation

```bash
composer require clesson-de/silverstripe-pdf-templates
php vendor/bin/sake dev/build flush=all
```

### Configuration

The module configuration in `_config/` is loaded automatically by SilverStripe.
After installation, run `dev/build` to apply the database changes; no separate
module configuration is required.

### Usage

### Generating a PDF

```php
use Clesson\Silverstripe\PdfTemplates\Services\PdfCreator;

$creator = PdfCreator::create('MY_TEMPLATE_UKEY');
// Plain text or HTML is accepted
$creator->addContent('<h1>Invoice</h1><p>Content for page 1 (LEFT).</p>');
$creator->addContent('<p>Content for page 2 (RIGHT).</p>');
$creator->addContent('<ul><li>Item A</li><li>Item B</li></ul>');
$creator->save('/path/to/output.pdf');
```

You can also pass a `PdfTemplate` model instance directly:

```php
$template = PdfTemplate::get()->find('Ukey', 'MY_TEMPLATE');
$creator  = PdfCreator::create($template);
```

### License

This module is licensed under the BSD 3-Clause License. See [LICENSE](LICENSE)
for the full license text.

### Page ordering

| Position | Page type |
|---|---|
| 1 (optional) | COVER — rendered once, no content block consumed |
| 2, 4, 6, … | LEFT — content blocks 0, 2, 4, … |
| 3, 5, 7, … | RIGHT — content blocks 1, 3, 5, … |

### Template variables

| Constant | Description |
|---|---|
| `PdfPageType::LEFT` | Inner page, hole-punch margin on the left |
| `PdfPageType::RIGHT` | Inner page, hole-punch margin on the right |
| `PdfPageType::COVER` | Optional cover / title page |

### Ukey generation

The `Ukey` field is auto-generated from the template `Name` on first save:

- Converted to ASCII, uppercased, spaces → underscores
- Example: `My Invoice Template` → `MY_INVOICE_TEMPLATE`
- If the key already exists a numeric suffix is appended: `MY_INVOICE_TEMPLATE_2`

### SVG Preview

The `PdfSvgPreviewHelper::generate()` method renders a proportional SVG of any
page. The blue rectangle shows the content area; dashed red lines mark the
margin boundaries with mm labels.

---

## User Guide

### Creating a template

1. Go to **Settings → PDF Templates**
2. Click **Add PDF template**
3. Enter a **Name** (the unique key is auto-generated)
4. Set **Page width** and **Page height** in mm (default: A4 = 210 × 297 mm)
5. Save — two pages (LEFT and RIGHT) are created automatically

### Editing pages

1. Open the template and switch to the **Pages** tab
2. Click the edit button next to a page
3. Adjust margins, font colour or upload a background PDF
4. The SVG preview updates after saving

### Adding a cover page

1. In the **Pages** tab click **Add page**
2. Set **Page type** to **Cover page**
3. Save — the cover is automatically placed as the first page when generating PDFs

### Reordering pages

Drag and drop the rows in the Pages GridField to change the sort order.
