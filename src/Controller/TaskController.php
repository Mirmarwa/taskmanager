<?php

namespace App\Controller;

use App\Entity\Comment; // ← AJOUTÉ
use App\Entity\Task;
use App\Form\CommentType; // ← AJOUTÉ
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/task')]
final class TaskController extends AbstractController
{
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

            $this->addFlash('success', 'Commentaire ajouté !');

            return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
        }

        // SOLUTION TEMPORAIRE pour "getComments undefined"
        // On récupère les commentaires manuellement via une requête Doctrine
        $comments = $em->getRepository(Comment::class)->findBy(['task' => $task], ['createdAt' => 'ASC']);

        return $this->render('task/show.html.twig', [
            'task' => $task,
            'comments' => $comments, // ← On utilise la requête manuelle au lieu de $task->getComments()
            'form' => $form->createView(),
        ]);
    }
}