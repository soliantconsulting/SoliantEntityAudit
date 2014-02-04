<?php

namespace SoliantEntityAudit\EventListener;

use Doctrine\Common\EventSubscriber
    , Doctrine\ORM\Events
    , Doctrine\ORM\Event\OnFlushEventArgs
    , Doctrine\ORM\Event\PostFlushEventArgs
    , SoliantEntityAudit\Entity\Revision as RevisionEntity
    , SoliantEntityAudit\Options\ModuleOptions
    , SoliantEntityAudit\Entity\RevisionEntity as RevisionEntityEntity
    , Zend\Code\Reflection\ClassReflection
    , Doctrine\ORM\PersistentCollection
    ;

class LogRevision implements EventSubscriber
{
    private $revision;
    private $entities;
    private $reexchangeEntities;
    private $collections;
    private $inAuditTransaction;
    private $many2many;

    public function getSubscribedEvents()
    {
        return array(Events::onFlush, Events::postFlush);
    }

    private function setEntities($entities)
    {
        if ($this->entities) return $this;
        $this->entities = $entities;

        return $this;
    }

    private function resetEntities()
    {
        $this->entities = array();
        return $this;
    }

    private function getEntities()
    {
        return $this->entities;
    }

    private function getReexchangeEntities()
    {
        if (!$this->reexchangeEntities) $this->reexchangeEntities = array();
        return $this->reexchangeEntities;
    }

    private function resetReexchangeEntities()
    {
        $this->reexchangeEntities = array();
    }

    private function addReexchangeEntity($entityMap)
    {
        $this->reexchangeEntities[] = $entityMap;
    }

    private function addRevisionEntity(RevisionEntityEntity $entity)
    {
        $this->revisionEntities[] = $entity;
    }

    private function resetRevisionEntities()
    {
        $this->revisionEntities = array();
    }

    private function getRevisionEntities()
    {
        return $this->revisionEntities;
    }

    public function addCollection($collection)
    {
        if (!$this->collections) $this->collections = array();
        if (in_array($collection, $this->collections, true)) return;
        $this->collections[] = $collection;
    }

    public function getCollections()
    {
        if (!$this->collections) $this->collections = array();
        return $this->collections;
    }

    public function setInAuditTransaction($setting)
    {
        $this->inAuditTransaction = $setting;
        return $this;
    }

    public function getInAuditTransaction()
    {
        return $this->inAuditTransaction;
    }

    private function getRevision()
    {
        return $this->revision;
    }

    private function resetRevision()
    {
        $this->revision = null;
        return $this;
    }

    // You must flush the revision for the compound audit key to work
    private function buildRevision()
    {
        if ($this->revision) return;

        $revision = new RevisionEntity();
        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();
        if ($moduleOptions->getUser()) $revision->setUser($moduleOptions->getUser());

        $comment = $moduleOptions->getAuditService()->getComment();
        $revision->setComment($comment);

        $this->revision = $revision;
    }

    // Reflect audited entity properties
    private function getClassProperties($entity)
    {
        $properties = array();

        $reflectedAuditedEntity = new ClassReflection($entity);

        // Get mapping from metadata

        foreach($reflectedAuditedEntity->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);

            // If a property is an object we probably are not mapping that to
            // a field.  Do no special handing...
            if ($value instanceof PersistentCollection) {
            }

            // Set values to getId for classes
            if (gettype($value) == 'object' and method_exists($value, 'getId')) {
                $value = $value->getId();
            }

            $properties[$property->getName()] = $value;
        }

