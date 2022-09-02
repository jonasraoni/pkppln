<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services\Processing;

use App\Services\Processing\VirusScanner;
use App\Utilities\XmlParser;
use DOMDocument;
use DOMElement;
use DOMXPath;
use App\Tests\TestCase\BaseControllerTestCase;
use Socket\Raw\Factory;
use Socket\Raw\Socket;
use Xenolope\Quahog\Client;
use Xenolope\Quahog\Result;

/**
 * This test makes use of the EICAR test signature found here:
 * http://www.eicar.org/86-0-Intended-use.html.
 */
class VirusScannerTest extends BaseControllerTestCase
{
    /**
     * @var VirusScanner
     */
    private $scanner;

    public function testInstance(): void
    {
        $this->assertInstanceOf(VirusScanner::class, $this->scanner);
    }

    public function testGetClient(): void
    {
        $factory = $this->createMock(Factory::class);
        $socket = $this->createMock(Socket::class);
        $socket->method('send')->willReturn(null);
        $factory->method('createClient')->willReturn($socket);
        $this->scanner->setFactory($factory);
        $client = $this->scanner->getClient();
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testScanEmbed(): void
    {
        $embed = new DOMElement('unused');
        $xp = $this->createMock(DOMXPath::class);
        $xp->method('evaluate')->will($this->onConsecutiveCalls(10, base64_encode("We're fine. We're all fine here, now, thank you. How are you?")));
        $client = $this->createMock(Client::class);
        $client->method('scanResourceStream')->willReturn(new Result(Client::RESULT_OK, 'stream', null, null));
        $result = $this->scanner->scanEmbed($embed, $xp, $client);
        $this->assertSame('stream', $result->getFilename());
        $this->assertNull($result->getReason());
        $this->assertTrue($result->isOk());
    }

    public function testScanEmbedEicar(): void
    {
        $embed = new DOMElement('unused');
        $xp = $this->createMock(DOMXPath::class);
        $xp->method('evaluate')->will($this->onConsecutiveCalls(10, base64_encode('X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*')));
        $client = $this->createMock(Client::class);
        $client->method('scanResourceStream')->willReturn(new Result(Client::RESULT_FOUND, 'stream', 'EICAR', null, null));
        $result = $this->scanner->scanEmbed($embed, $xp, $client);
        $this->assertSame('stream', $result->getFilename());
        $this->assertSame('EICAR', $result->getReason());
        $this->assertTrue($result->isFound());
    }

    /**
     * @group virusscanner
     */
    public function testLiveScanEmbed(): void
    {
        $embed = new DOMElement('unused');
        $xp = $this->createMock(DOMXPath::class);
        $xp->method('evaluate')->will($this->onConsecutiveCalls(10, base64_encode("We're fine. We're all fine here, now, thank you. How are you?")));

        $result = $this->scanner->scanEmbed($embed, $xp, $this->scanner->getClient());
        $this->assertSame('1', $result->getId());
        $this->assertSame('stream', $result->getFilename());
        $this->assertNull($result->getReason());
        $this->assertTrue($result->isOk());
    }

    /**
     * @group virusscanner
     */
    public function testLiveScanEmbedEicar(): void
    {
        $embed = new DOMElement('unused');
        $xp = $this->createMock(DOMXPath::class);
        $xp->method('evaluate')->will($this->onConsecutiveCalls(10, base64_encode('X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*')));
        $result = $this->scanner->scanEmbed($embed, $xp, $this->scanner->getClient());
        $this->assertSame('1', $result->getId());
        $this->assertSame('stream', $result->getFilename());
        $this->assertSame('Eicar-Test-Signature', $result->getReason());
        $this->assertTrue($result->isFound());
    }

    public function testScanCleanXmlFile(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML($this->getCleanXml());
        $parser = $this->createMock(XmlParser::class);
        $parser->method('fromFile')->willReturn($dom);
        $client = $this->createMock(Client::class);
        $client->method('scanResourceStream')->willReturn(new Result(Client::RESULT_OK, 'stream', null, null));
        $result = $this->scanner->scanXmlFile('foo', $client, $parser);
        $this->assertSame(['file1 OK', 'file2 OK'], $result);
    }

    public function testScanDirtyXmlFile(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML($this->getDirtyXml());
        $parser = $this->createMock(XmlParser::class);
        $parser->method('fromFile')->willReturn($dom);
        $client = $this->createMock(Client::class);
        $client->method('scanResourceStream')->will($this->onConsecutiveCalls(
            new Result(Client::RESULT_OK, 'stream', null, null),
            new Result(Client::RESULT_FOUND, 'stream', 'Eicar', null, null)
        ));
        $result = $this->scanner->scanXmlFile('foo', $client, $parser);
        $this->assertSame(['file1 OK', 'file2 FOUND: Eicar'], $result);
    }

    /**
     * @group virusscanner
     */
    public function testLiveScanCleanXmlFile(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML($this->getCleanXml());
        $parser = $this->createMock(XmlParser::class);
        $parser->method('fromFile')->willReturn($dom);
        $client = $this->scanner->getClient();
        $result = $this->scanner->scanXmlFile('foo', $client, $parser);
        $this->assertSame(['file1 OK', 'file2 OK'], $result);
    }

    /**
     * @group virusscanner
     */
    public function testLiveScanDirtyXmlFile(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML($this->getDirtyXml());
        $parser = $this->createMock(XmlParser::class);
        $parser->method('fromFile')->willReturn($dom);
        $client = $this->scanner->getClient();
        $result = $this->scanner->scanXmlFile('foo', $client, $parser);
        $this->assertSame(['file1 OK', 'file2 FOUND: Eicar-Test-Signature'], $result);
    }

    public function getCleanXml()
    {
        return <<<'ENDXML'
<root>
    <!-- All good. -->
    <embed filename='file1'>QWxsIGdvb2QuCg==</embed>
    <!-- Ooh, an EICAR test signature -->
    <embed filename='file2'>Y2hlZXNlIGlzIHRoZSBiZXN0Cg==</embed>
</root>
ENDXML;
    }

    public function getDirtyXml()
    {
        return <<<'ENDXML'
<root>
    <!-- All good. -->
    <embed filename='file1'>QWxsIGdvb2QuCg==</embed>
    <!-- Ooh, an EICAR test signature -->
    <embed filename='file2'>WDVPIVAlQEFQWzRcUFpYNTQoUF4pN0NDKTd9JEVJQ0FSLVNUQU5EQVJELUFOVElWSVJVUy1URVNULUZJTEUhJEgrSCo=</embed>
</root>
ENDXML;
    }

    protected function setup(): void
    {
        parent::setUp();
        $this->scanner = self::$container->get(VirusScanner::class);
    }
}
