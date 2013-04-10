<?php

// This is just a stub so a new many to many can be
// discovered after bootstrapping

namespace SoliantEntityAuditTest\Models\LogRevision;

use Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
    , Doctrine\Common\Collections\ArrayCollection
    ;

class SingleCoverArt {

    private $id;
    private $url;
    private $songs;

    public function getId()
    {
        return $this->id;
    }

    public function setUrl($value)
    {
        $this->url = $value;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getSongs()
    {
        if (!$this->songs)
            $this->songs = new ArrayCollection();

        return $this->songs;
    }

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
        $builder->addField('url', 'string');
        $builder->addOwningManyToMany('songs', 'Song', 'singleCoverArt');
    }
}
