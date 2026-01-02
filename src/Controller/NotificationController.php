<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;


#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'app_notifications')]
    public function index(NotificationRepository $repo): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $notifications = $repo->findBy(
            ['recipient' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }
    #[Route('/{id}/read', name: 'app_notification_read', methods: ['GET'])]
public function read(
    Notification $notification,
    EntityManagerInterface $em
): Response {
    $notification->setIsRead(true);
    $em->flush();

    return $this->redirectToRoute('app_notifications');
}

}
