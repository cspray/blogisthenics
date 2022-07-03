<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

final class SiteConfiguration {

    public function __construct(
        public readonly string $layoutDirectory = 'layouts',
        public readonly string $contentDirectory = 'content',
        public readonly string $outputDirectory = '_site',
        public readonly string $defaultLayout = 'main.html'
    ) {}

}