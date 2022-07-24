<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Exception\SiteGenerationException;
use Stringy\Stringy as S;

/**
 * Responsible for generating a Site based off of the files you have in your source directory, the root directory of your
 * blog, and ultimately writing the rendered output to the directory configured in your site configuration, the
 * config.json file in your root directory.
 *
 * Typically userland code would not interact with this object and instead would be utilized by the internal application
 * script that kicks off the site building process.
 */
#[Service]
final class Engine {

    /**
     * @var DataProvider[]
     */
    private array $dataProviders = [];

    /**
     * @var TemplateHelperProvider[]
     */
    private array $templateHelperProviders = [];

    /**
     * @var DynamicContentProvider[]
     */
    private array $dynamicContentProviders = [];

    public function __construct(
        private readonly SiteConfiguration $siteConfiguration,
        private readonly SiteGenerator $siteGenerator,
        private readonly SiteWriter $siteWriter,
        private readonly KeyValueStore $keyValueStore,
        private readonly MethodDelegator $methodDelegator
    ) {}

    public function addDataProvider(DataProvider $dataProvider) : void {
        $this->dataProviders[] = $dataProvider;
    }

    public function addTemplateHelperProvider(TemplateHelperProvider $helperProvider) : void {
        $this->templateHelperProviders[] = $helperProvider;
    }

    public function addDynamicContentProvider(DynamicContentProvider $dynamicContentProvider) : void {
        $this->dynamicContentProviders[] = $dynamicContentProvider;
    }

    public function addContentGeneratedHandler(ContentGeneratedHandler $handler) : void {
        $this->siteGenerator->addHandler($handler);
    }

    public function addContentWrittenHandler(ContentWrittenHandler $handler) : void {
        $this->siteWriter->addHandler($handler);
    }

    /**
     * Promise will be resolved with a Site object that has had all of the content in your blog turned into the appropriate
     * domain object, typically a Page, to later be rendered into appropriate content files and written to disk.
     *
     * @return Site
     */
    public function buildSite() : Site {
        $this->loadStaticData();

        foreach ($this->dataProviders as $dataProvider) {
            $dataProvider->addData($this->keyValueStore);
        }

        foreach ($this->templateHelperProviders as $templateHelperProvider) {
            $templateHelperProvider->addTemplateHelpers($this->methodDelegator);
        }

        $site = $this->siteGenerator->generateSite();

        foreach ($this->dynamicContentProviders as $dynamicContentProvider) {
            $dynamicContentProvider->addContent($site);
        }

        $this->removeDirectory($this->siteConfiguration->getOutputPath());

        $this->siteWriter->writeSite($site);
        return $site;
    }

    private function loadStaticData() : void {
        $siteConfiguration = $this->siteConfiguration;
        if (!empty($dataPath = $siteConfiguration->getDataPath())) {
            $dirIterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dataPath, \FilesystemIterator::SKIP_DOTS)
            );
            /** @var \SplFileInfo $file */
            foreach ($dirIterator as $file) {
                $json = json_decode(file_get_contents($file->getPathname()), true);
                if (is_null($json)) {
                    throw new SiteGenerationException(sprintf(
                        'A static data file, "%s", is not valid JSON.',
                        $file->getPathname()
                    ));
                }
                $namespace = S::create($file->getPathname())
                    ->replace($dataPath, '')
                    ->replace('.' . $file->getExtension(), '')
                    ->trimLeft('/');
                foreach ($json as $key => $value) {
                    $this->keyValueStore->set(sprintf('%s/%s', $namespace, $key), $value);
                }
            }
        }
    }

    private function removeDirectory(string $path) : void {
        if (is_file($path)) {
            unlink($path);
        } else if (is_dir($path)) {
            $iterator = new \FilesystemIterator($path, \FilesystemIterator::CURRENT_AS_PATHNAME);
            foreach ($iterator as $_path) {
                $this->removeDirectory($_path);
            }

            rmdir($path);
        }
    }

}