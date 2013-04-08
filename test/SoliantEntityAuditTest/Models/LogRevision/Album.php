<?php

namespace SoliantEntityAuditTest\Models\LogRevision;

use Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
    ;

class Album {

    private $id;
    private $artist;
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function getArtist()
    {
        return $this->artist;
    }

    public function setArtist($value)
    {
        $this->artist = $value;
        return $this;
    }

    public function setTitle($value)
    {
        $this->title = $value;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();

        $builder->addField('artist', 'string', array('nullable' => true));
        $builder->addField('title', 'string');
    }
}
