<?php

namespace App\Controller;

use App\Entity\ForumPost;
use App\Repository\ForumCategoryRepository;
use App\Repository\ForumPostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ForumPostController extends AbstractController
{
    private ForumPostRepository $postRepository;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(ForumPostRepository $postRepository, SerializerInterface $serializer,
                                ValidatorInterface      $validator, EntityManagerInterface $entityManager)
    {
        $this->postRepository = $postRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/forums/posts/{idMovie}', name: 'get_forumPost_getAllForumPostsByMovieId', methods: 'GET')]
    public function getAllForumPostsByMovieId(int $idMovie): Response
    {
        $postsJson = $this->serializer->serialize($this->postRepository->findAllActivatedByMovieId($idMovie),
            "json", ["groups" => "forumPost_read"]);
        return new JsonResponse($postsJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/forums/posts', name: 'get_forumPost_getAllForumPosts', methods: 'GET')]
    public function getAllForumPosts(): Response
    {
        $postsJson = $this->serializer->serialize($this->postRepository->findAll(),
            "json", ["groups" => "forumPost_read"]);
        return new JsonResponse($postsJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/forums/posts/search/{searching}', name: 'get_forumPost_getAllForumPostsBySearching', methods: 'GET')]
    public function getAllForumPostsBySearching(string $searching): Response
    {
        $searchingPosts = $this->postRepository->findAllActivatedSearching($searching);
        $forumPostJson = $this->serializer->serialize($searchingPosts, "json",
            ["groups" => "forumPost_read"]);
        return new JsonResponse($forumPostJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/forums/posts', name: 'post_forumPost_createForumPost', methods: 'POST')]
    public function createForumPost(Request $request, UserRepository $userRepository,
                                    ForumCategoryRepository $categoryRepository): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }
        $token = $this->token($request);

        $forumPost = $this->serializer->deserialize($request->getContent(), ForumPost::class, "json");
        $forumPost->setUuid(uniqid());
        $forumPost->setIsDeleted(false);
        $today = $this->getTimeNow();
        $forumPost->setCreatedAt($today);
        $forumPost->setUpdatedAt($today);
        $content = $request->toArray();

        $user = $userRepository->findUserByEmail($token->email);
        $forumPost->setIdUser($user[0]);
        $forumCat = $categoryRepository->find($content["idForumCategory"] ?? -1);
        $forumPost->setIdForumCategory($forumCat);

        if ($this->validatorError($forumPost)) {
            return $this->jsonResponseValidatorError($forumPost);
        }

        $this->entityManager->persist($forumPost);
        $this->entityManager->flush();

        $postJson = $this->serializer->serialize($forumPost,"json", ["groups" => "forumPost_read"]);
        return new JsonResponse($postJson, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/forums/posts/{idForumPost}', name: 'delete_forumPost_disableForumPost', methods: 'DELETE')]
    #[ParamConverter("forumPost", options: ["id" => "idForumPost"])]
    public function disableForumPost(forumPost $forumPost, Request $request): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }

        $forumPost->setIsDeleted(true);
        $this->entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/forums/posts/one/{idForumPost}', name: 'get_forumPost_getOneForumPost', methods: 'GET')]
    #[ParamConverter("forumPost", options: ["id" => "idForumPost"])]
    public function getOneForumPost(ForumPost $forumPost): Response
    {
        if ($forumPost->isIsDeleted()) {
            $data = $this->serializer->serialize(["message" => "Le post n'as pas été trouvé."], 'json');
            return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
        }
        $postsJson = $this->serializer->serialize($forumPost, "json",
            ["groups" => "oneForumPost_read"]);
        return new JsonResponse($postsJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/forums/posts/{idForumPost}', name: 'put_forumPost_disableForumCategory', methods: 'PUT')]
    #[ParamConverter("forumPostReq", options: ["id" => "idForumPost"])]
    public function updateForumCategory(Request $request, ForumPost $forumPostReq, UserRepository $userRepository,
                                        ForumCategoryRepository $forumCategoryRepository): JsonResponse
    {
        $updateForumPost = $this->serializer->deserialize($request->getContent(), ForumPost::class, "json");
        $content = $request->toArray();
        $forumPost = $this->loadForumPostData($updateForumPost, $forumPostReq);
        $forumPost = $this->setUser($forumPost);
        $forumPost = $this->setForumCat($forumCategoryRepository, $content, $forumPost);

        if ($this->validatorError($forumPost)) {
            return $this->jsonResponseValidatorError($forumPost);
        }

        $this->entityManager->persist($forumPost);
        $this->entityManager->flush();

        $postsJson = $this->serializer->serialize($forumPost, "json", ["groups" => "forumPost_read"]);
        return new JsonResponse($postsJson, Response::HTTP_CREATED, [], true);
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

    private function loadForumPostData(ForumPost $updateForumPost, ForumPost $forumPost)
    {
        $forumPost->setTitle($updateForumPost->getTitle() ?? $forumPost->getTitle());
        $forumPost->setContent($updateForumPost->getContent() ?? $forumPost->getContent());
        $forumPost->setSpoilers($updateForumPost->isSpoilers() ?? $forumPost->isSpoilers());
        $forumPost->setUpdatedAt($this->getTimeNow());

        return $forumPost;
    }

    private function tokenNotValaible()
    {
        $data = $this->serializer->serialize(["message" => "Token non invalide, ou expiré."], 'json');
        return new JsonResponse($data, 498, [], true);
    }

    private function unAuthorize()
    {
        $data = $this->serializer->serialize(["message" => "Vous n'êtes pas autorisé à accéder à cette section."], 'json');
        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED, [], true);
    }

    /**
     * Fonction retournant le nombre de validator error contenue dans un objet, 0 étant pareil que False
     *
     * @param $object
     * @return integer
     */
    private function validatorError($object): int
    {
        $errors = $this->validator->validate($object);
        return $errors->count();
    }

    /**
     * Fonction permettant de ressortir un JsonResponse de status Not_found avec les erreurs du validator error
     *
     * @param $object
     * @return JsonResponse
     */
    private function jsonResponseValidatorError($object): JsonResponse
    {
        $errors = $this->validator->validate($object);
        return new JsonResponse($this->serializer->serialize($errors, "json"),
            Response::HTTP_BAD_REQUEST, [], true);
    }

    private function getTimeNow()
    {
        $today = new \DateTimeImmutable();
        $today->format("Y-m-d H:i:s");
        return $today;
    }

    private function tokenVerification(Request $request)
    {
        $token = $this->token($request);
        if ($token->exp <= time() || is_null($token)) {
            return $this->tokenNotValaible();
        }

        return "pass";
    }

    private function setUser(ForumPost $forumPost): ForumPost
    {
        $forumPost->setIdUser($forumPost->getIdUser());
        return $forumPost;
    }

    private function setForumCat(ForumCategoryRepository $forumCategoryRepository, array $content, ForumPost $forumPost): ForumPost
    {
        $forumCat = $forumCategoryRepository->find($content["idCategory"] ?? $forumPost->getIdForumCategory());
        $forumPost->setIdForumCategory($forumCat);
        return $forumPost;
    }
}
