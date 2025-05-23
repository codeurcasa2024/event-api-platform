<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use ApiPlatform\Metadata\Operation;

/**
 * Filtre automatiquement la collection et les items Invitation pour ne
 * renvoyer que ceux où invitee == utilisateur connecté.
 */
final class CurrentUserInvitationExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    private function addWhere(QueryBuilder $qb, string $resourceClass): void
    {
        if ($resourceClass !== \App\Entity\Invitation::class) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        $rootAlias = $qb->getRootAliases()[0];
        $qb->andWhere(sprintf('%s.invitee = :currentUser', $rootAlias))
           ->setParameter('currentUser', $user->getId());
    }

    public function applyToCollection(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $qng,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($qb, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $qng,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($qb, $resourceClass);
    }
}
