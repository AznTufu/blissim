<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    public function __construct(
        private ProductService $productService,
        private CommentRepository $commentRepository,
    ) {
    }

    #[Route('/', name: 'product_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $this->productService->getProducts(),
        ]);
    }

    #[Route('/product/{id}', name: 'product_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(int $id, Request $request): Response
    {
        $product = $this->productService->getProduct($id)
            ?? throw $this->createNotFoundException('Produit introuvable.');

        $comment = new Comment();
        $comment->setProductId($id);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commentRepository->save($comment);
            $this->addFlash('success', 'Commentaire ajouté.');

            return $this->redirectToRoute('product_show', ['id' => $id]);
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'comments' => $this->commentRepository->findByProduct($id),
            'commentForm' => $form,
        ]);
    }
}
