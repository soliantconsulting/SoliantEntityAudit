<?php

namespace SoliantEntityAuditTest\View\Helper;

use SoliantEntityAuditTest\Bootstrap
    , SoliantEntityAuditTest\Models\Bootstrap\Album
    ;

class EntityOptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testRevisionsAreReturnedInPaginator()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $helper = $sm->get('viewhelpermanager')->get('auditEntityOptions');

        $helper('SoliantEntityAuditTest\Models\Bootstrap\Song');
        $helper();
    }
}
