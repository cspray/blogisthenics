<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\Stub;

use Cspray\AnnotatedContainer\Attribute\Inject;
use Cspray\Blogisthenics\Bootstrap\BlogisthenicsParameterStore;
use Cspray\Blogisthenics\SiteConfiguration;

class BlankLayoutSiteConfiguration implements SiteConfiguration {

    public function __construct(
        private readonly string $rootDirectory
    ) {}

    public function getRootDirectory() : string {
        return $this->rootDirectory;
    }

    public function getLayoutDirectory() : string {
        return '';
    }

    public function getComponentDirectory() : string {
        return sprintf('%s/components', $this->getRootDirectory());
    }

    public function getContentDirectory() : string {
        return sprintf('%s/content', $this->getRootDirectory());
    }

    public function getDataDirectory() : ?string {
        $dataDir = sprintf('%s/data', $this->getRootDirectory());
        if (is_dir($dataDir)) {
            return $dataDir;
        }

        return null;
    }

    public function getOutputDirectory() : string {
        return sprintf('%s/_site', $this->getRootDirectory());
    }

    public function getDefaultLayout() : string {
        return 'main';
    }

    public function shouldIncludeDraftContent() : bool {
        return true;
    }
}