        return $properties;
    }

    private function auditEntity($entity, $revisionType)
    {
        $auditEntities = array();

        $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();
        if (!in_array(get_class($entity), array_keys($moduleOptions->getAuditedClassNames())))
            return array();

        $auditEntityClass = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', get_class($entity));
        $auditEntity = new $auditEntityClass();
        $auditEntity->exchangeArray($this->getClassProperties($entity));

        $revisionEntity = new RevisionEntityEntity();
        $revisionEntity->setRevision($this->getRevision());
        $this->getRevision()->getRevisionEntities()->add($revisionEntity);
        $revisionEntity->setRevisionType($revisionType);
        if (method_exists($entity, '__toString'))
            $revisionEntity->setTitle((string)$entity);
        $this->addRevisionEntity($revisionEntity);

        $revisionEntitySetter = 'set' . $moduleOptions->getRevisionEntityFieldName();
        $auditEntity->$revisionEntitySetter($revisionEntity);

        // Re-exchange data after flush to map generated fields
        if ($revisionType ==  'INS' or $revisionType ==  'UPD') {
            $this->addReexchangeEntity(array(
                'auditEntity' => $auditEntity,
                'entity' => $entity,
                'revisionEntity' => $revisionEntity,
            ));
        } else {
            $revisionEntity->setAuditEntity($auditEntity);
        }

        $auditEntities[] = $auditEntity;

        // Map many to many
        foreach ($this->getClassProperties($entity) as $key => $value) {

            if ($value instanceof PersistentCollection) {
                if (!$this->many2many) $this->many2many = array();
                $this->many2many[] = array(
                    'revisionEntity' => $revisionEntity,
                    'collection' => $value,
                );
            }
        }

        return $auditEntities;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entities = array();

        $this->buildRevision();

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions() AS $entity) {
            $entities = array_merge($entities, $this->auditEntity($entity, 'INS'));
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityUpdates() AS $entity) {
            $entities = array_merge($entities, $this->auditEntity($entity, 'UPD'));
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityDeletions() AS $entity) {
            $entities = array_merge($entities, $this->auditEntity($entity, 'DEL'));
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledCollectionDeletions() AS $collectionToDelete) {
            if ($collectionToDelete instanceof PersistentCollection) {
                $this->addCollection($collectionToDelete);
            }
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates() AS $collectionToUpdate) {
            if ($collectionToUpdate instanceof PersistentCollection) {
                $this->addCollection($collectionToUpdate);
            }
        }

        $this->setEntities($entities);
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->getEntities() and !$this->getInAuditTransaction()) {
            $this->setInAuditTransaction(true);

            $moduleOptions = \SoliantEntityAudit\Module::getModuleOptions();
            $entityManager = $moduleOptions->getEntityManager();
            $entityManager->beginTransaction();

            // Insert entites will trigger key generation and must be
            // re-exchanged (delete entites go out of scope)
            foreach ($this->getReexchangeEntities() as $entityMap) {
                $entityMap['auditEntity']->exchangeArray($this->getClassProperties($entityMap['entity']));
                $entityMap['revisionEntity']->setAuditEntity($entityMap['auditEntity']);
            }

            // Flush revision and revisionEntities
            $entityManager->persist($this->getRevision());
            foreach ($this->getRevisionEntities() as $entity)
                $entityManager->persist($entity);
            $entityManager->flush();

            foreach ($this->getEntities() as $entity) {
                $entityManager->persist($entity);
            }

            // Persist many to many collections
            foreach ($this->getCollections() as $value) {
                $mapping = $value->getMapping();

                if (!$mapping['isOwningSide']) continue;

                $joinClassName = "SoliantEntityAudit\\Entity\\" . str_replace('\\', '_', $mapping['joinTable']['name']);
                $moduleOptions->addJoinClass($joinClassName, $mapping);

                foreach ($this->many2many as $map) {
                    if ($map['collection'] == $value)
                        $revisionEntity = $map['revisionEntity'];
                }

                foreach ($value->getSnapshot() as $element) {
                    $audit = new $joinClassName();

                    // Get current inverse revision entity
                    $revisionEntities = $entityManager->getRepository('SoliantEntityAudit\\Entity\\RevisionEntity')
                        ->findBy(array(
                            'targetEntityClass' => get_class($element),
                            'entityKeys' => serialize(array('id' => $element->getId())),
                        ), array('id' => 'DESC'), 1);

                    $inverseRevisionEntity = reset($revisionEntities);

                    if (!$inverseRevisionEntity) {
                        // No inverse revision entity found
                        continue;
                    }

                    $audit->setTargetRevisionEntity($revisionEntity);
                    $audit->setSourceRevisionEntity($inverseRevisionEntity);

                    $entityManager->persist($audit);
                }
            }

            $entityManager->flush();

            $entityManager->commit();
            $this->resetEntities();
            $this->resetReexchangeEntities();
            $this->resetRevision();
            $this->resetRevisionEntities();
            $this->setInAuditTransaction(false);
        }
    }
}
