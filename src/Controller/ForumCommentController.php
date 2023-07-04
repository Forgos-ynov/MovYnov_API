<?php

namespace App\Controller;

use App\Entity\ForumComment;
use App\Repository\ForumCommentRepository;
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

class ForumCommentController extends AbstractController
{
    private ForumCommentRepository $commentRepository;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(ForumCommentRepository $commentRepository, SerializerInterface $serializer,
                                ValidatorInterface      $validator, EntityManagerInterface $entityManager)
    {
        $this->commentRepository = $commentRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/forums/comments/{idPost}', name: 'get_forumComment_getAllForumCommentByPost', methods: 'GET')]
    public function getAllForumCommentByPost(int $idPost): Response
    {
        $commentJson = $this->serializer->serialize($this->commentRepository->findAllActivatedByPostId($idPost),
            "json", ["groups" => "forumComment_read"]);
        return new JsonResponse($commentJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/forums/comments', name: 'post_forumComment_createForumComment', methods: 'POST')]
    public function createForumComment(Request $request, UserRepository $userRepository,
                                       ForumPostRepository $postRepository): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }

        $forumComment = $this->serializer->deserialize($request->getContent(), ForumComment::class, "json");
        $forumComment->setUuid(uniqid());
        $forumComment->setIsDeleted(false);
        $today = $this->getTimeNow();
        $forumComment->setCreatedAt($today);
        $forumComment->setUpdatedAt($today);
        $content = $request->toArray();

        $user = $userRepository->find($content["idUser"] ?? -1);
        $forumComment->setIdUser($user);
        $forumPost = $postRepository->find($content["idForumPost"] ?? -1);
        $forumComment->setIdPost($forumPost);

        if ($this->validatorError($forumComment)) {
            return $this->jsonResponseValidatorError($forumComment);
        }

        $this->entityManager->persist($forumComment);
        $this->entityManager->flush();

        $data = $this->serializer->serialize(["message" => "Le commentaire à été créé avec succès."], 'json');
        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/forums/comments/{idForumComment}', name: 'delete_forumComment_disableForumComment', methods: 'DELETE')]
    #[ParamConverter("forumComment", options: ["id" => "idForumComment"])]
    public function disableForumComment(ForumComment $forumComment, Request $request): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }

        $forumComment->setIsDeleted(true);
        $this->entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/forums/comments', name: 'get_forumComment_getPostsByUser', methods: 'GET')]
    public function getPostsByUser(Request $request, UserRepository $userRepository): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }
        $token = $this->token($request);
        $user = $userRepository->retrieveUserByEmail($token->email);

        $commentJson = $this->serializer->serialize($this->commentRepository->findAllActivatedByUserId($user[0]->getId()),
            "json", ["groups" => "forumComment_read"]);
        return new JsonResponse($commentJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/forums/comments/{idForumComment}', name: 'put_forumComment_updateForumComment', methods: 'PUT')]
    #[ParamConverter("forumCommentReq", options: ["id" => "idForumComment"])]
    public function updateForumComment(Request $request, ForumComment $forumCommentReq, UserRepository $userRepository,
                                        ForumPostRepository $forumPostRepository): JsonResponse
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }
        $token = $this->token($request);

        $updateForumComment = $this->serializer->deserialize($request->getContent(), ForumComment::class, "json");
        $content = $request->toArray();
        $forumComment = $this->loadForumCommentData($updateForumComment, $forumCommentReq);
        $forumComment = $this->setUser($userRepository, $token, $forumComment);
        $forumComment = $this->setForumPost($forumPostRepository, $content, $forumComment);

        if ($this->validatorError($forumComment)) {
            return $this->jsonResponseValidatorError($forumComment);
        }

        $this->entityManager->persist($forumComment);
        $this->entityManager->flush();

        $data = $this->serializer->serialize(["message" => "Le commentaire à été modifier avec succès."], 'json');
        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
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

    private function loadForumCommentData(ForumComment $updateForumComment, ForumComment $forumComment)
    {
        $forumComment->setContent($updateForumComment->getContent() ?? $forumComment->getContent());
        $forumComment->setSpoilers($updateForumComment->isSpoilers() ?? $forumComment->isSpoilers());
        $forumComment->setUpdatedAt($this->getTimeNow());

        return $forumComment;
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

    private function setUser(UserRepository $userRepository, $token, ForumComment $forumComment): ForumComment
    {
        $user = $userRepository->retrieveUserByEmail($token->email) ?? $forumComment->getIdUser();
        $forumComment->setIdUser($user[0]);
        return $forumComment;
    }

    private function setForumPost(ForumPostRepository $postRepository, array $content, ForumComment $forumComment): ForumComment
    {
        $forumPost = $postRepository->find($content["idPost"] ?? $forumComment->getIdPost());
        $forumComment->setIdPost($forumPost);
        return $forumComment;
    }
}
