<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTimeImmutable;
use DateInterval;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Retourne les tâches assignées à l'utilisateur avec une deadline
     * dans les prochains $days jours (par défaut : 7 jours).
     *
     * Exemple : deadlines entre aujourd'hui (inclus) et dans 7 jours.
     *
     * @return Task[] Tableau de tâches triées par date limite croissante
     */
    public function findUpcomingDeadlinesForUser(User $user, int $days = 7): array
    {
        $now = new DateTimeImmutable();
        $limitDate = $now->add(new DateInterval('P' . $days . 'D'));

        return $this->createQueryBuilder('t')
            ->andWhere('t.assignee = :user')
            ->andWhere('t.dueDate IS NOT NULL')
            ->andWhere('t.dueDate >= :now')
            ->andWhere('t.dueDate <= :limitDate')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->setParameter('limitDate', $limitDate)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Tu peux laisser ou supprimer les méthodes d'exemple commentées ci-dessous
    //    /**
    //     * @return Task[] Returns an array of Task objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult();
    //    }

    //    public function findOneBySomeField($value): ?Task
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult();
    //    }
}