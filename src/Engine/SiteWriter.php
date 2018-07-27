<?php declare(strict_types=1);

namespace Cspray\Jasg\Engine;

use Cspray\Jasg\Exception\SiteGenerationException;
use Cspray\Jasg\FrontMatter;
use Cspray\Jasg\Page;
use Cspray\Jasg\Template\ContextFactory;
use Cspray\Jasg\Template\SafeToNotEncode;
use Cspray\Jasg\Site;
use Amp\Promise;
use function Amp\call;
use function Amp\File\filesystem;

/**
 * @internal This class should only be utilized by Engine implementations; use outside of this context is unsupported.
 */
final class SiteWriter {

    private $contextFactory;

    public function __construct(ContextFactory $contextFactory) {
        $this->contextFactory = $contextFactory;
    }

    public function writeSite(Site $site) : Promise {
        return call(function() use($site) {
            /** @var Page $page */
            foreach ($site->getAllPages() as $page) {
                $outputFile = (string) $page->getFrontMatter()->get('output_path');
                yield $this->ensureDirectoryExists(dirname($outputFile));

                $contents = yield $this->buildTemplateContents($site, $page);
                yield filesystem()->put($outputFile, $contents);
            }

            foreach ($site->getAllStaticAssets() as $staticAsset) {
                $outputFile = (string) $staticAsset->getFrontMatter()->get('output_path');
                yield $this->ensureDirectoryExists(dirname($outputFile));

                $context = $this->contextFactory->create([]);
                $contents = yield $staticAsset->getTemplate()->render($context);
                yield filesystem()->put($outputFile, $contents);
            }
        });
    }

    private function ensureDirectoryExists(string $filePath) : Promise {
        return call(function() use($filePath) {
            $outputDirExists = yield filesystem()->exists($filePath);
            if (!$outputDirExists) {
                yield filesystem()->mkdir($filePath, 0777, true);
            }
        });
    }

    private function buildTemplateContents(Site $site, Page $page) : Promise {
        return call(function() use($site, $page) {
            $pageTemplatesToRender = $this->getPagesToRender($site, $page);
            $pageFrontMatter = $page->getFrontMatter();
            $finalLayout = array_pop($pageTemplatesToRender);
            $contents = null;
            foreach ($pageTemplatesToRender as $contentPage) {
                $templateData = $this->mergeAndConvertToArray($pageFrontMatter->withData(['content' => $contents]), $contentPage->getFrontMatter());
                $context = $this->contextFactory->create($templateData);
                $markup = yield $contentPage->getTemplate()->render($context);

                $contents = new SafeToNotEncode($markup . PHP_EOL);
            }

            $templateData = $this->mergeAndConvertToArray($pageFrontMatter->withData(['content' => $contents]), $finalLayout->getFrontMatter());
            $context = $this->contextFactory->create($templateData);
            return yield $finalLayout->getTemplate()->render($context);
        });
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