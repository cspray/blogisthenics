<?php declare(strict_types=1);


namespace Cspray\Blogisthenics\SiteGeneration;

use Cspray\Blogisthenics\Exception\InvalidStateException;
use Cspray\Blogisthenics\Template\FrontMatter;
use Cspray\Blogisthenics\Template\Template;
use DateTimeImmutable;
use Psr\Http\Message\UriInterface;

final class Content {

    public function __construct(
        public readonly string $name,
        public readonly DateTimeImmutable $postDate,
        public readonly FrontMatter $frontMatter,
        public readonly Template $template,
        public readonly ContentCategory $category,
        public readonly ?string $outputPath,
        public readonly ?UriInterface $url
    ) {}

    public function isPublished() : bool {
        return $this->frontMatter->get('published') ?? true;
    }

    public function isDraft() : bool {
        return !$this->isPublished();
    }

    public function getRenderedContents() : string {
        if (is_null($this->outputPath) || !file_exists($this->outputPath)) {
            throw new InvalidStateException(sprintf(
                'Called %s before the corresponding Template has been rendered.',
                __METHOD__
            ));
        }

        return file_get_contents($this->outputPath);
    }

}