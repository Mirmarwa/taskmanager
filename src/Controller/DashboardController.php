<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
final class DashboardController extends AbstractController
{
#[Route('', name: 'app_dashboard')]
public function index(TaskRepository $taskRepository): Response
{
    $user = $this->getUser();

    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $tasks = $taskRepository->findBy(['assignedTo' => $user]);

    $todo = count(array_filter($tasks, fn($t) => $t->getStatus() === 'todo'));
    $doing = count(array_filter($tasks, fn($t) => $t->getStatus() === 'doing'));
    $done = count(array_filter($tasks, fn($t) => $t->getStatus() === 'done'));

    return $this->render('dashboard/index.html.twig', [
        'todo' => $todo,
        'doing' => $doing,
        'done' => $done,
    ]);
}


}
