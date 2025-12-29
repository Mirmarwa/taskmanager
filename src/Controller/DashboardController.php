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
    public function index(TaskRepository $taskRepository, ProjectRepository $projectRepository): Response
    {
        $user = $this->getUser();

        return $this->render('dashboard/index.html.twig', [
            'assigned_tasks' => $taskRepository->findBy(['assignee' => $user]),
            'active_projects' => $projectRepository->findActiveForUser($user), // à implémenter ou adapter
            'upcoming_deadlines' => $taskRepository->findUpcomingDeadlinesForUser($user),
        ]);
    }
}