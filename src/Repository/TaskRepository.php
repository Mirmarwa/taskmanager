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
     * Retourne les tâches assignées à l'utilisateur
     * avec une deadline dans les prochains $days jours.
     *
     * @return Task[]
     */
    public function findUpcomingDeadlinesForUser(User $user, int $days = 7): array
    {
        $now = new DateTimeImmutable();
        $limitDate = $now->add(new DateInterval('P' . $days . 'D'));

        return $this->createQueryBuilder('t')
            ->andWhere('t.assignedTo = :user')
            ->andWhere('t.deadline IS NOT NULL')
            ->andWhere('t.deadline >= :now')
            ->andWhere('t.deadline <= :limitDate')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->setParameter('limitDate', $limitDate)
            ->orderBy('t.deadline', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findByAssignedUser(User $user): array
{
    return $this->createQueryBuilder('t')
        ->andWhere('t.assignedTo = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult();
}

}
