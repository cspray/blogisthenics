<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
final class SiteConfiguration {

    public function __construct(
        private readonly string $rootDirectory,
        private readonly string $layoutDirectory,
        private readonly string $contentDirectory,
        private readonly ?string $dataDirectory,
        private readonly string $outputDirectory,
        private readonly string $defaultLayout,
        private readonly bool $includeDraftContent
    ) {}

    public function getRootDirectory() : string {
        return $this->rootDirectory;
    }

    public function getLayoutPath() : string {
        return sprintf(
            '%s/%s',
            $this->getRootDirectory(),
            $this->layoutDirectory
        );
    }

    public function getContentPath() : string {
        return sprintf(
            '%s/%s',
            $this->getRootDirectory(),
            $this->contentDirectory
        );
    }

    public function getDataPath() : ?string {
        if (!isset($this->dataDirectory)) {
            return null;
        }

        return sprintf(
            '%s/%s',
            $this->getRootDirectory(),
            $this->dataDirectory
        );
    }

    public function getOutputPath() : string {
        return sprintf(
            '%s/%s',
            $this->getRootDirectory(),
            $this->outputDirectory
        );
    }

    public function getDefaultLayout() : string {
        return $this->defaultLayout;
    }

    public function shouldIncludeDraftContent() : bool {
        return $this->includeDraftContent;
    }

}