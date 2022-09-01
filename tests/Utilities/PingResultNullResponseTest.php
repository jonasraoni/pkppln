<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Utilities;

use App\Utilities\PingResult;
use PHPUnit\Framework\TestCase;

/**
 * Description of PingResultTest.
 */
class PingResultNullResponseTest extends TestCase {
    /**
     * @var PingResult
     */
    private $result;

    public function testInstance() : void {
        $this->assertInstanceOf(PingResult::class, $this->result);
    }

    public function testHttpStatus() : void {
        $this->assertSame(500, $this->result->getHttpStatus());
    }

    public function testGetBody() : void {
        $this->assertSame(null, $this->result->getBody());
    }

    public function testHasXml() : void {
        $this->assertFalse($this->result->hasXml());
        $this->assertNull($this->result->getXml());
    }

    public function testGetHeader() : void {
        $this->assertSame([], $this->result->getHeader('foo'));
    }

    public function testGetOjsRelease() : void {
        $this->assertSame(null, $this->result->getOjsRelease());
    }

    public function testGetPluginReleaseVersion() : void {
        $this->assertSame(null, $this->result->getPluginReleaseVersion());
    }

    public function testPluginReleaseDate() : void {
        $this->assertSame(null, $this->result->getPluginReleaseDate());
    }

    public function testPluginCurrent() : void {
        $this->assertSame(null, $this->result->isPluginCurrent());
    }

    public function testTermsAccepted() : void {
        $this->assertSame(null, $this->result->areTermsAccepted());
    }

    public function testJournalTitle() : void {
        $this->assertSame(null, $this->result->getJournalTitle());
    }

    public function testArticleCount() : void {
        $this->assertSame(null, $this->result->getArticleCount());
    }

    public function testArticleTitles() : void {
        $expected = [];
        $this->assertSame($expected, $this->result->getArticleTitles());
    }

    protected function setup() : void {
        parent::setUp();
        $this->result = new PingResult();
    }
}
