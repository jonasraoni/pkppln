<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller\SwordController;

use App\Entity\Whitelist;
use Symfony\Component\HttpFoundation\Response;

class EditDepositTest extends AbstractSwordTestCase {
    private function getEditXml() {
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
    <id>urn:uuid:d38e7ecb-7d7e-408d-94b0-b00d434fdbd2</id>
    <updated>2016-04-22T12:35:48Z</updated>
    <pkp:content size="123" volume="2" issue="4" pubdate="2016-04-22"
		checksumType="SHA-1"
        checksumValue="55ca6286e3e4f4fba5d0448333fa99fc5a404a73">http://example.com/deposit/foo.zip
    </pkp:content>
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

    public function testEditDepositNotWhitelisted() : void {
        $depositCount = count($this->entityManager->getRepository('App:Deposit')->findAll());
        $this->client->request(
            'PUT',
            '/api/sword/2.0/cont-iri/04F2C06E-35B8-43C1-B60C-1934271B0B7E/F93A8108-B705-4763-A592-B718B00BD4EA/edit',
            [],
            [],
            [],
            $this->getEditXml()
        );
        $this->entityManager->clear();
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame($depositCount, count($this->entityManager->getRepository('App:Deposit')->findAll()));
    }

    public function testEditDepositDepositMissing() : void {
        $depositCount = count($this->entityManager->getRepository('App:Deposit')->findAll());
        $this->client->request(
            'PUT',
            '/api/sword/2.0/cont-iri/44428B12-CDC4-453E-8157-319004CD8CE6/c0a65967-32bd-4ee8-96de-c469743e563a/edit',
            [],
            [],
            [],
            $this->getEditXml()
        );
        $this->entityManager->clear();
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame($depositCount, count($this->entityManager->getRepository('App:Deposit')->findAll()));
    }

    public function testEditDepositJournalMismatch() : void {
        $depositCount = count($this->entityManager->getRepository('App:Deposit')->findAll());
        $this->client->request(
            'PUT',
            '/api/sword/2.0/cont-iri/44428B12-CDC4-453E-8157-319004CD8CE6/4ECC5D8B-ECC9-435C-A072-6DCF198ACD6D/edit',
            [],
            [],
            [],
            $this->getEditXml()
        );
        $this->entityManager->clear();
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame($depositCount, count($this->entityManager->getRepository('App:Deposit')->findAll()));
    }

    public function testEditDepositSuccess() : void {
        $whitelist = new Whitelist();
        $whitelist->setUuid($this->getReference('journal.1')->getUuid());
        $whitelist->setComment('b');
        $this->entityManager->persist($whitelist);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $depositCount = count($this->entityManager->getRepository('App:Deposit')->findAll());
        $this->client->request(
            'PUT',
            '/api/sword/2.0/cont-iri/04F2C06E-35B8-43C1-B60C-1934271B0B7E/F93A8108-B705-4763-A592-B718B00BD4EA/edit',
            [],
            [],
            [],
            $this->getEditXml()
        );
        $this->entityManager->clear();
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame($depositCount + 1, count($this->entityManager->getRepository('App:Deposit')->findAll()));

        $deposit = $this->entityManager->getRepository('App:Deposit')->findOneBy([
            'depositUuid' => strtoupper('d38e7ecb-7d7e-408d-94b0-b00d434fdbd2'),
        ]);
        $this->assertSame('55CA6286E3E4F4FBA5D0448333FA99FC5A404A73', $deposit->getChecksumValue());
        $this->assertSame('depositedByJournal', $deposit->getState());
    }
}
