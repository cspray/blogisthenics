<?php

namespace Cspray\Blogisthenics\Test\Support\Stub;

use Cspray\Blogisthenics\Observer\ContentGeneratedHandler;
use Cspray\Blogisthenics\SiteGeneration\Content;

class OverwritingContentOutputPathHandlerStub implements ContentGeneratedHandler {

    public function handle(Content $content) : Content {
        $outputDir = dirname($content->outputPath, 2);
        return $content->withOutputPath($outputDir . '/content-generated-path.html');
    }
}