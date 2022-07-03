<?php

namespace Cspray\Blogisthenics;

interface ContentGeneratedHandler {

    public function handle(Content $content) : Content;

}