<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PDO;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @return Comment[]
     */
    public function findByProduct(int $productId): array
    {
        return $this->findBy(['productId' => $productId], ['createdAt' => 'DESC', 'id' => 'DESC']);
    }

    public function save(Comment $comment): void
    {
        $pdo = $this->pdo();

        if (null === $comment->getId()) {
            $pdo->prepare('INSERT INTO comment (product_id, content, created_at) VALUES (:product_id, :content, :created_at)')
                ->execute([
                    'product_id' => $comment->getProductId(),
                    'content' => $comment->getContent(),
                    'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                ]);
            $comment->setId((int) $pdo->lastInsertId());
        } else {
            $pdo->prepare('UPDATE comment SET content = :content WHERE id = :id')
                ->execute(['content' => $comment->getContent(), 'id' => $comment->getId()]);
        }
    }

    public function delete(Comment $comment): void
    {
        $this->pdo()->prepare('DELETE FROM comment WHERE id = :id')->execute(['id' => $comment->getId()]);
    }

    private function pdo(): PDO
    {
        return $this->getEntityManager()->getConnection()->getNativeConnection();
    }
}
