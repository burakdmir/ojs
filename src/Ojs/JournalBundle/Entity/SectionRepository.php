<?php

namespace Ojs\JournalBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * SectionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SectionRepository extends EntityRepository
{
    public function getIds()
    {
        $query = $this
            ->createQueryBuilder('section')
            ->select('section.id')
            ->getQuery();

        return $query->getArrayResult();
    }

    public function getIdsByJournal(Journal $journal)
    {
        $query = $this
            ->createQueryBuilder('section')
            ->select('section.id')
            ->where('section.journal = :journal')
            ->setParameter('journal', $journal)
            ->getQuery();

        return $query->getArrayResult();
    }
}
