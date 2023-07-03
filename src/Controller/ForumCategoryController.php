<?php

namespace App\Controller;

use App\Entity\ForumCategory;
use App\Repository\ForumCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class ForumCategoryController extends AbstractController
{
    private ForumCategoryRepository $categoryRepository;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(ForumCategoryRepository $categoryRepository, SerializerInterface $serializer,
                                ValidatorInterface      $validator, EntityManagerInterface $entityManager)
    {
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/forums/categories', name: 'get_forumCat_getAllForumCategories', methods: 'GET')]
    public function getAllForumCategories(): Response
    {
        $categoriesJson = $this->serializer->serialize($this->categoryRepository->findAllActivated(), "json",
            ["groups" => "forumcategory_read"]);
        return new JsonResponse($categoriesJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/forums/categories', name: 'post_forumCat_createForumCategory', methods: 'POST')]
    public function createForumCategory(Request $request): Response
    {
        $tokenRes = $this->tokenVerification($request);
         if ($tokenRes != "pass") {
             return $tokenRes;
         }

        $forumCat = $this->serializer->deserialize($request->getContent(), ForumCategory::class, "json");
        $forumCat->setUuid(uniqid());
        $forumCat->setIsDeleted(false);
        $today = $this->getTimeNow();
        $forumCat->setCreatedAt($today);
        $forumCat->setUpdatedAt($today);

        if ($this->validatorError($forumCat)) {
            return $this->jsonResponseValidatorError($forumCat);
        }

        $this->entityManager->persist($forumCat);
        $this->entityManager->flush();

        $data = $this->serializer->serialize(["message" => "La catégorie à été créé avec succès."], 'json');
        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/forums/categories/{idForumCat}', name: 'delete_forumCat_disableForumCategory', methods: 'DELETE')]
    #[ParamConverter("forumCategory", options: ["id" => "idForumCat"])]
    public function disableForumCategory(ForumCategory $forumCategory, Request $request): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }

        $forumCategory->setIsDeleted(true);
        $this->entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/forums/categories/{idForumCat}', name: 'get_forumCat_getOneForumCategory', methods: 'GET')]
    #[ParamConverter("forumCategory", options: ["id" => "idForumCat"])]
    public function getOneForumCategory(ForumCategory $forumCategory): Response
    {
        if ($forumCategory->isIsDeleted()) {
            $data = $this->serializer->serialize(["message" => "La catégorie n'as pas été trouvée."], 'json');
            return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
        }
        $categoriesJson = $this->serializer->serialize($forumCategory, "json",
            ["groups" => "forumcategory_read"]);
        return new JsonResponse($categoriesJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/forums/categories/{idForumCat}', name: 'put_forumCat_disableForumCategory', methods: 'PUT')]
    #[ParamConverter("forumCategory", options: ["id" => "idForumCat"])]
    public function updateForumCategory(ForumCategory $forumCategory, Request$request): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }

        $updateForumCat = $this->serializer->deserialize($request->getContent(), ForumCategory::class, "json");
        $forumCat = $this->loadForumCatData($updateForumCat, $forumCategory);

        if ($this->validatorError($forumCat)) {
            return $this->jsonResponseValidatorError($forumCat);
        }

        $this->entityManager->persist($forumCat);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_CREATED);
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

    private function loadForumCatData(ForumCategory $updateForumCat, ForumCategory $forumCategory)
    {
        $forumCategory->setTitle($updateForumCat->getTitle() ?? $forumCategory->getTitle());
        $forumCategory->setDescription($updateForumCat->getDescription() ?? $forumCategory->getDescription());
        $forumCategory->setUpdatedAt($this->getTimeNow());

        return $forumCategory;
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
        } elseif ($token->roles[0] == "ROLE_USER" && sizeof($token->roles) == 1) {
            return $this->unAuthorize();
        }

        return "pass";
    }
}
