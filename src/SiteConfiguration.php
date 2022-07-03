<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

final class SiteConfiguration {

    public function __construct(
        public readonly string $layoutDirectory,
        public readonly string $contentDirectory,
        public readonly ?string $dataDirectory,
        public readonly string $outputDirectory,
        public readonly string $defaultLayout
    ) {}

    /**
     * @return array
     */
    public static function getDefaults() : array {
        return [
            'layout_directory' => 'layouts',
            'content_directory' => 'content',
            'output_directory' => '_site',
            'default_layout' => 'main'
        ];
    }

}