<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Utilities;

use App\Utilities\ServiceDocument;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Description of PingResultTest.
 */
class ServiceDocumentTest extends TestCase {
    /**
     * @var ServiceDocument
     */
    private $sd;

    private function getXml() {
        return <<<'ENDXML'
<service xmlns:dcterms="http://purl.org/dc/terms/"
         xmlns:sword="http://purl.org/net/sword/"
         xmlns:atom="http://www.w3.org/2005/Atom"
         xmlns:lom="http://lockssomatic.info/SWORD2"
         xmlns="http://www.w3.org/2007/app">
    <sword:version>2.0</sword:version>
    <sword:maxUploadSize>10000</sword:maxUploadSize>
    <lom:uploadChecksumType>SHA1 MD5</lom:uploadChecksumType>
    <workspace>
        <atom:title>LOCKSSOMatic</atom:title>
        <collection href="http://example.com/path/to/sd">
            <lom:pluginIdentifier id="com.example.text"/>
            <atom:title>Site Title</atom:title>
            <accept>application/atom+xml;type=entry</accept>
            <sword:mediation>true</sword:mediation>
        </collection>
    </workspace>
</service>
ENDXML;
    }

    public function testInstance() : void {
        $this->assertInstanceOf(ServiceDocument::class, $this->sd);
    }

    /**
     * @dataProvider getXpathValueData
     *
     * @param mixed $expected
     * @param mixed $query
     */
    public function testGetXpathValue($expected, $query) : void {
        $value = $this->sd->getXpathValue($query);
        $this->assertSame($expected, $value);
    }

    public function getXpathValueData() {
        return [
            ['2.0', '/app:service/sword:version'],
            [null, '/foo/bar'],
        ];
    }

    public function testGetXpathValueException() : void {
        $this->expectException(Exception::class);
        $this->sd->getXpathValue('/app:service/node()');
    }

    /**
     * @dataProvider valueData
     *
     * @param mixed $expected
     * @param mixed $method
     */
    public function testValue($expected, $method) : void {
        $this->assertSame($expected, $this->sd->{$method}());
    }

    public function valueData() {
        return [
            ['10000', 'getMaxUpload'],
            ['SHA1 MD5', 'getUploadChecksum'],
            ['http://example.com/path/to/sd', 'getCollectionUri'],
        ];
    }

    public function testToString() : void {
        $string = (string) $this->sd;
        $this->assertStringContainsStringIgnoringCase('LOCKSSOMatic', $string);
    }

    protected function setup() : void {
        parent::setUp();
        $this->sd = new ServiceDocument($this->getXml());
    }
}
