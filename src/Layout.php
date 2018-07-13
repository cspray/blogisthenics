<?php declare(strict_types=1);


namespace Cspray\Jasg;

class Layout extends AbstractContent implements Content {

    public function getType(): string {
        return ContentType::LAYOUT;
    }

}