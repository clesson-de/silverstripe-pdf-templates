<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Services;

use Clesson\Silverstripe\PdfTemplates\Constants\PdfPageType;
use Clesson\Silverstripe\PdfTemplates\Models\PdfTemplate;
use Clesson\Silverstripe\PdfTemplates\Models\PdfTemplatePage;
use TCPDF;

/**
 * Generates PDF documents from a PdfTemplate using TCPDF.
 *
 * Usage:
 * ```php
 * $creator = PdfCreator::create('MY_TEMPLATE');
 * $creator->addContent('<h1>Page one</h1><p>Content here.</p>');
 * $creator->addContent('<p>Page two content.</p>');
 * $creator->save('/path/to/output.pdf');
 * ```
 *
 * Content blocks are added via addContent() and accept plain text or HTML.
 * TCPDF renders HTML via writeHTML() — basic tags (h1–h6, p, b, i, ul, table)
 * are supported natively.
 *
 * Page order: COVER (if present, no content consumed) → LEFT → RIGHT → LEFT → RIGHT → …
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Services
 */
class PdfCreator
{

    /**
     * The resolved template model.
     */
    protected PdfTemplate $template;

    /**
     * Content blocks added via addContent().
     *
     * @var string[]
     */
    protected array $contentBlocks = [];

    /**
     * Whether to show margin overlay for debugging.
     */
    protected bool $showMargins = false;

    /**
     * @param PdfTemplate|string $template PdfTemplate instance or Ukey string.
     * @throws \InvalidArgumentException when the template cannot be found.
     */
    public function __construct(PdfTemplate|string $template)
    {
        if (is_string($template)) {
            /** @var PdfTemplate|null $found */
            $found = PdfTemplate::get()->find('Ukey', $template);

            if (!$found) {
                throw new \InvalidArgumentException(
                    'PdfTemplate with Ukey "' . $template . '" not found.'
                );
            }

            $this->template = $found;
        } else {
            $this->template = $template;
        }
    }

    /**
     * Static factory method for a fluent API.
     *
     * Always creates a PdfCreator instance. Subclasses with different
     * constructor signatures should define their own factory methods.
     *
     * @param PdfTemplate|string $template
     * @return self
     */
    public static function create(PdfTemplate|string $template): self
    {
        return new self($template);
    }

    /**
     * Adds a content block (plain text or HTML) to the next inner page.
     *
     * Content blocks are distributed one per page in the order LEFT → RIGHT → LEFT → …
     * The COVER page (if present) is rendered separately and does not consume a block.
     *
     * TCPDF supports basic HTML tags: h1–h6, p, b, i, u, ul, ol, li, table, tr, td, br, hr, img.
     *
     * @param string $content Plain text or HTML content for the page.
     * @return static
     */
    public function addContent(string $content): static
    {
        $this->contentBlocks[] = $content;
        return $this;
    }

    /**
     * Enables or disables the margin overlay for debugging.
     *
     * When enabled, a semi-transparent gray rectangle is drawn to visualize
     * the content area after margins are applied.
     *
     * @param bool $show
     * @return static
     */
    public function setShowMargins(bool $show): static
    {
        $this->showMargins = $show;
        return $this;
    }

    /**
     * Renders all pages using TCPDF and saves the PDF to the given file path.
     *
     * @param string $filePath Absolute path to the output file.
     * @return void
     * @throws \RuntimeException when TCPDF is not available or the output directory is not writable.
     */
    public function save(string $filePath): void
    {
        if (!class_exists(TCPDF::class)) {
            throw new \RuntimeException(
                'tecnickcom/tcpdf is required for PDF generation. Run: composer require tecnickcom/tcpdf'
            );
        }

        $dir = dirname($filePath);
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \RuntimeException('Output directory "' . $dir . '" is not writable.');
        }

        $pageSize = $this->template->dbObject('PageSize');
        $pageW = $pageSize && $pageSize->exists() ? $pageSize->getWidth() : 210.0;
        $pageH = $pageSize && $pageSize->exists() ? $pageSize->getHeight() : 297.0;

        $pdf = new TCPDF('P', 'mm', [$pageW, $pageH], true, 'UTF-8', false);
        $pdf->SetCreator('Silverstripe PDF Templates');
        $pdf->SetAuthor('');
        $pdf->SetTitle($this->template->Name);
        $pdf->SetAutoPageBreak(false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pages      = $this->buildPageSequence();
        $contentIdx = 0;

        foreach ($pages as $page) {
            $pdf->AddPage('P', [$pageW, $pageH]);

            $this->applyBackground($pdf, $page);

            // Draw margin overlay for debugging if enabled
            if ($this->showMargins) {
                $this->drawMarginOverlay($pdf, $page, $pageW, $pageH);
            }

            /** @var \Clesson\Silverstripe\PdfTemplates\ORM\DBRgba $color */
            $color = $page->dbObject('FontColor');
            $r = $color && $color->exists() ? $color->getR() : 0;
            $g = $color && $color->exists() ? $color->getG() : 0;
            $b = $color && $color->exists() ? $color->getB() : 0;
            $pdf->SetTextColor($r, $g, $b);

            if ($page->PageType === PdfPageType::COVER) {
                // COVER page: no content block consumed
                continue;
            }

            if (isset($this->contentBlocks[$contentIdx])) {
                $ml = (float) $page->MarginLeft;
                $mt = (float) $page->MarginTop;
                $mr = (float) $page->MarginRight;
                $mb = (float) $page->MarginBottom;

                $pdf->SetMargins($ml, $mt, $mr);
                $fontSize = (int) $page->FontSize > 0 ? (int) $page->FontSize : 10;
                $pdf->SetFont('helvetica', '', $fontSize);
                $pdf->SetXY($ml, $mt);

                $content = $this->contentBlocks[$contentIdx];

                // Use writeHTML for HTML content, MultiCell for plain text
                if ($this->isHtml($content)) {
                    $pdf->writeHTML($content, true, false, true, false, '');
                } else {
                    $pdf->MultiCell(
                        $pageW - $ml - $mr,
                        0,
                        $content,
                        0,
                        'L',
                        false,
                        1,
                        $ml,
                        $mt
                    );
                }

                $contentIdx++;
            }
        }

        $pdf->Output($filePath, 'F');
    }

