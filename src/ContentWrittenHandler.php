<?php

namespace Cspray\Blogisthenics;

interface ContentWrittenHandler {

    public function handle(Content $content) : void;

}