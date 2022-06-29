<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Cspray\Blogisthenics\Exception\SiteGenerationException;

/**
 * @internal This class should only be utilized by Engine implementations; use outside of this context is unsupported.
 */
final class SiteWriter {


    public function __construct(
        private readonly TemplateFormatter $templateFormatter,
        private readonly ContextFactory $contextFactory
    ) {}

    public function writeSite(Site $site) : void {
        foreach ($site->getAllPages() as $page) {
            $outputFile = $page->outputPath;
            $this->ensureDirectoryExists(dirname($outputFile));

            $contents = $this->buildTemplateContents($site, $page);
            file_put_contents($outputFile, $contents);
        }

        foreach ($site->getAllStaticAssets() as $staticAsset) {
            $outputFile = $staticAsset->outputPath;
            $this->ensureDirectoryExists(dirname($outputFile));

            $context = $this->contextFactory->create([]);
            $contents = $staticAsset->template->render($this->templateFormatter, $context);
            file_put_contents($outputFile, $contents);
        }
    }

    private function ensureDirectoryExists(string $filePath) : void {
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, true);
        }
    }

    private function buildTemplateContents(Site $site, Content $page) : string {
        $pageTemplatesToRender = $this->getPagesToRender($site, $page);

        $finalLayout = array_pop($pageTemplatesToRender);
        $contents = null;
        foreach ($pageTemplatesToRender as $contentPage) {
            $templateData = $this->mergeAndConvertToArray($page->frontMatter->withData(['content' => $contents]), $contentPage->frontMatter);
            $context = $this->contextFactory->create($templateData);
            $markup = $contentPage->template->render($this->templateFormatter, $context);

            $contents = new SafeToNotEncode($markup . PHP_EOL);
        }

        $templateData = $this->mergeAndConvertToArray($page->frontMatter->withData(['content' => $contents]), $finalLayout->frontMatter);
        $context = $this->contextFactory->create($templateData);
        return $finalLayout->template->render($this->templateFormatter, $context);
    }

    private function mergeAndConvertToArray(FrontMatter $first, FrontMatter $second) : array {
        $firstData = iterator_to_array($first);
        return iterator_to_array($second->withData($firstData));
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