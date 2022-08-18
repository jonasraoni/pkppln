<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\DataFixtures\DepositFixtures;
use App\DataFixtures\JournalFixtures;
use App\Services\FilePaths;
use App\Services\SwordClient;
use App\Utilities\ServiceDocument;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use org\bovigo\vfs\vfsStream;
use SimpleXMLElement;

/**
 * Description of PingTest.
 */
class SwordClientTest extends ControllerBaseCase {
    /**
     * @var SwordClient
     */
    private $swordClient;

    private function serviceDocumentData() {
        return <<<'ENDXML'
<?xml version="1.0" ?>
<service xmlns:dcterms="http://purl.org/dc/terms/"
    xmlns:sword="http://purl.org/net/sword/"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xmlns:lom="http://lockssomatic.info/SWORD2"
    xmlns="http://www.w3.org/2007/app">
    <sword:version>2.0</sword:version>
    <!-- sword:maxUploadSize is the maximum file size in content element, measured in kB (1,000 bytes). -->
    <sword:maxUploadSize>10000</sword:maxUploadSize>
    <lom:uploadChecksumType>SHA1</lom:uploadChecksumType>
    <workspace>
        <atom:title>LOCKSSOMatic</atom:title>
        <collection href="http://localhost/lom2/web/app_dev.php/api/sword/2.0/col-iri/29125DE2-E622-416C-93EB-E887B2A3126C">
            <lom:pluginIdentifier id="com.example.text"/>
            <atom:title>Test Provider 1</atom:title>
            <accept>application/atom+xml;type=entry</accept>
            <sword:mediation>true</sword:mediation>
        </collection>
    </workspace>
</service>
ENDXML;
    }

    private function createDepositResponse() {
        return <<<'ENDXML'
<entry xmlns="http://www.w3.org/2005/Atom"
       xmlns:sword="http://purl.org/net/sword/">
    <sword:treatment>Content URLs deposited to Network Test, collection Test Provider 1.</sword:treatment>
    <content src="http://localhost/lom2/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/state"/>
    <!-- Col-IRI. -->
    <link rel="edit-media" href="http://localhost/lom2/web/app_dev.php/api/sword/2.0/col-iri/29125DE2-E622-416C-93EB-E887B2A3126C" />
    <!-- SE-IRI (can be same as Edit-IRI) -->
    <link rel="http://purl.org/net/sword/terms/add" href="http://localhost/lom2/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/edit" />
    <!-- Edit-IRI -->
    <link rel="edit" href="http://localhost/lom2/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/edit" />
    <!-- In LOCKSS-O-Matic, the State-IRI will be the EM-IRI/Cont-IRI with the string '/state' appended. -->
    <link rel="http://purl.org/net/sword/terms/statement" type="application/atom+xml;type=feed"
          href="http://localhost/lom2/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/state" />
</entry>
ENDXML;
    }

    private function receiptData() {
        return <<<'ENDXML'
<entry xmlns="http://www.w3.org/2005/Atom"
       xmlns:sword="http://purl.org/net/sword/">

    <sword:treatment>Content URLs deposited to TestPLN.</sword:treatment>
    <content src="http://path/to/statement"/>
    <link rel="edit-media" href="http://path/to/collection" />
    <link rel="http://purl.org/net/sword/terms/add" href="http://path/to/receipt" />
    <link rel="edit" href="http://path/to/receipt" />
    <link rel="http://purl.org/net/sword/terms/statement" type="application/atom+xml;type=feed" href="http://path/to/statement" />
</entry>
ENDXML;
    }

    private function statementData() {
        return <<<'ENDXML'
<atom:feed xmlns:sword="http://purl.org/net/sword/terms/"
           xmlns:atom="http://www.w3.org/2005/Atom"
           xmlns:lom="http://lockssomatic.info/SWORD2">
    <atom:category scheme="http://purl.org/net/sword/terms/state"
                   term="inProgress"
                   label="State">
        The deposit has been sent to LOCKSS.
    </atom:category>
    <atom:entry>
        <atom:category scheme="http://purl.org/net/sword/terms"
                       term="http://purl.org/net/sword/terms/originalDeposit"
                       label="Original Deposit"/>
        <sword:depositedOn>Tuesday</sword:depositedOn>
        <lom:agreement>0</lom:agreement>
        <sword:originalDeposit href="http://path/to/deposit" />
    </atom:entry>
</atom:feed>
ENDXML;
    }

    protected function fixtures() : array {
        return [
            DepositFixtures::class,
            JournalFixtures::class,
        ];
    }

    public function testSanity() : void {
        $this->assertInstanceOf(SwordClient::class, $this->swordClient);
    }

    public function testServiceDocument() : void {
        $mock = new MockHandler([
            new Response(200, [], $this->serviceDocumentData()),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $guzzle = new Client(['handler' => $stack]);
        $this->swordClient->setClient($guzzle);
        $sd = $this->swordClient->serviceDocument();
        $this->assertInstanceOf(ServiceDocument::class, $sd);

        $this->assertSame(1, count($container));
        $transaction = $container[0];
        $this->assertSame('GET', $transaction['request']->getMethod());
        $this->assertSame(
            ['9AE14D70-B799-473C-8072-983310ECB0E1'],
            $transaction['request']->getHeader('On-Behalf-Of')
        );
    }

    public function testServiceDocumentException() : void {
        $this->expectException(Exception::class);
        $mock = new MockHandler([
            new Response(400, []),
        ]);
        $stack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $stack]);
        $this->swordClient->setClient($guzzle);
        $sd = $this->swordClient->serviceDocument();
    }

