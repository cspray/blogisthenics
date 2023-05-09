<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Inject;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Bootstrap\BlogisthenicsParameterStore;

#[Service]
final class DefaultSiteConfiguration implements SiteConfiguration {

    public function __construct(
        #[Inject('rootDir', from: BlogisthenicsParameterStore::STORE_NAME)]
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