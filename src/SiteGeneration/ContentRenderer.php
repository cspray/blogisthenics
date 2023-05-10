<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\SiteGeneration;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Exception\SiteGenerationException;
use Cspray\Blogisthenics\Site;
use Cspray\Blogisthenics\Template\ContextFactory;
use Cspray\Blogisthenics\Template\FrontMatter;
use Cspray\Blogisthenics\Template\TemplateFormatter;

#[Service]
class ContentRenderer {

    public function __construct(
        private readonly TemplateFormatter $templateFormatter,
        private readonly ContextFactory $contextFactory,
        private readonly Site $site
    ) {}

    public function renderForWeb(Content $content) : string {
        $pageTemplatesToRender = $this->getPagesToRender($this->site, $content);
        return $this->buildTemplateContents($content, $pageTemplatesToRender);
    }

    public function renderForAtomFeed(Content $content) : string {
        $pageTemplatesToRender = $this->getPagesToRender($this->site, $content);
        array_pop($pageTemplatesToRender); // we wanna pop the last one off to not have a whole HTML document
        return $this->buildTemplateContents($content, $pageTemplatesToRender);
    }

    private function buildTemplateContents(Content $page, array $pageTemplatesToRender) : string {
        $finalLayout = array_pop($pageTemplatesToRender);
        $contents = null;
        foreach ($pageTemplatesToRender as $contentPage) {
            $templateData = $this->mergeAndConvertToArray($page->frontMatter, $contentPage->frontMatter);
            $yield = is_null($contents) ? null : fn() => $contents;
            $context = $this->contextFactory->create($templateData, $yield);
            $markup = $contentPage->template->render($context);

            $contents = $this->templateFormatter->format($contentPage->template->getFormatType(), $markup) . PHP_EOL;
        }

        $templateData = $this->mergeAndConvertToArray($page->frontMatter, $finalLayout->frontMatter);
        $context = $this->contextFactory->create($templateData, fn() => $contents);
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
