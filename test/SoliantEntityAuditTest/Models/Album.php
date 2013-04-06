<?php

namespace SoliantEntityAuditTest\Models;

use Doctrine\ORM\Mapping as ORM;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Type
 *
 * @ORM\HasLifecycleCallbacks
 */
class Album {

    private $id;
    private $artist;
    private $title;

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $metadata->mapField(array(
           'id' => true,
           'fieldName' => 'id',
           'type' => 'integer',
        ));

        $metadata->mapField(array(
           'fieldName' => 'artist',
           'type' => 'string'
        ));

        $metadata->mapField(array(
           'fieldName' => 'title',
           'type' => 'string'
        ));
    }
}
