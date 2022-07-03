<?php

namespace Cspray\Blogisthenics\Test\Support\Stub;

use Cspray\Blogisthenics\Content;
use Cspray\Blogisthenics\ContentGeneratedHandler;

class OverwritingContentOutputPathHandlerStub implements ContentGeneratedHandler {

    public function handle(Content $content) : Content {
        $outputDir = dirname($content->outputPath);
        return $content->withOutputPath($outputDir . '/content-generated-path.html');
    }
}