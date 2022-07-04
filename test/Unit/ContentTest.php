<?php

namespace Cspray\Blogisthenics\Test\Unit;

use Cspray\Blogisthenics\Content;
use Cspray\Blogisthenics\Exception\InvalidStateException;
use Cspray\Blogisthenics\FrontMatter;
use Cspray\Blogisthenics\Template;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase {

    public function testNoFrontMatterAttributePresentDefaultsToPublished() : void {
        $content = new Content(
            'name',
            new \DateTimeImmutable(),
            new FrontMatter([]),
            $this->getMockBuilder(Template::class)->getMock(),
            null
        );

        $this->assertTrue($content->isPublished());
        $this->assertFalse($content->isDraft());
    }

    public function testFrontMatterSetPublishedFalseIsDraft() : void {
        $content = new Content(
            'name',
            new \DateTimeImmutable(),
            new FrontMatter(['published' => false]),
            $this->getMockBuilder(Template::class)->getMock(),
            null
        );

        $this->assertFalse($content->isPublished());
        $this->assertTrue($content->isDraft());
    }

    public function testGetRenderedContentsBeforeRenderThrowsException() : void {
        $content = new Content(
            'name',
            new \DateTimeImmutable(),
            new FrontMatter(['published' => false]),
            $this->getMockBuilder(Template::class)->getMock(),
            null
        );

        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('Called ' . Content::class . '::getRenderedContents before the corresponding Template has been rendered.');

        $content->getRenderedContents();
    }

}