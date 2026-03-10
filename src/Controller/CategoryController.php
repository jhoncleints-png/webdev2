<?php

namespace App\Controller;

    use App\Entity\Category;
    use App\Form\CategoryType;
    use App\Repository\CategoryRepository;
    use App\Service\ActivityLogger;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    #[Route('/category')]
    #[IsGranted('ROLE_ADMIN')]
    final class CategoryController extends AbstractController
    {
        public function __construct(
            private ActivityLogger $activityLogger
        ) {}

        #[Route(name: 'app_category_index', methods: ['GET'])]
        public function index(CategoryRepository $categoryRepository): Response
        {
            // No need for denyAccessUnlessGranted - class attribute protects it
            return $this->render('category/index.html.twig', [
                'categories' => $categoryRepository->findAll(),
            ]);
        }

        #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
        public function new(Request $request, EntityManagerInterface $entityManager): Response
        {
            $category = new Category();
            $form = $this->createForm(CategoryType::class, $category);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($category);
                $entityManager->flush();

                // Log CREATE_CATEGORY action
                $this->activityLogger->log(
                    $this->getUser(),
                    'CREATE_CATEGORY',
                    'Category',
                    $category->getId(),
                    "Created category: {$category->getName()} (ID: {$category->getId()})"
                );

                $this->addFlash('success', 'Category created successfully.');
                return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('category/new.html.twig', [
                'category' => $category,
                'form' => $form->createView(),
            ]);
        }

        #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
        public function show(Category $category): Response
        {
            return $this->render('category/show.html.twig', [
                'category' => $category,
            ]);
        }

        #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
        public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
        {
            $form = $this->createForm(CategoryType::class, $category);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->flush();

                // Log UPDATE_CATEGORY action
                $this->activityLogger->log(
                    $this->getUser(),
                    'UPDATE_CATEGORY',
                    'Category',
                    $category->getId(),
                    "Updated category: {$category->getName()} (ID: {$category->getId()})"
                );

                $this->addFlash('success', 'Category updated successfully.');
                return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('category/edit.html.twig', [
                'category' => $category,
                'form' => $form->createView(),
            ]);
        }

        #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
        public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
        {
            if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
                $categoryName = $category->getName();
                $categoryId = $category->getId();
                
                $entityManager->remove($category);
                $entityManager->flush();

                // Log DELETE_CATEGORY action
                $this->activityLogger->log(
                    $this->getUser(),
                    'DELETE_CATEGORY',
                    'Category',
                    $categoryId,
                    "Deleted category: {$categoryName} (ID: {$categoryId})"
                );

                $this->addFlash('success', 'Category deleted successfully.');
            }

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }
    }