<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    // 👤 UTILISATEUR STANDARD
    #[Route('', name: 'app_dashboard')]
    public function userDashboard(TaskRepository $taskRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $tasks = $taskRepository->findBy(['assignedTo' => $user]);

        $todo = count(array_filter($tasks, fn($t) => $t->getStatus() === 'todo'));
        $doing = count(array_filter($tasks, fn($t) => $t->getStatus() === 'doing'));
        $done = count(array_filter($tasks, fn($t) => $t->getStatus() === 'done'));

        return $this->render('dashboard/user.html.twig', [
            'todo' => $todo,
            'doing' => $doing,
            'done' => $done,
            'assigned_tasks' => $tasks,
        ]);
    }

    // 🛠️ ADMIN
    #[Route('/admin', name: 'app_dashboard_admin')]
    public function adminDashboard(ProjectRepository $projectRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('dashboard/admin.html.twig', [
            'projects' => $projectRepository->findAll(),
        ]);
    }

    // 👔 DIRECTEUR / RESPONSABLE
    #[Route('/director', name: 'app_dashboard_director')]
    public function directorDashboard(
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_DIRECTOR');

        return $this->render('dashboard/director.html.twig', [
            'total_projects' => count($projectRepository->findAll()),
            'total_tasks' => count($taskRepository->findAll()),
        ]);
    }
}
