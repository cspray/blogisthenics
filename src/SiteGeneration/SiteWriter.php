<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\SiteGeneration;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Observer\ContentWritten;
use Cspray\Blogisthenics\Site;

/**
 * @internal This class should only be utilized by Engine implementations; use outside of this context is unsupported.
 */
#[Service]
final class SiteWriter {

    /**
     * @var ContentWritten[]
     */
    private array $handlers = [];

    public function __construct(
        private readonly ContentRenderer $contentRenderer
    ) {}

    public function addHandler(ContentWritten $handler) : void {
        $this->handlers[] = $handler;
    }

    public function writeSite(Site $site) : void {
        $contentsToWrite = [...$site->getAllPages(), ...$site->getAllStaticAssets()];
        foreach ($contentsToWrite as $content) {
            $outputFile = $content->outputPath;
            if (!is_dir($dirPath = dirname($outputFile))) {
                mkdir($dirPath, 0777, true);
            }

            file_put_contents(
                $outputFile,
                $this->contentRenderer->renderForWeb($content)
            );

            foreach ($this->handlers as $handler) {
                $handler->notify($content);
            }
        }
    }


}