<?php

namespace App\Controller;

use App\Repository\ForumCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class ForumController extends AbstractController
{
    private ForumCategoryRepository $categoryRepository;
    private SerializerInterface $serializer;

    public function __construct(ForumCategoryRepository $categoryRepository, SerializerInterface $serializer)
    {
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
    }

    #[Route('/api/forums/categories', name: 'get_forum_getAllForumCategories', methods: 'GET')]
    public function getAllForumCategories(): Response
    {
        $categoriesJson = $this->serializer->serialize($this->categoryRepository->findAllActivated(), "json",
            ["groups" => "forumcategory_read"]);
        return $this->json($categoriesJson);
    }
}
