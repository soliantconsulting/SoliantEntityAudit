<?php

namespace SoliantEntityAudit\Service;

use Zend\View\Helper\AbstractHelper
    , SoliantEntityAudit\Entity\AbstractAudit
    ;

class AuditService extends AbstractHelper
{
    private $comment;

    /**
     * To add a comment to a revision fetch this object before flushing
     * and set the comment.  The comment will be fetched by the revision
     * and reset after reading
     */
    public function getComment()
    {
        $comment = $this->comment;
        $this->comment = null;

        return $comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    public function getEntityValues($entity) {
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();

        $metadata = $em->getClassMetadata(get_class($entity));
        $fields = $metadata->getFieldNames();

#        $targetEntityMetadata = $em->getClassMetadata($entity->getRevisionEntity()->getTargetEntity());

        $return = array();
        foreach ($fields AS $fieldName) {
            $return[$fieldName] = $metadata->getFieldValue($entity, $fieldName);
        }

        ksort($return);

        return $return;
    }


    public function getEntityAssociations(AbstractAudit $entity)
    {
        $associations = array();
        foreach ($entity->getAssociationMappings() as $mapping) {
            $associations[$mapping['fieldName']] = $mapping;
        }

        return $associations;
    }

    /**
     * Find a mapping to the given field for 1:many
     */
    public function getAssociationRevisionEntity(AbstractAudit $entity, $field, $value) {
        $em = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();

        foreach ($entity->getAssociationMappings() as $mapping) {

            if ($mapping['fieldName'] == $field) {
                $qb = $em->createQueryBuilder();
                $qb->select('revisionEntity')
                    ->from('SoliantEntityAudit\\Entity\\RevisionEntity', 'revisionEntity')
                    ->innerJoin('revisionEntity.revision', 'revision')
                    ->andWhere('revisionEntity.targetEntityClass = ?1')
                    ->andWhere('revisionEntity.entityKeys = ?2')
                    ->andWhere('revision.timestamp <= ?3')
                    ->setParameter(1, $mapping['targetEntity'])
                    ->setParameter(2, serialize(array('id' => $value)))
                    ->setParameter(3, $entity->getRevisionEntity()->getRevision()->getTimestamp())
                    ->orderBy('revision.timestamp', 'DESC')
                    ->setMaxResults(1);

                $result = $qb->getQuery()->getResult();

                if ($result) return reset($result);
            }

        }

        return;
    }

    public function getEntityIdentifierValues($entity, $cleanRevisionEntity = false)
    {
        $entityManager = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();
        $metadataFactory = $entityManager->getMetadataFactory();

        // Get entity metadata - Audited entities will always have composite keys
        $metadata = $metadataFactory->getMetadataFor(get_class($entity));
        $values = $metadata->getIdentifierValues($entity);

        if ($cleanRevisionEntity and $values['revisionEntity'] instanceof \SoliantEntityAudit\Entity\RevisionEntity) {
            unset($values['revisionEntity']);
        }

        foreach ($values as $key => $val) {
            if (gettype($val) == 'object') $values[$key] = $val->getId();
        }

        return $values;
    }

    /**
     * Pass an audited entity or the audit entity
     * and return a collection of RevisionEntity s
     * for that record
     */
    public function getRevisionEntities($entity)
    {
        $entityManager = \SoliantEntityAudit\Module::getModuleOptions()->getEntityManager();

        if (gettype($entity) != 'string' and in_array(get_class($entity), array_keys(\SoliantEntityAudit\Module::getModuleOptions()->getAuditedClassNames()))) {
            $auditEntityClass = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', get_class($entity));
            $identifiers = $this->getEntityIdentifierValues($entity);
        } elseif ($entity instanceof AbstractAudit) {
            $auditEntityClass = get_class($entity);
            $identifiers = $this->getEntityIdentifierValues($entity, true);
        } else {
            $auditEntityClass = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $entity);
        }

        $search = array('auditEntityClass' => $auditEntityClass);
        if (isset($identifiers)) $search['entityKeys'] = serialize($identifiers);

        return $entityManager->getRepository('SoliantEntityAudit\\Entity\\RevisionEntity')
            ->findBy($search, array('id' => 'DESC'));
    }
}
