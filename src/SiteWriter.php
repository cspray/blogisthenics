<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Exception\SiteGenerationException;

/**
 * @internal This class should only be utilized by Engine implementations; use outside of this context is unsupported.
 */
#[Service]
final class SiteWriter {

    /**
     * @var ContentWrittenHandler[]
     */
    private array $handlers = [];

    public function __construct(
        private readonly TemplateFormatter $templateFormatter,
        private readonly ContextFactory $contextFactory
    ) {}

    public function addHandler(ContentWrittenHandler $handler) : void {
        $this->handlers[] = $handler;
    }

    public function writeSite(Site $site) : void {
        $contentsToWrite = [...$site->getAllPages(), ...$site->getAllStaticAssets()];
        foreach ($contentsToWrite as $content) {
            $outputFile = $content->outputPath;
            if (!is_dir($dirPath = dirname($outputFile))) {
                mkdir($dirPath, 0777, true);
            }

            $contents = $this->buildTemplateContents($site, $content);
            file_put_contents($outputFile, $contents);

            foreach ($this->handlers as $handler) {
                $handler->handle($content);
            }
        }
    }

    private function buildTemplateContents(Site $site, Content $page) : string {
        $pageTemplatesToRender = $this->getPagesToRender($site, $page);

        $finalLayout = array_pop($pageTemplatesToRender);
        $contents = null;
        foreach ($pageTemplatesToRender as $contentPage) {
            $templateData = $this->mergeAndConvertToArray($page->frontMatter, $contentPage->frontMatter);
            $yield = is_null($contents) ? null : fn() => new SafeToNotEncode($contents);
            $context = $this->contextFactory->create($templateData, $yield);
            $markup = $contentPage->template->render($context);

            $contents = $this->templateFormatter->format($contentPage->template->getFormatType(), $markup) . PHP_EOL;
        }

        $templateData = $this->mergeAndConvertToArray($page->frontMatter, $finalLayout->frontMatter);
        $context = $this->contextFactory->create($templateData, fn() => new SafeToNotEncode($contents));
        $markup = $finalLayout->template->render($context);
        return $this->templateFormatter->format($finalLayout->template->getFormatType(), $markup);
    }

    private function mergeAndConvertToArray(FrontMatter $first, FrontMatter $second) : array {
        return iterator_to_array($second->withData(iterator_to_array($first)));
    }

    /**
     * @param Site $site
     * @param Content $page
     * @return Content[]
     */
    private function getPagesToRender(Site $site, Content $page) : array {
        $pages = [];
        $pages[] = $page;
        $layoutName = $page->frontMatter->get('layout');

        while ($layoutName !== null) {
            $layout = $site->findLayout((string) $layoutName);
            if (is_null($layout)) {
                $msg = 'The page "' . $page->name . '" specified a layout "' . $layoutName . '" but the layout is not present.';
                throw new SiteGenerationException($msg);
            }
            $pages[] = $layout;
            $layoutName = $layout->frontMatter->get('layout');
        }

        return $pages;
    }

}