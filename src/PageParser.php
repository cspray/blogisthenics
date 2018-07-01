<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics;

use Cspray\Blogisthenics\Exception\ParsingException;

final class PageParser {

    public function parse(string $fileContents) : PageParserResults {
        list($hasFrontMatter, $frontMatterBuffer, $contentBuffer) = $this->parseIntoRawBuffers($fileContents);
        $frontMatter = [];
        if ($hasFrontMatter) {
            $frontMatter = json_decode($frontMatterBuffer, true);
            if (is_null($frontMatter)) {
                $errorMsg = json_last_error_msg();
                throw new ParsingException("An error was encountered parsing FrontMatter: $errorMsg");
            }
        }

        return new PageParserResults($frontMatter, trim($contentBuffer));
    }

    private function parseIntoRawBuffers(string $fileContents) : array {
        $frontMatterBuffer = $contentBuffer = '';
        $counter = 0;
        $parsingFrontMatter = $hasFrontMatter = $fileContents[0] === '{';
        foreach (str_split($fileContents) as $index => $char) {
            // we need to keep track of how many opening braces we see, but only if parsing front matter, to ensure that
            // we capture the appropriate number of closing braces. if we don't check if we're parsing front matter then
            // opening braces in content will trigger front matter parsing
            if ($parsingFrontMatter && $char === '{') {
                $counter++;
            } elseif ($parsingFrontMatter && $char === '}') {
                $counter--;
            }

            // we check for the || conditional to ensure that we capture the last closing } in the front matter object
            if ($counter > 0 || ($parsingFrontMatter && $char === '}')) {
                if ($counter === 0) {
                    // if we don't turn this off when the counter hits 0 braces in content will cause syntax errors in
                    // front matter and not be present in content
                    $parsingFrontMatter = false;
                }
                $frontMatterBuffer .= $char;
            } else {
                $contentBuffer .= $char;
            }
        }

        return [$hasFrontMatter, $frontMatterBuffer, $contentBuffer];
    }

}