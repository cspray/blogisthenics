<?php

namespace Cspray\Blogisthenics\Test\Unit;

use Cspray\Blogisthenics\Formatter;
use Cspray\Blogisthenics\TemplateFormatter;
use PHPUnit\Framework\TestCase;

class TemplateFormatterTest extends TestCase {

    public function testFormatWithNoFormattersReturnsContentUnchanged() {
        $subject = new TemplateFormatter();

        $actual = $subject->format('not-found', 'my content unchanged');

        $this->assertSame('my content unchanged', $actual);
    }

    public function testFormatWithFormatterAddedAfterConstruct() {
        $formatter = $this->getMockBuilder(Formatter::class)->getMock();
        $formatter->expects($this->once())
            ->method('getFormatType')
            ->willReturn('foo');
        $formatter->expects($this->once())
            ->method('format')
            ->with('content')
            ->willReturn('my formatted content');

        $subject = new TemplateFormatter();
        $subject->addFormatter($formatter);

        $actual = $subject->format('foo', 'content');

        $this->assertSame($actual, 'my formatted content');
    }

    public function testFormatterAddedWithFormatTypeAlreadyPresentThrowsException() {
        $formatter = $this->getMockBuilder(Formatter::class)->getMock();
        $formatter->expects($this->once())
            ->method('getFormatType')
            ->willReturn('foo');
        $dupe = $this->getMockBuilder(Formatter::class)->getMock();
        $dupe->expects($this->once())
            ->method('getFormatType')
            ->willReturn('foo');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A Formatter is already associated with the format type \'foo\'.');

        $templateFormatter = new TemplateFormatter();
        $templateFormatter->addFormatter($formatter);
        $templateFormatter->addFormatter($dupe);
    }

}