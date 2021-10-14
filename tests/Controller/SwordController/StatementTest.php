<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller\SwordController;

use App\Entity\Whitelist;

class StatementTest extends AbstractSwordTestCase {
    // journal not whitelisted
    public function testStatementNotWhitelisted() : void {
        $this->client->request('GET', '/api/sword/2.0/cont-iri/04F2C06E-35B8-43C1-B60C-1934271B0B7E/F93A8108-B705-4763-A592-B718B00BD4EA/state');
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Not authorized to request statements.', $this->client->getResponse()->getContent());
    }

    // requested journal uuid does not match deposit uuid.
    public function testStatementMismatch() : void {
        $this->client->request('GET', '/api/sword/2.0/cont-iri/44428B12-CDC4-453E-8157-319004CD8CE6/F93A8108-B705-4763-A592-B718B00BD4EA/state');
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Deposit does not belong to journal.', $this->client->getResponse()->getContent());
    }

    // journal uuid unknown.
    public function testStatementJournalNonFound() : void {
        $this->client->request('GET', '/api/sword/2.0/cont-iri/15827F1C-02BC-4FF2-8C86-D1F01DE8E98B/BFDC45E7-58A8-4C46-B194-E20E040F0BD7/state');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('object not found', $this->client->getResponse()->getContent());
    }

    // deposit uuid unknown.
    public function testStatementDepositNonFound() : void {
        $this->client->request('GET', '/api/sword/2.0/cont-iri/44428B12-CDC4-453E-8157-319004CD8CE6/BFDC45E7-58A8-4C46-B194-E20E040F0BD7/state');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('object not found', $this->client->getResponse()->getContent());
    }

    public function testStatement() : void {
        $whitelist = new Whitelist();
        $whitelist->setUuid($this->getReference('journal.1')->getUuid());
        $whitelist->setComment('b');
        $this->entityManager->persist($whitelist);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->request('GET', '/api/sword/2.0/cont-iri/04F2C06E-35B8-43C1-B60C-1934271B0B7E/4ECC5D8B-ECC9-435C-A072-6DCF198ACD6D/state');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $xml = $this->getXml($this->client);
        $this->assertSame('http://example.com/path/to/1.zip', $this->getXmlValue($xml, '//atom:content/text()'));
    }
}
