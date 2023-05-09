<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface SiteConfiguration {

    public function getRootDirectory() : string;

    public function getLayoutDirectory() : string;

    public function getComponentDirectory() : string;

    public function getContentDirectory() : string;

    public function getDataDirectory() : ?string;

    public function getOutputDirectory() : string;

    public function getDefaultLayout() : string;

    public function shouldIncludeDraftContent() : bool;

}