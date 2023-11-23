<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 *
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @param string $isbn
     * @return Book|null
     */
    public function findOneByIsbn(string $isbn): ?Book
    {
        $result = $this->createQueryBuilder("b")
            ->andWhere("b.isbn = :isbn")
            ->setParameter("isbn", $isbn)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof Book ? $result : null;
    }
}