    /**
     * Builds the sequence of pages in rendering order:
     * COVER (optional, once) → LEFT, RIGHT, LEFT, RIGHT, … (one per content block).
     *
     * @return PdfTemplatePage[]
     */
    protected function buildPageSequence(): array
    {
        $allPages = $this->template->Pages()->sort('Sort ASC');

        /** @var PdfTemplatePage|null $cover */
        $cover = null;
        /** @var PdfTemplatePage|null $left */
        $left  = null;
        /** @var PdfTemplatePage|null $right */
        $right = null;

        foreach ($allPages as $page) {
            if ($page->PageType === PdfPageType::COVER && !$cover) {
                $cover = $page;
            } elseif ($page->PageType === PdfPageType::LEFT && !$left) {
                $left = $page;
            } elseif ($page->PageType === PdfPageType::RIGHT && !$right) {
                $right = $page;
            }
        }

        $sequence = [];

        if ($cover) {
            $sequence[] = $cover;
        }

        $count = count($this->contentBlocks);
        for ($i = 0; $i < $count; $i++) {
            $sequence[] = ($i % 2 === 0) ? ($left ?? $right) : ($right ?? $left);
        }

        return array_filter($sequence);
    }

    /**
     * Applies the background PDF as the first layer of the current TCPDF page.
     *
     * Reads the background PDF from the Silverstripe Asset Store via
     * getString(), writes it to a temp file and imports it via TCPDF.
     *
     * @param TCPDF           $pdf
     * @param PdfTemplatePage $page
     * @return void
     */
    protected function applyBackground(TCPDF $pdf, PdfTemplatePage $page): void
    {
        $bg = $page->BackgroundPdf();

        if (!$bg || !$bg->exists()) {
            return;
        }

        $content = $bg->getString();

        if (!$content) {
            return;
        }

        $tmpBg = tempnam(sys_get_temp_dir(), 'bg_pdf_');

        if ($tmpBg === false) {
            return;
        }

        file_put_contents($tmpBg, $content);

        try {
            $pageSize = $this->template->dbObject('PageSize');
            $width = $pageSize && $pageSize->exists() ? $pageSize->getWidth() : 210.0;
            $height = $pageSize && $pageSize->exists() ? $pageSize->getHeight() : 297.0;

            $pdf->Image($tmpBg, 0, 0, $width, $height, 'PDF', '', 'N', false, 300, '', false, false, 0);
        } catch (\Exception) {
            // Silently ignore background PDF errors
        } finally {
            @unlink($tmpBg);
        }
    }

    /**
     * Draws a semi-transparent gray rectangle over the content area to visualize margins.
     *
     * @param TCPDF           $pdf
     * @param PdfTemplatePage $page
     * @param float           $pageW
     * @param float           $pageH
     * @return void
     */
    protected function drawMarginOverlay(TCPDF $pdf, PdfTemplatePage $page, float $pageW, float $pageH): void
    {
        $ml = (float) $page->MarginLeft;
        $mt = (float) $page->MarginTop;
        $mr = (float) $page->MarginRight;
        $mb = (float) $page->MarginBottom;

        // Draw red lines at margin boundaries
        $pdf->SetDrawColor(255, 0, 0);
        $pdf->SetLineWidth(0.5);

        // Top margin line (horizontal)
        $pdf->Line(0, $mt, $pageW, $mt);

        // Bottom margin line (horizontal)
        $pdf->Line(0, $pageH - $mb, $pageW, $pageH - $mb);

        // Left margin line (vertical)
        $pdf->Line($ml, 0, $ml, $pageH);

        // Right margin line (vertical)
        $pdf->Line($pageW - $mr, 0, $pageW - $mr, $pageH);

        // Draw semi-transparent gray rectangle in content area
        $contentX = $ml;
        $contentY = $mt;
        $contentW = $pageW - $ml - $mr;
        $contentH = $pageH - $mt - $mb;

        $pdf->SetAlpha(0.2);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Rect($contentX, $contentY, $contentW, $contentH, 'F');

        // Reset
        $pdf->SetAlpha(1);
        $pdf->SetDrawColor(0, 0, 0);
    }

    /**
     * Returns true when the given string appears to contain HTML markup.
     *
     * @param string $content
     * @return bool
     */
    protected function isHtml(string $content): bool
    {
        return $content !== strip_tags($content);
    }

}
