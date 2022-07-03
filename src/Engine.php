<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Cspray\Blogisthenics\Exception\SiteGenerationException;
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

        $this->loadStaticData($siteConfig);

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
        $filePath = $this->rootDirectory . '/.blogisthenics/config.json';
        if (is_file($filePath)) {
            $config = json_decode(file_get_contents($filePath), true);
        } else {
            $config = SiteConfiguration::getDefaults();
        }

        $this->guardInvalidSiteConfigurationPreGeneration($config);

        return new SiteConfiguration(
            layoutDirectory: $config['layout_directory'],
            contentDirectory: $config['content_directory'],
            dataDirectory: $config['data_directory'] ?? null,
            outputDirectory: $config['output_directory'],
            defaultLayout: $config['default_layout']
        );
    }

    private function guardInvalidSiteConfigurationPreGeneration(array $rawConfiguration) : void {
        $this->validateDirectory($rawConfiguration, 'layout_directory', true);
        $this->validateDirectory($rawConfiguration, 'content_directory', true);
        $this->validateDirectory($rawConfiguration, 'output_directory', false);
        if (array_key_exists('data_directory', $rawConfiguration)) {
            $this->validateDirectory(
                $rawConfiguration,
                'data_directory',
                true,
                ' If your site does not require static data do not include this configuration value.'
            );
        }
    }

    private function validateDirectory(array $rawConfiguration, string $configKey, bool $mustExist, string $messageSuffix = null) : void {
        $configuredDir = $rawConfiguration[$configKey] ?? null;
        if (empty($configuredDir)) {
            $msg = sprintf('The "%s" specified in your .blogisthenics/config.json configuration contains a blank value.', $configKey);
            if (isset($messageSuffix)) {
                $msg .= $messageSuffix;
            }
            throw new SiteValidationException($msg);
        }

        if ($mustExist) {
            $layoutDir = $this->rootDirectory . '/' . $configuredDir;
            if (!is_dir($layoutDir)) {
                $msg = $this->getMissingDirectoryMessage($configKey, $configuredDir);
                if (isset($messageSuffix)) {
                    $msg .= $messageSuffix;
                }
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

    private function loadStaticData(SiteConfiguration $siteConfiguration) : void {
        if (isset($siteConfiguration->dataDirectory)) {
            $dataPath = sprintf('%s/%s', $this->rootDirectory, $siteConfiguration->dataDirectory);
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

}