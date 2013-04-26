<?php

namespace SoliantEntityAudit\Entity;

use Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
    , Zend\Code\Reflection\ClassReflection;
    ;

class RevisionEntity
{
    private $id;

    // Foreign key to the revision
    private $revision;

    // An array of primary keys
    private $entityKeys;

    // The name of the audit entity
    private $auditEntityClass;

    // The name of the entity which is audited
    private $targetEntityClass;

    // The type of action, INS, UPD, DEL
    private $revisionType;

    // Fetched from entity::getAuditTitle() if exists
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function setRevision(Revision $revision)
    {
        $this->revision = $revision;
        return $this;
    }

    public function getAuditEntityClass()
    {
        return $this->auditEntityClass;
    }

    public function setAuditEntityClass($value)
    {
        $this->auditEntityClass = $value;
        return $this;
    }

    public function getRevision()
    {
        return $this->revision;
    }

    public function setTargetEntityClass($value)
    {
        $this->targetEntityClass = $value;
        return $this;
    }

    public function getTargetEntityClass()
    {
        return $this->targetEntityClass;
    }

    public function getEntityKeys()
    {
        return unserialize($this->entityKeys);
    }

    public function setEntityKeys($value)
    {
        unset($value['revisionEntity']);

        $this->entityKeys = serialize($value);
    }

    public function getRevisionType()
    {
        return $this->revisionType;
    }

    public function setRevisionType($value)
    {
        $this->revisionType = $value;
        return $this;
    }

    public function setAuditEntity(AbstractAudit $entity)
    {
        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();

        $auditService = $moduleOptions->getAuditService();
        $identifiers = $auditService->getEntityIdentifierValues($entity);

        $this->setAuditEntityClass(get_class($entity));
        $this->setTargetEntityClass($entity->getAuditedEntityClass());
        $this->setEntityKeys($identifiers);

        return $this;
    }

    public function getAuditEntity()
    {
        $entityManager = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();

        return $entityManager->getRepository($this->getAuditEntityClass())->findOneBy(array('revisionEntity' => $this));
    }

    public function getTargetEntity()
    {
        $entityManager = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();

        return $entityManager->getRepository(
            $entityManager
                ->getRepository($this->getAuditEntityClass())
                    ->findOneBy($this->getEntityKeys())->getAuditedEntityClass()
            )->findOneBy($this->getEntityKeys());
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($value)
    {
        $this->title = substr($value, 0, 256);

        return $this;
    }
}