    public function testServiceDocumentGenericException() : void {
        $this->expectException(Exception::class);
        $mock = new MockHandler([
            new Exception('FAILURE WILL ROBINSON'),
        ]);
        $stack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $stack]);
        $this->swordClient->setClient($guzzle);
        $sd = $this->swordClient->serviceDocument();
    }

    public function testServiceDocumentExceptionResponse() : void {
        $this->expectException(Exception::class);
        $mock = new MockHandler([
            new Response(400, [], 'NO NO'),
        ]);
        $stack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $stack]);
        $this->swordClient->setClient($guzzle);
        $sd = $this->swordClient->serviceDocument();
    }

    public function testCreateDeposit() : void {
        $mock = new MockHandler([
            new Response(200, [], $this->serviceDocumentData()),
            new Response(201, ['Location' => 'http://example.com'], $this->createDepositResponse()),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $guzzle = new Client(['handler' => $stack]);
        $this->swordClient->setClient($guzzle);
        $deposit = $this->getReference('deposit.1');
        $result = $this->swordClient->createDeposit($deposit);
        $this->assertTrue($result);

        $this->assertSame(2, count($container));
        $request = $container[1]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            'http://localhost/lom2/web/app_dev.php/api/sword/2.0/col-iri/29125DE2-E622-416C-93EB-E887B2A3126C',
            (string) $request->getUri()
        );

        $this->assertSame('http://example.com', $deposit->getDepositReceipt());
    }

    public function testCreateDepositException() : void {
        $this->expectException(Exception::class);
        $mock = new MockHandler([
            new Response(200, [], $this->serviceDocumentData()),
            new Response(401, [], 'NOT AUTHORIZED'),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $guzzle = new Client(['handler' => $stack]);
        $this->swordClient->setClient($guzzle);
        $deposit = $this->getReference('deposit.1');
        $result = $this->swordClient->createDeposit($deposit);
    }

    public function testCreateDepositGenericException() : void {
        $this->expectException(Exception::class);
        $mock = new MockHandler([
            new Response(200, [], $this->serviceDocumentData()),
            new Exception('NO FUN FOR YOU'),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $guzzle = new Client(['handler' => $stack]);
        $this->swordClient->setClient($guzzle);
        $deposit = $this->getReference('deposit.1');
        $result = $this->swordClient->createDeposit($deposit);
        $this->assertContains('NO FUN FOR YOU', $deposit->getErrorLog("\n"));
    }

    public function testGetDepositReceiptNull() : void {
        $mock = new MockHandler([
            new Response(200, [], $this->receiptData()),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $guzzle = new Client(['handler' => $stack]);
        $this->swordClient->setClient($guzzle);
        $deposit = $this->getReference('deposit.1');
        $deposit->setDepositReceipt(null);
        $result = $this->swordClient->receipt($deposit);
        $this->assertNull($result);
        $this->assertSame(0, count($container));
    }

    public function testGetDepositReceipt() : void {
        $mock = new MockHandler([
            new Response(200, [], $this->receiptData()),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $guzzle = new Client(['handler' => $stack]);
        $this->swordClient->setClient($guzzle);
        $deposit = $this->getReference('deposit.1');
        $result = $this->swordClient->receipt($deposit);
        $this->assertInstanceOf(SimpleXMLElement::class, $result);

        $this->assertSame(1, count($container));
        $request = $container[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://example.com/receipt/1', (string) $request->getUri());
    }

    public function testGetDepositStatement() : void {
        $mock = new MockHandler([
            new Response(200, [], $this->receiptData()),
            new Response(200, [], $this->statementData()),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $guzzle = new Client(['handler' => $stack]);
        $this->swordClient->setClient($guzzle);
        $deposit = $this->getReference('deposit.1');
        $result = $this->swordClient->statement($deposit);
        $this->assertInstanceOf(SimpleXMLElement::class, $result);

        $this->assertSame(2, count($container));
        $request = $container[1]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://path/to/statement', (string) $request->getUri());
    }

    public function testFetch() : void {
        $root = vfsStream::setup();

        $fp = $this->createMock(FilePaths::class);
        $fp->method('getRestoreFile')->willReturn('vfs://root/path.zip');
        $this->swordClient->setFilePaths($fp);

        $mock = new MockHandler([
            new Response(200, [], $this->receiptData()),
            new Response(200, [], $this->statementData()),
            new Response(200, [], 'some random content.'),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $guzzle = new Client(['handler' => $stack]);
        $this->swordClient->setClient($guzzle);
        $deposit = $this->getReference('deposit.1');
        $result = $this->swordClient->fetch($deposit);
        $this->assertSame('vfs://root/path.zip', $result);

        $this->assertSame(3, count($container));
        $request = $container[2]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://path/to/deposit', (string) $request->getUri());
    }

    protected function setup() : void {
        parent::setUp();
        $this->swordClient = self::$container->get(SwordClient::class);
    }
}
