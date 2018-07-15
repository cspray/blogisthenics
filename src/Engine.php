<?php declare(strict_types=1);

namespace Cspray\Jasg;

use Cspray\Jasg\Engine\{SiteGenerator, SiteWriter};
use Amp\Promise;
use function Amp\call;
use function Amp\File\filesystem;
use Cspray\Jasg\Exception\SiteValidationException;

/**
 * Responsible for generating a Site based off of the files you have in your source directory, the root directory of your
 * blog, and ultimately writing the rendered output to the directory configured in your site configuration, the
 * config.json file in your root directory.
 *
 * Typically userland code would not interact with this object and instead would be utilized by the internal application
 * script that kicks off the site building process.
 */
final class Engine {

    private $rootDirectory;
    private $siteGenerator;
    private $siteWriter;

    /**
     * @param SiteGenerator $siteGenerator
     * @param SiteWriter $siteWriter
     */
    public function __construct(string $rootDirectory, SiteGenerator $siteGenerator, SiteWriter $siteWriter) {
        $this->rootDirectory = $rootDirectory;
        $this->siteGenerator = $siteGenerator;
        $this->siteWriter = $siteWriter;
    }


    /**
     * Promise will be resolved with a Site object that has had all of the content in your blog turned into the appropriate
     * domain object, typically a Page, to later be rendered into appropriate content files and written to disk.
     *
     * @return Promise
     */
    public function buildSite() : Promise {
        return call(function() {
            $siteConfig = yield $this->getSiteConfiguration();

            yield $this->guardInvalidSiteConfigurationPreGeneration($siteConfig);

            $site = yield $this->siteGenerator->generateSite($siteConfig);
            yield $this->siteWriter->writeSite($site);
            return $site;
        });
    }

    private function getSiteConfiguration() : Promise {
        return call(function() {
            $rawConfig = yield filesystem()->get($this->rootDirectory . '/.jasg/config.json');
            $config = json_decode($rawConfig, true);
            return new SiteConfiguration($config);
        });
    }

    private function guardInvalidSiteConfigurationPreGeneration(SiteConfiguration $siteConfiguration) : Promise {
        return call(function() use($siteConfiguration) {
            yield $this->validateLayoutDirectory($siteConfiguration);
            yield $this->validateSiteDirectory($siteConfiguration);
        });
    }

    private function validateLayoutDirectory(SiteConfiguration $siteConfiguration) : Promise {
        return call(function() use($siteConfiguration) {
            $configuredDir = $siteConfiguration->getLayoutDirectory();
            if (empty($configuredDir)) {
                $msg = 'There is no layouts directory specified in your .jasg/config.json configuration.';
                throw new SiteValidationException($msg);
            }

            $layoutDir = $this->rootDirectory . '/' . $configuredDir;
            $exists = yield filesystem()->exists($layoutDir);
            if (!$exists) {
                $msg = "The layouts directory in your .jasg/config.json configuration, \"$configuredDir\", does not exist.";
                throw new SiteValidationException($msg);
            }
        });
    }

    private function validateSiteDirectory(SiteConfiguration $siteConfiguration) : Promise {
        return call(function() use($siteConfiguration) {
            $configuredDir = $siteConfiguration->getOutputDirectory();
            if (empty($configuredDir)) {
                $msg = 'There is no output directory specified in your .jasg/config.json configuration.';
                throw new SiteValidationException($msg);
            }
        });
    }

}