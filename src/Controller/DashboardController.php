<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $taskRepo = $em->getRepository(Task::class);

        $todo = count($taskRepo->findBy(['status' => 'À faire']));
        $doing = count($taskRepo->findBy(['status' => 'En cours']));
        $done = count($taskRepo->findBy(['status' => 'Terminé']));

        return $this->render('dashboard/index.html.twig', [
            'todo' => $todo,
            'doing' => $doing,
            'done' => $done,
        ]);
    }
}
