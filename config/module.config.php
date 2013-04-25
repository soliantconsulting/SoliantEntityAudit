<?php

namespace SoliantEntityAudit;

return array(
    'doctrine' => array(
        'driver' => array(
            __NAMESPACE__ . '_driver' => array(
                'class' => 'SoliantEntityAudit\Mapping\Driver\AuditDriver',
            ),

            'orm_default' => array(
                'drivers' => array(
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver',
                ),
            ),
        ),

        'eventmanager' => array(
            'orm_default' => array(
                'subscribers' => array(
                    'SoliantEntityAudit\EventListener\LogRevision',
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'audit' => 'SoliantEntityAudit\Controller\IndexController'
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),

    'view_helpers' => array(
        'invokables' => array(
            'auditCurrentRevisionEntity' => 'SoliantEntityAudit\View\Helper\CurrentRevisionEntity',
            'auditEntityOptions' => 'SoliantEntityAudit\View\Helper\EntityOptions',

            'auditRevisionEntityLink' => 'SoliantEntityAudit\View\Helper\RevisionEntityLink',

            'auditRevisionPaginator' => 'SoliantEntityAudit\View\Helper\RevisionPaginator',
            'auditRevisionEntityPaginator' => 'SoliantEntityAudit\View\Helper\RevisionEntityPaginator',
            'auditAssociationSourcePaginator' => 'SoliantEntityAudit\View\Helper\AssociationSourcePaginator',
            'auditAssociationTargetPaginator' => 'SoliantEntityAudit\View\Helper\AssociationTargetPaginator',
            'auditOneToManyPaginator' => 'SoliantEntityAudit\View\Helper\OneToManyPaginator',
        ),
    ),

    'router' => array(
        'routes' => array(
            'audit' => array(
                'type' => 'Literal',
                'priority' => 1000,
                'options' => array(
                    'route' => '/audit',
                    'defaults' => array(
                        'controller' => 'audit',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'page' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:page]',
                            'constraints' => array(
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'index',
                                'page' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'user' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/user[/:userId][/:page]',
                            'constraints' => array(
                                'userId' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'user',
                            ),
                        ),
                    ),

                    'revisions' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/revision[/:revisionId]',
                            'constraints' => array(
                                'revisionId' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'revision',
                                'revisionId' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'revision-entity' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/revision-entity[/:revisionEntityId][/:page]',
                            'constraints' => array(
                                'revisionEntityId' => '[0-9]*',
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'revisionEntity',
                            ),
                        ),
                    ),
                    'one-to-many' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/one-to-many[/:revisionEntityId][/:joinTable][/:mappedBy][/:page]',
                            'constraints' => array(
                                'revisionEntityId' => '[0-9]*',
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'one-to-many',
                            ),
                        ),
                    ),
                    'association-target' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/association-target[/:revisionEntityId][/:joinTable][/:page]',
                            'constraints' => array(
                                'revisionEntityId' => '[0-9]*',
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'association-target',
                            ),
                        ),
                    ),
                    'association-source' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/association-source[/:revisionEntityId][/:joinTable][/:page]',
                            'constraints' => array(
                                'revisionEntityId' => '[0-9]*',
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'association-source',
                            ),
                        ),
                    ),
                    'entity' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/entity[/:entityClass][/:page]',
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'entity',
                            ),
                        ),
                    ),
                    'compare' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/audit/compare',
                            'defaults' => array(
                                'controller' => 'audit',
                                'action' => 'compare',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);
