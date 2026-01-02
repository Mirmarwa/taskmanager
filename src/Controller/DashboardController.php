<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
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
public function adminDashboard(
    ProjectRepository $projectRepository,
    TaskRepository $taskRepository
): Response {
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $projects = $projectRepository->findAll();

    $totalProjects = $projectRepository->count([]);
    $totalTasks = $taskRepository->count([]);
    $todoTasks = $taskRepository->count(['status' => 'todo']);
    $doneTasks = $taskRepository->count(['status' => 'done']);

    return $this->render('dashboard/admin.html.twig', [
        'projects' => $projects,
        'totalProjects' => $totalProjects,
        'totalTasks' => $totalTasks,
        'todoTasks' => $todoTasks,
        'doneTasks' => $doneTasks,
    ]);
}


    // 👔 DIRECTEUR / RESPONSABLE
    #[Route('/director', name: 'app_dashboard_director')]
public function directorDashboard(
    ProjectRepository $projectRepository,
    TaskRepository $taskRepository,
    UserRepository $userRepository
): Response {
    $this->denyAccessUnlessGranted('ROLE_DIRECTOR');

    // Totaux globaux
    $totalProjects = $projectRepository->count([]);
    $totalTasks = $taskRepository->count([]);
    $totalUsers = $userRepository->count([]);

    // Répartition des tâches
    $todo = $taskRepository->count(['status' => 'todo']);
    $doing = $taskRepository->count(['status' => 'doing']);
    $done = $taskRepository->count(['status' => 'done']);

    return $this->render('dashboard/director.html.twig', [
        'total_projects' => $totalProjects,
        'total_tasks' => $totalTasks,
        'total_users' => $totalUsers,
        'todo' => $todo,
        'doing' => $doing,
        'done' => $done,
    ]);
}

}
