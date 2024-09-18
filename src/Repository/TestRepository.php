<?php

namespace App\Repository;

use App\Entity\Discipline;
use App\Entity\Test;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TestRepository extends ServiceEntityRepository

{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Test::class);
    }

    public function findDistinctDisciplines()
    {
        return $this->createQueryBuilder('t')
            ->select('DISTINCT d.id, d.name')
            ->join('t.discipline', 'd')
            ->getQuery()
            ->getResult();
    }

}