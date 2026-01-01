<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User; // Ajouté pour $user
use App\Form\AddMemberType;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Task;


#[Route('/project')]
final class ProjectController extends AbstractController
{
    #[Route('', name: 'app_project_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): Response
    {
        return $this->render('project/index.html.twig', [
            'projects' => $projectRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projet créé avec succès !');

            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
public function show(Project $project, EntityManagerInterface $em): Response
{
    $tasks = $em->getRepository(Task::class)
        ->findBy(['project' => $project]);

    return $this->render('project/show.html.twig', [
        'project' => $project,
        'tasks' => $tasks,
    ]);
}


    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Projet modifié avec succès !');

            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->request->get('_token'))) {
            $entityManager->remove($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projet supprimé avec succès.');
        }

        return $this->redirectToRoute('app_project_index');
    }

    #[Route('/{id}/add-member', name: 'app_project_add_member', methods: ['GET', 'POST'])]
    public function addMember(Request $request, Project $project, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AddMemberType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->get('user')->getData();

            // Vérifie si déjà membre sans getUsers()
            $exists = $em->createQueryBuilder()
                ->select('COUNT(u.id)')
                ->from(User::class, 'u')
                ->innerJoin('u.projects', 'p') // Assure-toi que la relation existe dans l'entité User
                ->where('p.id = :projectId')
                ->andWhere('u.id = :userId')
                ->setParameter('projectId', $project->getId())
                ->setParameter('userId', $user->getId())
                ->getQuery()
                ->getSingleScalarResult();

            if ($exists == 0) {
                $user->addProject($project); // Utilise addProject() depuis l'entité User (relation inverse)
                $em->flush();

                $this->addFlash('success', 'Membre ajouté au projet !');
            } else {
                $this->addFlash('info', 'Cet utilisateur est déjà membre du projet.');
            }

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/add_member.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }
}