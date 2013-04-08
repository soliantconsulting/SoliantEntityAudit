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
    private $collectionUpdates;
    private $inAuditTransaction;

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

    public function addCollectionUpdate($collection)
    {
        $this->collectionUpdates[] = $collection;
    }

    public function getCollectionUpdates()
    {
        if (!$this->collectionUpdates) $this->collectionUpdates = array();
        return $this->collectionUpdates;
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

        echo "revision comment set to $comment\n\n";

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

            // Set values to getId for classes
            if ($value instanceof \StdClass and method_exists($value, 'getId')) {
                $value = $value->getId();
            }

            // If a property is an object we probably are not mapping that to
            // a field.  Do no special handing...
#            if (gettype($value) == 'object') {
##                $value = $value->getId();
#            }
            $properties[$property->getName()] = $value;
        }

        return $properties;
    }

    private function auditEntity($entity, $revisionType)
    {
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

        return array($auditEntity);
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

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledCollectionDeletions() AS $col) {
            die('deletion.  If you reached this you should just try to figure out the next foreach block first.');
            print_r($col);die();
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates() AS $collectionToUpdate) {
            if ($collectionToUpdate instanceof PersistentCollection) {
                $this->addCollectionUpdate($collectionToUpdate);
            }
        }

        $this->setEntities($entities);
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->getEntities() and !$this->getInAuditTransaction()) {
            $this->setInAuditTransaction(true);
            $entityManager = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();
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

                // Audit complete collections as a snapshot of an updated entity
                # FIXME: many to many data are not populated in audit
                foreach ($this->getCollectionUpdates() as $collection) {
                    foreach ($this->getClassProperties($entity) as $key => $value) {
                        if ($value instanceof PersistentCollection) {
                            die('persistent collection found');
                            continue;
                        }
                    }
                }

                $entityManager->persist($entity);
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
