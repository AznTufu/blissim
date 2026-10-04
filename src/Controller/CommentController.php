<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/comment/{id}', requirements: ['id' => '\d+'])]
final class CommentController extends AbstractController
{
    public function __construct(private CommentRepository $commentRepository)
    {
    }

    #[Route('/edit', name: 'comment_edit', methods: ['GET', 'POST'])]
    public function edit(Comment $comment, Request $request): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commentRepository->save($comment);
            $this->addFlash('success', 'Commentaire modifié.');

            return $this->redirectToRoute('product_show', ['id' => $comment->getProductId()]);
        }

        return $this->render('product/edit_comment.html.twig', [
            'form' => $form,
            'comment' => $comment,
        ]);
    }

    #[Route('/delete', name: 'comment_delete', methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete" ~ args["comment"].getId()'))]
    public function delete(Comment $comment): Response
    {
        $this->commentRepository->delete($comment);
        $this->addFlash('success', 'Commentaire supprimé.');

        return $this->redirectToRoute('product_show', ['id' => $comment->getProductId()]);
    }
}
