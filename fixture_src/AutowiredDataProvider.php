<?php declare(strict_types=1);

namespace Cspray\BlogisthenicsFixture;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\SiteData\DataProvider;
use Cspray\Blogisthenics\SiteData\KeyValueStore;

#[Service]
final class AutowiredDataProvider implements DataProvider {

    public function addData(KeyValueStore $keyValue) : void {
        $keyValue->set(__METHOD__, 'autowired');
    }
}