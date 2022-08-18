<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller\SwordController;

class CreateDepositTest extends AbstractSwordTestCase {
    private function getDepositXml() {
        return <<<'ENDXML'
<entry
    xmlns="http://www.w3.org/2005/Atom"
    xmlns:dcterms="http://purl.org/dc/terms/"
    xmlns:pkp="http://pkp.sfu.ca/SWORD">
    <email>foo@example.com</email>
    <title>Test Data Journal of Testing</title>
    <pkp:journal_url>http://tdjt.example.com</pkp:journal_url>
    <pkp:publisherName>Publisher of Stuff</pkp:publisherName>
    <pkp:publisherUrl>http://publisher.example.com</pkp:publisherUrl>
    <pkp:issn>1234-1234</pkp:issn>
    <id>urn:uuid:5F5C84B1-80BF-4071-8D3F-057AA3184FC9</id>
    <updated>2016-04-22T12:35:48Z</updated>
    <pkp:content size="123" volume="2" issue="4" pubdate="2016-04-22"
		checksumType="SHA-1"
        checksumValue="d46c034ef54c36237b89d456968965432830a603">http://example.com/deposit/foo.zip</pkp:content>
    <pkp:license>
        <pkp:publishingMode>Open</pkp:publishingMode>
        <pkp:openAccessPolicy>OA GOOD</pkp:openAccessPolicy>
        <pkp:licenseUrl>http://example.com/license</pkp:licenseUrl>
        <pkp:copyrightNotice>Copyright ME</pkp:copyrightNotice>
        <pkp:copyrightBasis>ME</pkp:copyrightBasis>
        <pkp:copyrightHolder>MYSELF</pkp:copyrightHolder>
    </pkp:license>
</entry>
ENDXML;
    }

    public function testCreateDepositWhitelisted() : void {
        $depositCount = count($this->entityManager->getRepository('App:Deposit')->findAll());
        $this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/44428B12-CDC4-453E-8157-319004CD8CE6',
            [],
            [],
            [],
            $this->getDepositXml()
        );
        $response = $this->client->getResponse();
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('http://localhost/api/sword/2.0/cont-iri/44428B12-CDC4-453E-8157-319004CD8CE6/5F5C84B1-80BF-4071-8D3F-057AA3184FC9/state', $response->headers->get('Location'));
        $this->assertSame($depositCount + 1, count($this->entityManager->getRepository('App:Deposit')->findAll()));
        $xml = $this->getXml($this->client);
        $this->assertSame('depositedByJournal', $this->getXmlValue($xml, '//atom:category[@label="Processing State"]/@term'));
    }

    public function testCreateDepositNotWhitelisted() : void {
        $depositCount = count($this->entityManager->getRepository('App:Deposit')->findAll());
        $this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/04F2C06E-35B8-43C1-B60C-1934271B0B7E',
            [],
            [],
            [],
            $this->getDepositXml()
        );
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Not authorized to create deposits.', $this->client->getResponse()->getContent());
        $this->assertSame($depositCount, count($this->entityManager->getRepository('App:Deposit')->findAll()));
    }
}
