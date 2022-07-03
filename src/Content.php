<?php declare(strict_types=1);


namespace Cspray\Blogisthenics;

use Cspray\Blogisthenics\Test\Unit\ContentTest;
use DateTimeImmutable;

final class Content {

    public function __construct(
        public readonly string $name,
        public readonly DateTimeImmutable $postDate,
        public readonly FrontMatter $frontMatter,
        public readonly Template $template,
        public readonly ?string $outputPath,
        public readonly bool $isLayout = false,
        public readonly bool $isStaticAsset = false
    ) {}

    public function withOutputPath(string $outputPath) : Content {
        return new Content(
            $this->name,
            $this->postDate,
            $this->frontMatter,
            $this->template,
            $outputPath,
            $this->isLayout,
            $this->isStaticAsset
        );
    }

    public function withFrontMatter(FrontMatter $frontMatter) : Content {
        return new Content(
            $this->name,
            $this->postDate,
            $frontMatter,
            $this->template,
            $this->outputPath,
            $this->isLayout,
            $this->isStaticAsset
        );
    }

    public function isPublished() : bool {
        return $this->frontMatter->get('published') ?? true;
    }

    public function isDraft() : bool {
        return !$this->isPublished();
    }

}