<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\Stub;

use Cspray\AnnotatedContainer\Attribute\Inject;
use Cspray\Blogisthenics\Bootstrap\BlogisthenicsMetaDataParameterStore;
use Cspray\Blogisthenics\SiteConfiguration;

class BlankDataSiteConfiguration implements SiteConfiguration {

    public function __construct(
        private readonly string $rootDirectory
    ) {}

    public function getRootDirectory() : string {
        return $this->rootDirectory;
    }

    public function getLayoutDirectory() : string {
        return sprintf('%s/layouts', $this->getRootDirectory());
    }

    public function getComponentDirectory() : string {
        return sprintf('%s/components', $this->getRootDirectory());
    }

    public function getContentDirectory() : string {
        return sprintf('%s/content', $this->getRootDirectory());
    }

    public function getDataDirectory() : ?string {
        return '';
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