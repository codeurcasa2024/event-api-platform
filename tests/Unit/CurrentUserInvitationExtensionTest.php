<?php

// tests/Unit/CurrentUserInvitationExtensionTest.php

namespace App\Tests\Unit;

use App\Doctrine\CurrentUserInvitationExtension;
use App\Entity\Invitation;
use App\Entity\User as UserEntity;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use PHPUnit\Framework\TestCase;
// Remplacer l’import pour utiliser la bonne classe Security
use Symfony\Bundle\SecurityBundle\Security;

class CurrentUserInvitationExtensionTest extends TestCase
{
    public function testAddWhereAppliesFilter(): void
    {
        // 1) Stub d’un User réel avec getId() = 42
        $user = $this->getMockBuilder(UserEntity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user->method('getId')->willReturn(42);

        // 2) Mock de Security (Symfony\Bundle\SecurityBundle\Security)
        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user);

        // 3) Mock du QueryBuilder
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('o.invitee = :currentUser')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('currentUser', 42)
            ->willReturnSelf();
        $qb->method('getRootAliases')
            ->willReturn(['o']);

        // 4) Instanciation de l’extension et appel
        $extension = new CurrentUserInvitationExtension($security);
        $extension->applyToCollection(
            $qb,
            new QueryNameGenerator(),
            Invitation::class,
            null,
            []  // n’oubliez pas le paramètre $context
        );
    }
}
