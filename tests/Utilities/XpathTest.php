<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Utilities;

use App\Utilities\Xpath;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Description of XpathTest.
 */
class XpathTest extends TestCase {
    private function getXml() {
        $data = <<<'ENDXML'
        <root>
          <a>1</a>
          <b>2</b>
        </root>
ENDXML;

        return simplexml_load_string($data);
    }

    /**
     * @dataProvider getXmlData
     *
     * @param mixed $expected
     * @param mixed $query
     * @param null|mixed $default
     */
    public function testGetXmlValue($expected, $query, $default = null) : void {
        $xml = $this->getXml();
        $this->assertSame($expected, Xpath::getXmlValue($xml, $query, $default));
    }

    public function getXmlData() {
        return [
            ['1', '//a', null],
            ['1', '//a', '3'],
            ['3', '//c', '3'],
        ];
    }

    public function testGetXmlValueException() : void {
        $this->expectException(Exception::class);
        $xml = $this->getXml();
        Xpath::getXmlValue($xml, '/root/node()');
    }

    public function testQuery() : void {
        $xml = $this->getXml();
        $result = Xpath::query($xml, '/root/*');
        $this->assertSame(2, count($result));
    }
}
