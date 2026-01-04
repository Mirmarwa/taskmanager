<?php

namespace App\Controller;

use App\Entity\Workspace;
use App\Form\WorkspaceType;
use App\Repository\WorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/workspace')]
#[IsGranted('ROLE_USER')]
final class WorkspaceController extends AbstractController
{
    #[Route('', name: 'app_workspace_index', methods: ['GET'])]
    public function index(WorkspaceRepository $workspaceRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('workspace/index.html.twig', [
            'workspaces' => $workspaceRepository->findByOwner($user),
        ]);
    }

    #[Route('/new', name: 'app_workspace_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $workspace = new Workspace();
        $form = $this->createForm(WorkspaceType::class, $workspace);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $workspace->setOwner($user);

            $em->persist($workspace);
            $em->flush();

            return $this->redirectToRoute('app_workspace_index');
        }

        return $this->render('workspace/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_workspace_show', methods: ['GET'])]
    public function show(Workspace $workspace): Response
    {
        return $this->render('workspace/show.html.twig', [
            'workspace' => $workspace,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_workspace_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Workspace $workspace, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(WorkspaceType::class, $workspace);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_workspace_index');
        }

        return $this->render('workspace/edit.html.twig', [
            'workspace' => $workspace,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_workspace_delete', methods: ['POST'])]
    public function delete(Request $request, Workspace $workspace, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$workspace->getId(), $request->request->get('_token'))) {
            $entityManager->remove($workspace);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_workspace_index');
    }
}
