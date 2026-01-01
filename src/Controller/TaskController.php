<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Comment;
use App\Form\TaskType;
use App\Form\CommentType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/task')]
final class TaskController extends AbstractController
{
    /**
     * LISTE DES TÂCHES
     * URL : /task
     */
    #[Route('', name: 'app_task_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
{
    return $this->render('task/index.html.twig', [
        'tasks' => $taskRepository->findBy([
            'assignedTo' => $this->getUser()
        ]),
    ]);
}


    /**
     * CRÉER UNE TÂCHE
     * URL : /task/new
     */
    #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $task = new Task();

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

    $task->setAssignedTo($this->getUser()); // 🔴 LIGNE CLÉ

    $em->persist($task);
    $em->flush();

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
public function edit(Request $request, Task $task, EntityManagerInterface $em): Response
{
    $form = $this->createForm(TaskType::class, $task);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
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
     * AFFICHER UNE TÂCHE + COMMENTAIRES
     * URL : /task/{id}
     */
    #[Route('/{id}', name: 'app_task_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setTask($task);
            $comment->setAuthor($this->getUser());
            $comment->setCreatedAt(new \DateTimeImmutable());

            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Commentaire ajouté');

            return $this->redirectToRoute('app_task_show', [
                'id' => $task->getId(),
            ]);
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
#[Route('/{id}', name: 'app_task_delete', methods: ['POST'])]
public function delete(Request $request, Task $task, EntityManagerInterface $em): Response
{
    if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
        $em->remove($task);
        $em->flush();

        $this->addFlash('success', 'Tâche supprimée avec succès');
    }

    return $this->redirectToRoute('app_task_index');
}

}
