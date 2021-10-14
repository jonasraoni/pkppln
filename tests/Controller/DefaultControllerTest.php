<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use App\DataFixtures\DepositFixtures;
use Nines\UserBundle\DataFixtures\UserFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class DefaultControllerTest extends ControllerBaseCase {
    protected function fixtures() : array {
        return [
            DepositFixtures::class,
            UserFixtures::class,
        ];
    }

    public function testAnonIndex() : void {

        $crawler = $this->client->request('GET', '/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testUserIndex() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminIndex() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonDepositDepositSearch() : void {

        $formCrawler = $this->client->request('GET', '/deposit_search');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    public function testUserDepositSearch() : void {
        $this->login('user.user');
        $formCrawler = $this->client->request('GET', '/deposit_search');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $form = $formCrawler->selectButton('Search')->form([
            'q' => '4F37',
        ]);
        $this->client->submit($form);
        $this->assertSame(200, $this->client->getresponse()->getStatusCode());
        $this->assertSame(1, $this->client->getCrawler()->filter('td:contains("978EA2B4-01DB-4F37-BD74-871DDBE71BF5")')->count());
    }

    public function testAdminDepositSearch() : void {
        $this->login('user.admin');
        $formCrawler = $this->client->request('GET', '/deposit_search');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $form = $formCrawler->selectButton('Search')->form([
            'q' => '4F37',
        ]);
        $this->client->submit($form);
        $this->assertSame(200, $this->client->getresponse()->getStatusCode());
        $this->assertSame(1, $this->client->getCrawler()->filter('td:contains("978EA2B4-01DB-4F37-BD74-871DDBE71BF5")')->count());
    }

    public function testFetchActionJournalMismatch() : void {

        $crawler = $this->client->request('GET', '/fetch/44428B12-CDC4-453E-8157-319004CD8CE6/F93A8108-B705-4763-A592-B718B00BD4EA.zip');
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Journal ID does not match', $this->client->getResponse()->getContent());
    }

    public function testFetchActionDeposit404() : void {

        $crawler = $this->client->request('GET', '/fetch/04F2C06E-35B8-43C1-B60C-1934271B0B7E/F93A8108-B705-4763-A592-B718B00BD4EA.zip');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Deposit not found.', $this->client->getResponse()->getContent());
    }

    public function testPermissionAction() : void {

        $crawler = $this->client->request('GET', '/permission');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('LOCKSS system has permission', $this->client->getResponse()->getContent());
    }
}
