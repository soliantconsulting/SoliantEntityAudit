<?php

namespace SoliantEntityAuditTest\Models\LogRevision;

use Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
    ;

class Song {

    private $id;
    private $title;
    private $album;
    private $singleCoverArt;

    public function getId()
    {
        return $this->id;
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

    public function getAlbum()
    {
        return $this->album;
    }

    public function setAlbum($value)
    {
        $this->album = $value;
        return $this;
    }

    public function getSingleCoverArt()
    {
        if (!$this->singleCoverArt)
            $this->singleCoverArt = new ArrayCollection();

        return $this->singleCoverArt;
    }

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
        $builder->addField('title', 'string');
        $builder->addManyToOne('album', 'Album', 'songs');
        $builder->addInverseManyToMany('singleCoverArt', 'SingleCoverArt', 'songs');
    }
}
