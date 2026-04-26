<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Tasks;

use Clesson\Silverstripe\PdfTemplates\Helpers\PdfPagePreviewHelper;
use Clesson\Silverstripe\PdfTemplates\Models\PdfTemplatePage;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Generates preview PDFs for all PdfTemplatePage records.
 *
 * URL: /dev/tasks/PdfTemplatePreviewTask
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Tasks
 */
class PdfTemplatePreviewTask extends BuildTask
{
    /**
     * @inheritdoc
     */
    private static string $segment = 'PdfTemplatePreviewTask';

    /**
     * @inheritdoc
     */
    protected string $title = 'PDF Template: Generate page previews';

    /**
     * @inheritdoc
     */
    protected static string $description = 'Generates or regenerates preview PDFs for all PdfTemplatePage records.';

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $pages = PdfTemplatePage::get();
        $output->writeln('Found ' . $pages->count() . ' page(s).');

        foreach ($pages as $page) {
            $output->writeln('');
            $output->writeln('Processing page ID=' . $page->ID . ' (' . $page->PageType . ')...');

            try {
                $file = PdfPagePreviewHelper::generatePreview($page);

                if (!$file || !$file->exists()) {
                    $output->writeln('  ERROR: generatePreview returned no file.');
                    continue;
                }

                $output->writeln('  File ID=' . $file->ID . ' Filename=' . $file->FileFilename);

                $table = $page->config()->get('table_name');
                DB::prepared_query(
                    'UPDATE "' . $table . '" SET "PreviewFileID" = ? WHERE "ID" = ?',
                    [$file->ID, $page->ID]
                );

                $output->writeln('  PreviewFileID updated to ' . $file->ID);
            } catch (\Throwable $e) {
                $output->writeln('  EXCEPTION: ' . $e->getMessage());
                $output->writeln('  ' . $e->getFile() . ':' . $e->getLine());
            }
        }

        $output->writeln('');
        $output->writeln('Done.');

        return Command::SUCCESS;
    }
}

