<?php

namespace App\EventSubscriber;

use App\Entity\Notification;
use App\Services\PushNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist)]
final class NotificationPushSubscriber
{
    public function __construct(
        private readonly PushNotificationService $pushNotificationService,
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Notification) {
            return;
        }

        $this->pushNotificationService->dispatchNotification($entity);
    }
}
