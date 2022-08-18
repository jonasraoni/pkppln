<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use App\DataFixtures\DepositFixtures;
use App\DataFixtures\JournalFixtures;
use Nines\UserBundle\DataFixtures\UserFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class DepositControllerTest extends ControllerBaseCase {
    protected function fixtures() : array {
        return [
            UserFixtures::class,
            DepositFixtures::class,
            JournalFixtures::class,
        ];
    }

    public function testAnonIndex() : void {

        $crawler = $this->client->request('GET', '/journal/1/deposit/');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserIndex() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/journal/1/deposit/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminIndex() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/journal/1/deposit/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonShow() : void {

        $crawler = $this->client->request('GET', '/journal/1/deposit/1');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserShow() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/journal/1/deposit/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminShow() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/journal/1/deposit/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonSearch() : void {

        $formCrawler = $this->client->request('GET', '/journal/2/deposit/search');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    public function testUserSearch() : void {
        $this->login('user.user');
        $formCrawler = $this->client->request('GET', '/journal/2/deposit/search');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $form = $formCrawler->selectButton('Search')->form([
            'q' => 'A584',
        ]);
        $this->client->submit($form);
        $this->assertSame(200, $this->client->getresponse()->getStatusCode());
        $this->assertSame(1, $this->client->getCrawler()->filter('td:contains("92ED9A27-A584-4487-A3F9-997379FBA182")')->count());
    }

    public function testAdminSearch() : void {
        $this->login('user.admin');
        $formCrawler = $this->client->request('GET', '/journal/2/deposit/search');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $form = $formCrawler->selectButton('Search')->form([
            'q' => 'A584',
        ]);
        $this->client->submit($form);
        $this->assertSame(200, $this->client->getresponse()->getStatusCode());
        $this->assertSame(1, $this->client->getCrawler()->filter('td:contains("92ED9A27-A584-4487-A3F9-997379FBA182")')->count());
    }
}
