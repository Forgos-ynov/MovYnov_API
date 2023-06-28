<?php

namespace App\Controller;

use App\Repository\ForumCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;

class ForumController extends AbstractController
{
    private ForumCategoryRepository $categoryRepository;
    private SerializerInterface $serializer;

    public function __construct(ForumCategoryRepository  $categoryRepository, SerializerInterface $serializer)
    {
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
    }

    #[Route('/api/forums/categories', name: 'get_forum_getAllForumCategories', methods: 'GET')]
    public function getAllForumCategories(Request $request): Response
    {
        $token = $this->token($request);
        if ($token["exp"] >= time() || is_null($token)) {
            $data = $this->serializer->serialize(["message" => "Token non invalide, ou expiré."], 'json');
            return new JsonResponse($data, 498, [], true);
        }

        $categoriesJson = $this->serializer->serialize($this->categoryRepository->findAllActivated(), "json",
            ["groups" => "forumcategory_read"]);
        return $this->json($categoriesJson);
    }

    private function token(Request $request)
    {
        $authorizationHeader = $request->headers->get('Authorization');
        $bearer = substr($authorizationHeader, 0, 6);

        if ($bearer == "Bearer") {
            $bearer = substr($authorizationHeader, 7);
            $json = base64_decode($bearer);
            $token = json_decode($json);
        } else {
            $token = null;
        }

        return $token;
    }
}
