<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Utilities;

use App\Utilities\Namespaces;
use PHPUnit\Framework\TestCase;

/**
 * Simplify handling namespaces for SWORD XML documents.
 */
class NamespacesTest extends TestCase {
    /**
     * @dataProvider getNamespaceData
     *
     * @param mixed $prefix
     * @param mixed $expected
     */
    public function testGetNamespace($prefix, $expected) : void {
        $this->assertSame($expected, Namespaces::getNamespace($prefix));
    }

    public function getNamespaceData() {
        return [
            ['dcterms', 'http://purl.org/dc/terms/'],
            ['sword', 'http://purl.org/net/sword/'],
            ['atom', 'http://www.w3.org/2005/Atom'],
            ['lom', 'http://lockssomatic.info/SWORD2'],
            ['rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'],
            ['app', 'http://www.w3.org/2007/app'],
            ['foo', null],
            ['', null],
            [null, null],
        ];
    }

    public function testRegisterNamespaces() : void {
        $xml = simplexml_load_string($this->getXml());
        Namespaces::registerNamespaces($xml);
        $this->assertSame('1', (string) $xml->xpath('//dcterms:a[1]/text()')[0]);
        $this->assertSame('2', (string) $xml->xpath('//sword:b[1]/text()')[0]);
        $this->assertSame('3', (string) $xml->xpath('//atom:c[1]/text()')[0]);
        $this->assertSame('4', (string) $xml->xpath('//lom:d[1]/text()')[0]);
        $this->assertSame('5', (string) $xml->xpath('//rdf:e[1]/text()')[0]);
        $this->assertSame('6', (string) $xml->xpath('//app:f[1]/text()')[0]);
    }

    public function getXml() {
        return <<<'ENDXML'
        <root>
          <a xmlns="http://purl.org/dc/terms/">1</a>
          <b xmlns="http://purl.org/net/sword/">2</b>
          <c xmlns="http://www.w3.org/2005/Atom">3</c>
          <d xmlns="http://lockssomatic.info/SWORD2">4</d>
          <e xmlns="http://www.w3.org/1999/02/22-rdf-syntax-ns#">5</e>
          <f xmlns="http://www.w3.org/2007/app">6</f>
        </root>
ENDXML;
    }
}
