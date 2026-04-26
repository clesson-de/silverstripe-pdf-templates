<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Controllers;

use Clesson\Silverstripe\PdfTemplates\Models\PdfTemplate;
use Clesson\Silverstripe\PdfTemplates\Services\PdfCreator;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\Permission;

/**
 * Controller for generating test PDFs for PdfTemplate records.
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Controllers
 */
class PdfTemplateTestController extends Controller
{

    /**
     * @inheritdoc
     */
    private static string $url_segment = 'admin/pdf-templates';

    /**
     * @inheritdoc
     */
    private static array $allowed_actions = [
        'test',
    ];

    /**
     * @inheritdoc
     */
    private static array $url_handlers = [
        'test/$ID' => 'test',
    ];

    /**
     * Generates and downloads a test PDF for the given template ID.
     *
     * URL: /admin/pdf-templates/test/{ID}
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function test(HTTPRequest $request): HTTPResponse
    {
        // Check if user is admin
        if (!Permission::check('ADMIN')) {
            return $this->httpError(403, 'Access denied');
        }

        $id = $request->param('ID');
        if (!$id) {
            return $this->httpError(400, 'Invalid ID');
        }

        /** @var PdfTemplate|null $template */
        $template = PdfTemplate::get()->byID((int) $id);
        if (!$template) {
            return $this->httpError(404, 'Template not found');
        }

        if (!$template->canView()) {
            return $this->httpError(403, 'Access denied');
        }

        return $this->generateTestPdf($template);
    }

    /**
     * Generates a test PDF with lorem ipsum content.
     *
     * @param PdfTemplate $template
     * @return HTTPResponse
     */
    protected function generateTestPdf(PdfTemplate $template): HTTPResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_test_');

        try {
            $creator = PdfCreator::create($template);
            $creator->setShowMargins(true); // Enable margin visualization

            $templateName = $template->Name;

            // Add 3 pages of lorem ipsum content
            $loremPages = [
                '<h1>' . $templateName . '</h1><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>',
                '<h1>' . $templateName . ' — Page 2</h1><p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p><p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.</p>',
                '<h1>' . $templateName . ' — Page 3</h1><p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.</p><p>Similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio.</p>',
            ];

            foreach ($loremPages as $content) {
                $creator->addContent($content);
            }

            $creator->save($tempFile);

            $fileName = preg_replace('/[^a-z0-9\-_]/i', '_', $template->Name) . '_test.pdf';

            $response = HTTPResponse::create();
            $response->addHeader('Content-Type', 'application/pdf');
            $response->addHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
            $response->addHeader('Content-Length', (string) filesize($tempFile));
            $response->setBody(file_get_contents($tempFile));

            unlink($tempFile);

            return $response;

        } catch (\Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            return $this->httpError(500, 'PDF generation failed: ' . $e->getMessage());
        }
    }

}

