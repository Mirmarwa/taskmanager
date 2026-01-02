<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Comment;
use App\Form\TaskType;
use App\Repository\UserRepository;
use App\Form\CommentType;
use App\Repository\TaskRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/task')]
final class TaskController extends AbstractController
{
    /**
     * LISTE DES TÂCHES (par utilisateur + filtres)
     * URL : /task
     */
    #[Route('', name: 'app_task_index', methods: ['GET'])]
    public function index(Request $request, TaskRepository $taskRepository): Response
{
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $status = $request->query->get('status');
    $priority = $request->query->get('priority');

    $qb = $taskRepository->createQueryBuilder('t');

    if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTOR')) {
        $qb->andWhere('t.assignedTo = :user')
           ->setParameter('user', $user);
    }

    if ($status) {
        $qb->andWhere('t.status = :status')
           ->setParameter('status', $status);
    }

    if ($priority) {
        $qb->andWhere('t.priority = :priority')
           ->setParameter('priority', (int) $priority);
    }

    $tasks = $qb->getQuery()->getResult();

    return $this->render('task/index.html.twig', [
        'tasks' => $tasks,
        'status' => $status,
        'priority' => $priority,
    ]);
}


    /**
     * CRÉER UNE TÂCHE
     * URL : /task/new
     */
    #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): Response {
        $task = new Task();

        // Si la tâche est créée depuis un projet
        $projectId = $request->query->get('project');
        if ($projectId) {
            $project = $em->getRepository(Project::class)->find($projectId);
            if ($project) {
                $task->setProject($project);
            }
        }

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Assignation à l'utilisateur connecté
            $task->setAssignedTo($this->getUser());

            $em->persist($task);
            $em->flush();

            // 🔔 Notification assignation
            if ($task->getAssignedTo()) {
                $notificationService->notify(
                    $task->getAssignedTo(),
                    'Nouvelle tâche assignée : ' . $task->getTitle()
                );
            }

            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * MODIFIER UNE TÂCHE
     * URL : /task/{id}/edit
     */
    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Task $task,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): Response {
        $oldStatus = $task->getStatus();

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($oldStatus !== $task->getStatus() && $task->getAssignedTo()) {
                $notificationService->notify(
                    $task->getAssignedTo(),
                    'Le statut de la tâche "' . $task->getTitle() . '" a changé.'
                );
            }

            $em->flush();

            $this->addFlash('success', 'Tâche modifiée avec succès');
            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    /**
     * VUE KANBAN
     * URL : /task/kanban
     */
    #[Route('/kanban', name: 'app_task_kanban', methods: ['GET'])]
    public function kanban(TaskRepository $taskRepository): Response
    {
        $user = $this->getUser();
        $tasks = $taskRepository->findByAssignedUser($user);

        return $this->render('task/kanban.html.twig', [
            'todo' => array_filter($tasks, fn($t) => $t->getStatus() === 'todo'),
            'doing' => array_filter($tasks, fn($t) => $t->getStatus() === 'doing'),
            'done' => array_filter($tasks, fn($t) => $t->getStatus() === 'done'),
        ]);
    }

    /**
     * AFFICHER UNE TÂCHE + COMMENTAIRES
     * URL : /task/{id}
     */
    #[Route('/{id}', name: 'app_task_show', methods: ['GET', 'POST'])]
public function show(
    Request $request,
    Task $task,
    EntityManagerInterface $em,
    NotificationService $notificationService
): Response {
    $comment = new Comment();
    $form = $this->createForm(CommentType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        $comment->setTask($task);
        $comment->setAuthor($this->getUser());
        $comment->setCreatedAt(new \DateTimeImmutable());

        $em->persist($comment);

        // =========================
        // 🔔 Notification commentaire (assigné)
        // =========================
        if ($task->getAssignedTo() && $task->getAssignedTo() !== $this->getUser()) {
            $notificationService->notify(
                $task->getAssignedTo(),
                'Nouveau commentaire sur la tâche : ' . $task->getTitle()
            );
        }

        // =========================
        // 🔔 MENTIONS @email
        // =========================
        $content = $comment->getContent();

        preg_match_all('/@([\w\.\-]+@[\w\-]+\.[\w]+)/', $content, $matches);
        $mentionedEmails = array_unique($matches[1]);

        $userRepository = $em->getRepository(\App\Entity\User::class);

        foreach ($mentionedEmails as $email) {
            $mentionedUser = $userRepository->findOneBy(['email' => $email]);

            if ($mentionedUser && $mentionedUser !== $this->getUser()) {
                $notificationService->notify(
                    $mentionedUser,
                    'Vous avez été mentionné dans un commentaire sur la tâche : ' . $task->getTitle()
                );
            }
        }

        // =========================
        // 💾 Sauvegarde finale
        // =========================
        $em->flush();

        return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
    }

    $comments = $em->getRepository(Comment::class)
        ->findBy(['task' => $task], ['createdAt' => 'ASC']);

    return $this->render('task/show.html.twig', [
        'task' => $task,
        'comments' => $comments,
        'form' => $form->createView(),
    ]);
}


    /**
     * SUPPRIMER UNE TÂCHE
     * URL : /task/{id}
     */
    #[Route('/{id}/delete', name: 'app_task_delete', methods: ['POST'])]

    public function delete(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
        }

        return $this->redirectToRoute('app_task_index');
    }
}
