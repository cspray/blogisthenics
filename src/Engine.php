<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Exception\SiteGenerationException;
use Cspray\Blogisthenics\Exception\SiteValidationException;
use Cspray\Blogisthenics\Observer\ContentGeneratedHandler;
use Cspray\Blogisthenics\Observer\ContentWrittenHandler;
use Cspray\Blogisthenics\SiteData\DataProvider;
use Cspray\Blogisthenics\SiteData\KeyValueStore;
use Cspray\Blogisthenics\SiteGeneration\DynamicContentProvider;
use Cspray\Blogisthenics\SiteGeneration\SiteGenerator;
use Cspray\Blogisthenics\SiteGeneration\SiteWriter;
use Cspray\Blogisthenics\Template\MethodDelegator;
use Cspray\Blogisthenics\Template\TemplateHelperProvider;
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
        $this->guardInvalidSiteConfigurationPreGeneration($this->siteConfiguration);
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

        $this->removeDirectory($this->siteConfiguration->getOutputDirectory());

        $this->siteWriter->writeSite($site);
        return $site;
    }

    private function loadStaticData() : void {
        $siteConfiguration = $this->siteConfiguration;
        if (!empty($dataPath = $siteConfiguration->getDataDirectory())) {
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
            $iterator = new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);
            /** @var \SplFileInfo $_path */
            foreach ($iterator as $_path) {
                $this->removeDirectory($_path->getPathname());
            }

            rmdir($path);
        }
    }

    private function guardInvalidSiteConfigurationPreGeneration(SiteConfiguration $siteConfiguration) : void {
        $this->validateDirectory('getLayoutDirectory', $siteConfiguration->getLayoutDirectory(),  true);
        $this->validateDirectory('getContentDirectory', $siteConfiguration->getContentDirectory(), true);
        $this->validateDirectory('getOutputDirectory', $siteConfiguration->getOutputDirectory(), false);
        $dataDirectory = $siteConfiguration->getDataDirectory();
        if ($dataDirectory !== null) {
            $this->validateDirectory(
                'getDataDirectory',
                $dataDirectory,
                true,
                ' If your site does not require static data do not include this configuration value.'
            );
        }
    }

    private function validateDirectory(string $configName, string $directory, bool $mustExist, string $messageSuffix = null) : void {
        if (trim($directory) === '') {
            throw new SiteValidationException(sprintf(
                'SiteConfiguration::%s returned a blank value.',
                $configName
            ));
        }

        if ($mustExist) {
            if (!is_dir($directory)) {
                $msg = $this->getMissingDirectoryMessage($configName, $directory);
                if (isset($messageSuffix)) {
                    $msg .= $messageSuffix;
                }
                throw new SiteValidationException($msg);
            }
        }
    }

    private function getMissingDirectoryMessage(string $configName, string $dir) : string {
        return sprintf(
            'SiteConfiguration::%s specifies a directory, "%s", that does not exist.',
            $configName,
            $dir
        );
    }

}