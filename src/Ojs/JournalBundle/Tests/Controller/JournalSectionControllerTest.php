<?php

namespace Ojs\JournalBundle\Tests\Controller;

use Ojs\CoreBundle\Tests\BaseTestCase;

class SectionControllerTest extends BaseTestCase
{

    public function testStatus()
    {
        $this->logIn('admin', array('ROLE_SUPER_ADMIN'));

        $this->client->request('GET', '/manager/journal/section/1/show');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
}
