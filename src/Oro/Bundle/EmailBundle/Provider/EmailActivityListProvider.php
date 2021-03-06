<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class EmailActivityListProvider implements ActivityListProviderInterface, CommentProviderInterface
{
    const ACTIVITY_CLASS = 'Oro\Bundle\EmailBundle\Entity\Email';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ServiceLink */
    protected $doctrineRegistryLink;

    /** @var ServiceLink */
    protected $nameFormatterLink;

    /** @var Router */
    protected $router;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ServiceLink    $doctrineRegistryLink
     * @param ServiceLink    $nameFormatterLink
     * @param Router         $router
     * @param ConfigManager  $configManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ServiceLink $doctrineRegistryLink,
        ServiceLink $nameFormatterLink,
        Router $router,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper       = $doctrineHelper;
        $this->doctrineRegistryLink = $doctrineRegistryLink;
        $this->nameFormatterLink    = $nameFormatterLink;
        $this->router               = $router;
        $this->configManager        = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget(ConfigIdInterface $configId, ConfigManager $configManager)
    {
        $provider = $configManager->getProvider('activity');

        return $provider->hasConfigById($configId)
            && $provider->getConfigById($configId)->has('activities')
            && in_array(self::ACTIVITY_CLASS, $provider->getConfigById($configId)->get('activities'));
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        return [
            'itemView' => 'oro_email_view',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityClass()
    {
        return self::ACTIVITY_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject($entity)
    {
        /** @var $entity Email */
        return $entity->getSubject();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization($activityEntity)
    {
        /** @var $activityEntity Email */
        return $activityEntity->getFromEmailAddress()->getOwner()->getOrganization();
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityListEntity)
    {
        /** @var Email $email */
        $email = $this->doctrineRegistryLink->getService()
            ->getRepository($activityListEntity->getRelatedActivityClass())
            ->find($activityListEntity->getRelatedActivityId());

        $data = [
            'ownerName' => $email->getFromName(),
            'ownerLink' => null
        ];

        if ($email->getFromEmailAddress()->hasOwner()) {
            $owner             = $email->getFromEmailAddress()->getOwner();
            $data['ownerName'] = $this->nameFormatterLink->getService()->format($owner);

            $route = $this->configManager->getEntityMetadata(ClassUtils::getClass($owner))
                ->getRoute('view');
            if (null !== $route) {
                $id                = $this->doctrineHelper->getSingleEntityIdentifier($owner);
                $data['ownerLink'] = $this->router->generate($route, ['id' => $id]);
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'OroEmailBundle:Email:js/activityItemTemplate.js.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($entity)
    {
        return $this->doctrineHelper->getEntityClass($entity) == self::ACTIVITY_CLASS
            && $entity->getFromEmailAddress()->hasOwner();
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntities($entity)
    {
        return $entity->getActivityTargetEntities();
    }

    /**
     * {@inheritdoc}
     */
    public function hasComments(ConfigManager $configManager, $entity)
    {
        $config = $configManager->getProvider('comment')->getConfig($entity);

        return $config->is('enabled');
    }
}
