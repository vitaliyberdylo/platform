<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;

class ActivityListChangesListener
{
    /** @var ServiceLink */
    protected $securityFacadeLink;

    /**
     * @param ServiceLink $securityFacadeLink
     */
    public function __construct(ServiceLink $securityFacadeLink)
    {
        $this->securityFacadeLink = $securityFacadeLink;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$this->isActivityListEntity($entity)) {
            return;
        }

        /** @var ActivityList $entity */
        $this->setCreatedProperties($entity, $args->getEntityManager());
        $this->setUpdatedProperties($entity, $args->getEntityManager());
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$this->isActivityListEntity($entity)) {
            return;
        }

        /** @var ActivityList $entity */
        $this->setUpdatedProperties($entity, $args->getEntityManager(), true);
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    protected function isActivityListEntity($entity)
    {
        return $entity instanceof ActivityList;
    }

    /**
     * @param EntityManager $entityManager
     *
     * @return User|null
     */
    protected function getUser(EntityManager $entityManager)
    {
        /** @var User $user */
        $user = $this->securityFacadeLink->getService()->getLoggedUser();
        if ($user && $entityManager->getUnitOfWork()->getEntityState($user) == UnitOfWork::STATE_DETACHED) {
            $user = $entityManager->find('OroUserBundle:User', $user->getId());
        }

        return $user;
    }

    /**
     * @param ActivityList  $activityList
     * @param EntityManager $entityManager
     */
    protected function setCreatedProperties(ActivityList $activityList, EntityManager $entityManager)
    {
        $activityList->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $activityList->setOwner($this->getUser($entityManager));
    }

    /**
     * @param ActivityList  $activityList
     * @param EntityManager $entityManager
     * @param bool          $update
     */
    protected function setUpdatedProperties(ActivityList $activityList, EntityManager $entityManager, $update = false)
    {
        $newUpdatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $newUpdatedBy = $this->getUser($entityManager);

        $unitOfWork = $entityManager->getUnitOfWork();
        if ($update && $newUpdatedBy != $activityList->getEditor()) {
            $unitOfWork->propertyChanged($activityList, 'updatedAt', $activityList->getUpdatedAt(), $newUpdatedAt);
            $unitOfWork->propertyChanged($activityList, 'editor', $activityList->getEditor(), $newUpdatedBy);
        }

        $activityList->setUpdatedAt($newUpdatedAt);
        $activityList->setEditor($newUpdatedBy);
    }
}
