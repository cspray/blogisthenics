<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Cspray\Blogisthenics\Exception\SiteValidationException;
use Stringy\Stringy as S;

/**
 * Responsible for generating a Site based off of the files you have in your source directory, the root directory of your
 * blog, and ultimately writing the rendered output to the directory configured in your site configuration, the
 * config.json file in your root directory.
 *
 * Typically userland code would not interact with this object and instead would be utilized by the internal application
 * script that kicks off the site building process.
 */
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
        private readonly string $rootDirectory,
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

    /**
     * Promise will be resolved with a Site object that has had all of the content in your blog turned into the appropriate
     * domain object, typically a Page, to later be rendered into appropriate content files and written to disk.
     *
     * @return Site
     */
    public function buildSite() : Site {
        $siteConfig = $this->getSiteConfiguration();

        $this->guardInvalidSiteConfigurationPreGeneration($siteConfig);

        foreach ($this->dataProviders as $dataProvider) {
            $dataProvider->setData($this->keyValueStore);
        }

        foreach ($this->templateHelperProviders as $templateHelperProvider) {
            $templateHelperProvider->addTemplateHelpers($this->methodDelegator);
        }

        $site = $this->siteGenerator->generateSite($siteConfig);

        foreach ($this->dynamicContentProviders as $dynamicContentProvider) {
            $dynamicContentProvider->addContent($site);
        }

        $this->siteWriter->writeSite($site);
        return $site;
    }

    private function getSiteConfiguration() : SiteConfiguration {
        $rawConfig = file_get_contents($this->rootDirectory . '/.blogisthenics/config.json');
        $config = json_decode($rawConfig, true);
        return new SiteConfiguration(
            $config['layout_directory'],
            $config['content_directory'],
            $config['output_directory'],
            $config['default_layout']
        );
    }

    private function guardInvalidSiteConfigurationPreGeneration(SiteConfiguration $siteConfiguration) : void {
        $this->validateDirectory($siteConfiguration, 'layout_directory', true);
        $this->validateDirectory($siteConfiguration, 'content_directory', true);
        $this->validateDirectory($siteConfiguration, 'output_directory', false);
    }

    private function validateDirectory(SiteConfiguration $siteConfiguration, string $configKey, bool $mustExist) : void {
        $configProp = S::create($configKey)->camelize();
        $configuredDir = $siteConfiguration->$configProp;
        if (empty($configuredDir)) {
            $msg = sprintf('There is no "%s" specified in your .blogisthenics/config.json configuration.', $configKey);
            throw new SiteValidationException($msg);
        }

        if ($mustExist) {
            $layoutDir = $this->rootDirectory . '/' . $configuredDir;
            if (!is_dir($layoutDir)) {
                $msg = $this->getMissingDirectoryMessage($configKey, $configuredDir);
                throw new SiteValidationException($msg);
            }
        }
    }

    private function getMissingDirectoryMessage(string $config, string $dir) : string {
        return sprintf(
            'The "%s" in your .blogisthenics/config.json configuration, "%s", does not exist.'  ,
            $config,
            $dir
        );
    }

}