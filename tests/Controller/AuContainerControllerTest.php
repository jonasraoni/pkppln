<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\DataFixtures\AuContainerFixtures;
use App\DataFixtures\BlacklistFixtures;
use App\DataFixtures\DepositFixtures;
use App\DataFixtures\JournalFixtures;
use Nines\UserBundle\DataFixtures\UserFixtures;
use App\Tests\TestCase\BaseControllerTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuContainerControllerTest extends BaseControllerTestCase
{
    protected function fixtures(): array
    {
        return [
            UserFixtures::class,
            BlacklistFixtures::class,
            JournalFixtures::class,
            DepositFixtures::class,
            AuContainerFixtures::class,
        ];
    }

    public function testIndex(): void
    {
        $this->login(UserFixtures::USER);
        $this->client->request('GET', '/aucontainer/');
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Displaying 3 records of 3 total.', $response->getContent());
        $this->assertStringContainsString('2 (0 deposits/0kb)', $this->client->getResponse()->getContent());
    }

    protected function setUp(): void
    {
        parent::setUp();
    }
}
