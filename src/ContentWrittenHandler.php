<?php

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;

/**
 * An observer that is triggered every time a piece of content is written to its output path.
 */
#[Service]
interface ContentWrittenHandler {

    /**
     *
     *
     * @param Content $content The content that was written, it is safe to call $content::getRenderedContents()
     * @return void
     */
    public function handle(Content $content) : void;

}