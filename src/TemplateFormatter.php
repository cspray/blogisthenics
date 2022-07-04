<?php

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
final class TemplateFormatter {

    /**
     * @var Formatter[]
     */
    private array $formatters;

    public function addFormatter(Formatter $formatter) : void {
        $formatType = $formatter->getFormatType();
        if (isset($this->formatters[$formatType])) {
            throw new \InvalidArgumentException(sprintf(
                'A Formatter is already associated with the format type \'%s\'.',
                $formatType
            ));
        }
        $this->formatters[$formatType] = $formatter;
    }

    public function format(string $format, string $contents) : string {
        if (isset($this->formatters[$format])) {
            return $this->formatters[$format]->format($contents);
        }
        return $contents;
    }

}