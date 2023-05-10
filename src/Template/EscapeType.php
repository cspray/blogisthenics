<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Template;

enum EscapeType {
    case Html;
    case HtmlAttribute;
    case Css;
    case JavaScript;
    case Url;
}
