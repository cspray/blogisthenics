<?php

namespace Cspray\Blogisthenics\Observer;

use Cspray\Blogisthenics\Content;

/**
 * An observer that is triggered every time a piece of content is written to its output path.
 */
interface ContentWrittenHandler {

    /**
     *
     *
     * @param Content $content The content that was written, it is safe to call $content::getRenderedContents()
     * @return void
     */
    public function handle(Content $content) : void;

}