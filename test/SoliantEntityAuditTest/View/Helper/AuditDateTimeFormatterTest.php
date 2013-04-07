<?php

namespace SoliantEntityAuditTest\View\Helper;

use SoliantEntityAuditTest\Bootstrap
    ;

class AuditDateTimeFormatterTest extends \PHPUnit_Framework_TestCase
{
    public function testFormatter()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $helper = $sm->get('viewhelpermanager')->get('auditDateTimeFormatter');

        $now = new \DateTime();
        $helper->setDateTimeFormat('U');
        $this->assertEquals($helper($now), $now->format('U'));
    }
}
