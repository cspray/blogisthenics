<?php

namespace Cspray\Blogisthenics\Observer;

use Cspray\Blogisthenics\SiteGeneration\Content;

/**
 * An observer that is triggered every time a piece of Content is generated, before it is added to the site.
 */
interface ContentGenerated {

    /**
     * Allows for taking some action on a piece of Content that will be written to the site.
     *
     * The ability to override the Content by returning a different instance is useful in situations where you might
     * want to adjust the front matter or output path of a content.
     *
     * @param Content $content The Content that was generated, will not include Layouts
     * @return void
     */
    public function notify(Content $content) : void;

}