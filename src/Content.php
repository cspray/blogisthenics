<?php declare(strict_types=1);


namespace Cspray\Blogisthenics;

use Cspray\Blogisthenics\Exception\InvalidStateException;
use Cspray\Blogisthenics\Template\FrontMatter;
use Cspray\Blogisthenics\Template\Template;
use DateTimeImmutable;

final class Content {

    public function __construct(
        public readonly string $name,
        public readonly DateTimeImmutable $postDate,
        public readonly FrontMatter $frontMatter,
        public readonly Template $template,
        public readonly ContentCategory $category,
        public readonly ?string $outputPath,
    ) {}

    public function withOutputPath(string $outputPath) : Content {
        return new Content(
            $this->name,
            $this->postDate,
            $this->frontMatter,
            $this->template,
            $this->category,
            $outputPath,
        );
    }

    public function withFrontMatter(FrontMatter $frontMatter) : Content {
        return new Content(
            $this->name,
            $this->postDate,
            $frontMatter,
            $this->template,
            $this->category,
            $this->outputPath,
        );
    }

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