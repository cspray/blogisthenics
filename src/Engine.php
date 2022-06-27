<?php declare(strict_types=1);

namespace Cspray\Jasg;

use Amp\Promise;
use Cspray\Jasg\Exception\SiteValidationException;
use function Amp\call;
use function Amp\File\filesystem;

/**
 * Responsible for generating a Site based off of the files you have in your source directory, the root directory of your
 * blog, and ultimately writing the rendered output to the directory configured in your site configuration, the
 * config.json file in your root directory.
 *
 * Typically userland code would not interact with this object and instead would be utilized by the internal application
 * script that kicks off the site building process.
 */
final class Engine {

    public function __construct(
        private readonly string        $rootDirectory,
        private readonly SiteGenerator $siteGenerator,
        private readonly SiteWriter    $siteWriter
    ) {}


    /**
     * Promise will be resolved with a Site object that has had all of the content in your blog turned into the appropriate
     * domain object, typically a Page, to later be rendered into appropriate content files and written to disk.
     *
     * @return Site
     */
    public function buildSite() : Site {
        $siteConfig = $this->getSiteConfiguration();

        $this->guardInvalidSiteConfigurationPreGeneration($siteConfig);

        $site = $this->siteGenerator->generateSite($siteConfig);
        $this->siteWriter->writeSite($site);
        return $site;
    }

    private function getSiteConfiguration() : SiteConfiguration {
        $rawConfig = file_get_contents($this->rootDirectory . '/.jasg/config.json');
        $config = json_decode($rawConfig, true);
        return new SiteConfiguration($config);
    }

    private function guardInvalidSiteConfigurationPreGeneration(SiteConfiguration $siteConfiguration) : void {
        $this->validateLayoutDirectory($siteConfiguration);
        $this->validateSiteDirectory($siteConfiguration);
    }

    private function validateLayoutDirectory(SiteConfiguration $siteConfiguration) : void {
        $configuredDir = $siteConfiguration->getLayoutDirectory();
        if (empty($configuredDir)) {
            $msg = 'There is no layouts directory specified in your .jasg/config.json configuration.';
            throw new SiteValidationException($msg);
        }

        $layoutDir = $this->rootDirectory . '/' . $configuredDir;
        if (!is_dir($layoutDir)) {
            $msg = "The layouts directory in your .jasg/config.json configuration, \"$configuredDir\", does not exist.";
            throw new SiteValidationException($msg);
        }
    }

    private function validateSiteDirectory(SiteConfiguration $siteConfiguration) : void {
        $configuredDir = $siteConfiguration->getOutputDirectory();
        if (empty($configuredDir)) {
            $msg = 'There is no output directory specified in your .jasg/config.json configuration.';
            throw new SiteValidationException($msg);
        }
    }

}