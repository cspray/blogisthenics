<?php declare(strict_types=1);


namespace Cspray\Blogisthenics;

use DateTimeImmutable;

final class Content {

    public function __construct(
        public readonly string $name,
        public readonly DateTimeImmutable $postDate,
        public readonly FrontMatter $frontMatter,
        public readonly Template $template,
        public readonly string $outputPath
    ) {}

}