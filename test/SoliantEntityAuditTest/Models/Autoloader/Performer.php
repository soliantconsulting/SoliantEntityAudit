<?php

namespace SoliantEntityAuditTest\Models\Autoloader;

use Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
    ;

class Performer {

    private $id;
    private $name;
    private $albums;

    public function getId()
    {
        return $this->id;
    }

    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAlbums()
    {
        return $this->albums;
    }

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
        $builder->addField('name', 'string');
        $builder->addOwningManyToMany('albums', 'SoliantEntityAuditTest\\Models\\Bootstrap\\Album', 'performers');
    }
}
