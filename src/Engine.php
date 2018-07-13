<?php declare(strict_types=1);

namespace Cspray\Jasg;

use Cspray\Jasg\Engine\{SiteGenerator, SiteWriter};
use Amp\Promise;
use function Amp\call;

/**
 * Responsible for generating a Site based off of the files you have in your source directory, the root directory of your
 * blog, and ultimately writing the rendered output to the directory configured in your site configuration, the
 * config.json file in your root directory.
 *
 * Typically userland code would not interact with this object and instead would be utilized by the internal application
 * script that kicks off the site building process.
 */
final class Engine {

    private $siteGenerator;
    private $siteWriter;

    /**
     * @param SiteGenerator $siteGenerator
     * @param SiteWriter $siteWriter
     */
    public function __construct(SiteGenerator $siteGenerator, SiteWriter $siteWriter) {
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
            /** @var Site $site */
            $site = yield $this->siteGenerator->generateSite();
            yield $this->siteWriter->writeSite($site);
            return $site;
        });
    }

}