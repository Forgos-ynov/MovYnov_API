<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(UserRepository     $userRepository, SerializerInterface $serializer,
                                ValidatorInterface $validator, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/users', name: 'get_user_getAllUsers', methods: 'GET')]
    public function getAllUsers(): Response
    {
        $usersJson = $this->serializer->serialize($this->userRepository->findAllActivated(), "json",
            ["groups" => "user_read"]);
        return new JsonResponse($usersJson, Response::HTTP_OK, [], true);
    }


    #[Route('/api/users/{idUser}', name: 'delete_users_disableUser', methods: 'DELETE')]
    #[ParamConverter("user", options: ["id" => "idUser"])]
    public function disableUser(User $user, Request $request): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }

        $token = $this->token($request);
        if ($token->email == $user->getEmail()) {
            $data = $this->serializer->serialize(["message" => "Vous ne pouvez pas vous désactiver"], 'json');
            return new JsonResponse($data, Response::HTTP_UNAUTHORIZED, [], true);
        }

        $user->setIsDeleted(true);
        $this->entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/users/{idUser}', name: 'get_users_getOneUser', methods: 'GET')]
    #[ParamConverter("user", options: ["id" => "idUser"])]
    public function getOneUser(User $user): Response
    {
        if ($user->isIsDeleted()) {
            $data = $this->serializer->serialize(["message" => "L'utilisateur n'as pas été trouvée."], 'json');
            return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
        }
        $userJson = $this->serializer->serialize($user, "json", ["groups" => "user_read"]);
        return new JsonResponse($userJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/token', name: 'get_users_getUserByToken', methods: 'GET')]
    public function getUserByToken(Request $request): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }

        $token = $this->token($request);
        $user = $this->userRepository->findUserByEmail($token->email);

        if ($user->isIsDeleted()) {
            $data = $this->serializer->serialize(["message" => "L'utilisateur n'as pas été trouvée."], 'json');
            return new JsonResponse($data, Response::HTTP_NOT_FOUND, [], true);
        }
        $userJson = $this->serializer->serialize($user, "json", ["groups" => "user_read"]);
        return new JsonResponse($userJson, Response::HTTP_OK, [], true);
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
        try {
            $token = $this->token($request);
            if (is_null($token) || $token->exp <= time()) {
                return $this->tokenNotValaible();
            } elseif ($token->roles[0] == "ROLE_USER" && sizeof($token->roles) == 1) {
                return $this->unAuthorize();
            }

            return "pass";
        } catch (Exception $e) {
            return $this->tokenNotValaible();
        }

    }
}
