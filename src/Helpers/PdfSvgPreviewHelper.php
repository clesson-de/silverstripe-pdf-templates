<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Helpers;

/**
 * Generates an inline SVG preview for a PDF template page.
 *
 * The SVG is proportional to the configured page dimensions and visualises
 * the four margin lines (dashed) with mm-value labels.
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Helpers
 */
class PdfSvgPreviewHelper
{

    /** Preview width in pixels. Height is calculated proportionally. */
    private const PREVIEW_WIDTH = 200;

    /**
     * Generates an inline SVG string for the given page parameters.
     *
     * @param float  $pageWidthMm   Page width in mm
     * @param float  $pageHeightMm  Page height in mm
     * @param float  $marginTop     Top margin in mm
     * @param float  $marginRight   Right margin in mm
     * @param float  $marginBottom  Bottom margin in mm
     * @param float  $marginLeft    Left margin in mm
     * @param string $pageType      Page type label shown as a badge (optional)
     * @return string Inline SVG markup
     */
    public static function generate(
        float $pageWidthMm,
        float $pageHeightMm,
        float $marginTop,
        float $marginRight,
        float $marginBottom,
        float $marginLeft,
        string $pageType = ''
    ): string {
        if ($pageWidthMm <= 0 || $pageHeightMm <= 0) {
            return '';
        }

        $scale = self::PREVIEW_WIDTH / $pageWidthMm;
        $svgW  = self::PREVIEW_WIDTH;
        $svgH  = round($pageHeightMm * $scale, 2);

        $mTop    = round($marginTop    * $scale, 2);
        $mRight  = round($marginRight  * $scale, 2);
        $mBottom = round($marginBottom * $scale, 2);
        $mLeft   = round($marginLeft   * $scale, 2);

        $contentX   = $mLeft;
        $contentY   = $mTop;
        $contentW   = max(0.0, $svgW - $mLeft - $mRight);
        $contentH   = max(0.0, $svgH - $mTop  - $mBottom);
        $lineBottom = $svgH - $mBottom;
        $lineRight  = $svgW - $mRight;
        $midX       = round($svgW / 2);
        $midY       = round($svgH / 2);

        $dash  = 'stroke:#e74c3c;stroke-width:0.7;stroke-dasharray:3,2;fill:none;';
        $lbl   = 'font-family:sans-serif;font-size:8px;fill:#555;';
        $ns    = 'Clesson\\Silverstripe\\PdfTemplates\\Helpers\\PdfSvgPreviewHelper';

        $lT = htmlspecialchars(_t($ns . '.MARGIN_TOP',    'T') . ': ' . $marginTop    . ' mm');
        $lB = htmlspecialchars(_t($ns . '.MARGIN_BOTTOM', 'B') . ': ' . $marginBottom . ' mm');
        $lL = htmlspecialchars(_t($ns . '.MARGIN_LEFT',   'L') . ': ' . $marginLeft   . ' mm');
        $lR = htmlspecialchars(_t($ns . '.MARGIN_RIGHT',  'R') . ': ' . $marginRight  . ' mm');

        $out  = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $svgW . '" height="' . $svgH . '" style="display:block;border:1px solid #ccc;">';
        $out .= '<rect x="0" y="0" width="' . $svgW . '" height="' . $svgH . '" style="stroke:#333;stroke-width:1;fill:#fff;" />';
        $out .= '<rect x="' . $contentX . '" y="' . $contentY . '" width="' . $contentW . '" height="' . $contentH . '" style="fill:#f0f4ff;stroke:none;" />';
        $out .= '<line x1="0" y1="' . $mTop . '" x2="' . $svgW . '" y2="' . $mTop . '" style="' . $dash . '" />';
        $out .= '<line x1="0" y1="' . $lineBottom . '" x2="' . $svgW . '" y2="' . $lineBottom . '" style="' . $dash . '" />';
        $out .= '<line x1="' . $mLeft . '" y1="0" x2="' . $mLeft . '" y2="' . $svgH . '" style="' . $dash . '" />';
        $out .= '<line x1="' . $lineRight . '" y1="0" x2="' . $lineRight . '" y2="' . $svgH . '" style="' . $dash . '" />';
        $out .= '<text x="' . $midX . '" y="' . max(9, $mTop - 3) . '" text-anchor="middle" style="' . $lbl . '">' . $lT . '</text>';
        $out .= '<text x="' . $midX . '" y="' . min($svgH - 2, $lineBottom + 9) . '" text-anchor="middle" style="' . $lbl . '">' . $lB . '</text>';
        $out .= '<text x="10" y="' . $midY . '" text-anchor="middle" style="' . $lbl . '" transform="rotate(-90,10,' . $midY . ')">' . $lL . '</text>';
        $out .= '<text x="' . ($svgW - 10) . '" y="' . $midY . '" text-anchor="middle" style="' . $lbl . '" transform="rotate(90,' . ($svgW - 10) . ',' . $midY . ')">' . $lR . '</text>';

        if ($pageType) {
            $out .= '<text x="' . ($svgW - 4) . '" y="10" style="font-family:sans-serif;font-size:7px;fill:#888;text-anchor:end;">' . htmlspecialchars($pageType) . '</text>';
        }

        $out .= '</svg>';

        return $out;
    }

}

