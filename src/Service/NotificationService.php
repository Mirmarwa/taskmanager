<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function notify(User $recipient, string $message): void
    {
        $notification = new Notification();
        $notification->setRecipient($recipient);
        $notification->setMessage($message);
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($notification);
        $this->em->flush();
    }
}
