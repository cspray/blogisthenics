<?php declare(strict_types=1);

namespace Cspray\Jasg\Engine;

use Cspray\Jasg\Exception\SiteGenerationException;
use Cspray\Jasg\FrontMatter;
use Cspray\Jasg\Page;
use Cspray\Jasg\Template\Renderer;
use Cspray\Jasg\Template\SafeToNotEncode;
use Cspray\Jasg\Site;
use Amp\Promise;
use function Amp\call;
use function Amp\File\filesystem;

/**
 * @internal This class should only be utilized by Engine implementations; use outside of this context is unsupported.
 */
final class SiteWriter {

    private $renderer;

    public function __construct(Renderer $renderer) {
        $this->renderer = $renderer;
    }

    public function writeSite(Site $site) : Promise {
        return call(function() use($site) {
            /** @var Page $page */
            foreach ($site->getAllPages() as $page) {
                $frontMatter = $page->getFrontMatter();
                $outputFile = (string) $frontMatter->get('output_path');
                $outputDir = dirname($outputFile);
                $outputDirExists = yield filesystem()->exists($outputDir);
                if (!$outputDirExists) {
                    yield filesystem()->mkdir($outputDir, 0777, true);
                }

                $contents = $this->buildTemplateContents($site, $page);
                yield filesystem()->put($outputFile, $contents);
            }
        });
    }

    private function buildTemplateContents(Site $site, Page $page) : string {
        $pageTemplatesToRender = $this->getPagesToRender($site, $page);
        $pageFrontMatter = $page->getFrontMatter();
        $finalLayout = array_pop($pageTemplatesToRender);
        $contents = null;
        foreach ($pageTemplatesToRender as $contentPage) {
            $templateData = $this->mergeAndConvertToArray($pageFrontMatter->withData(['content' => $contents]), $contentPage->getFrontMatter());
            $markup = $this->renderer->render($contentPage->getTemplate()->getPath(), $templateData);

            $contents = new SafeToNotEncode($markup . PHP_EOL);
        }

        $templateData = $this->mergeAndConvertToArray($pageFrontMatter->withData(['content' => $contents]), $finalLayout->getFrontMatter());
        return $this->renderer->render($finalLayout->getTemplate()->getPath(), $templateData);
    }

    private function mergeAndConvertToArray(FrontMatter $first, FrontMatter $second) : array {
        $firstData = iterator_to_array($first);
        return iterator_to_array($second->withData($firstData));
    }

    private function getPagesToRender(Site $site, Page $page) : array {
        $pages = [];
        $pages[] = $page;
        $layoutName = $page->getFrontMatter()->get('layout');

        while ($layoutName !== null) {
            $layout = $site->findLayout((string) $layoutName);
            if (is_null($layout)) {
                $msg = 'The page "' . basename($page->getSourcePath() . '" specified a layout "' . $layoutName . '" but the layout is not present.');
                throw new SiteGenerationException($msg);
            }
            $pages[] = $layout;
            $layoutName = $layout->getFrontMatter()->get('layout');
        }

        return $pages;
    }

}