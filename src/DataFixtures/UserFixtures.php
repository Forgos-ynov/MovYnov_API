<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $userAdmin = $this->createUser("admin@gmail.com", ["ROLE_USER", "ROLE_ADMIN"], "Administrateur");
        $user = $this->createUser("utilisateur@gmail.com", ["ROLE_USER"], "Utilisateur");

        $manager->persist($userAdmin);
        $manager->persist($user);
        $manager->flush();
    }

    private function createUser(string $email, array $roles, string $pseudo): User
    {
        $passHash = password_hash("password", PASSWORD_ARGON2I);
        $today = new \DateTimeImmutable();
        $today->format("Y-m-d H:i:s");
        $uuid4 = Uuid::uuid4();

        $user = new User();
        $user->setEmail($email)
            ->setSpoilers(false)
            ->setRoles($roles)
            ->setPassword($passHash)
            ->setUpdatedAt($today)
            ->setCreatedAt($today)
            ->setIsDeleted(false)
            ->setPseudo($pseudo)
            ->setUuid($uuid4->toString());

        return $user;
    }
}
