<?php

namespace App\DataFixtures;

use App\Entity\ForumCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class ForumCategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $forumCatDiscussion = $this->createForumCat("Discussion","Catégorie réunissant toutes discussions autour d'un film");
        $forumCatQuestion = $this->createForumCat("Question","Catégorie réunissant toutes questions autour d'un film");
        $forumCatDiscCrit = $this->createForumCat("Critique","Catégorie réunissant toutes critiques autour d'un film");
        $forumCatDiscReview = $this->createForumCat("Review","Catégorie réunissant toutes review autour d'un film");

        $manager->persist($forumCatDiscussion);
        $manager->persist($forumCatQuestion);
        $manager->persist($forumCatDiscCrit);
        $manager->persist($forumCatDiscReview);
        $manager->flush();
    }

    private function createForumCat(string $title, string $description): ForumCategory
    {
        $today = new \DateTimeImmutable();
        $today->format("Y-m-d H:i:s");
        $uuid4 = Uuid::uuid4();

        $forumCat = new ForumCategory();
        $forumCat->setUuid($uuid4->toString())
            ->setIsDeleted(false)
            ->setCreatedAt($today)
            ->setUpdatedAt($today)
            ->setTitle($title)
            ->setDescription($description);

        return $forumCat;
    }
}
