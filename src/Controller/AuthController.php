<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;

class AuthController extends AbstractController
{
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private UserRepository $userRepository;


    /**
     * Constructeur de mon controlleur de booklet
     *
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     */
    public function __construct(SerializerInterface    $serializer, ValidatorInterface $validator,
                                EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/api/register', name: 'post_auth_registerAuthController', methods: ["POST"])]
    public function registerAuthController(Request $request): JsonResponse
    {
        if ($this->userExistInDB($request)) {
            $data = $this->serializer->serialize(["message" => "Problème avec les identifiants"], 'json');
            return new JsonResponse($data, Response::HTTP_FORBIDDEN, [], true);
        }

        $user = $this->serializer->deserialize($request->getContent(), User::class, "json");
        $user->setIsDeleted(false);
        $today = new \DateTimeImmutable();
        $today->format("Y-m-d H:i:s");
        $user->setCreatedAt($today);
        $user->setUpdatedAt($today);
        $uuid4 = Uuid::uuid4();
        $user->setUuid($uuid4->toString());
        $user->setRoles(["ROLE_USER"]);

        $pass = $user->getPassword();
        $passHash = password_hash($pass, PASSWORD_ARGON2I);
        $user->setPassword($passHash);

        if ($this->validatorError($user)) {
            return $this->jsonResponseValidatorError($user);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->createToken($user);
        $data = ['token' => $token, "user" => $user];
        $serializedData = $this->serializer->serialize($data, 'json', ["groups" => "user_read"]);
        return new JsonResponse($serializedData, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/login', name: 'post_auth_loginAuthController', methods: "POST")]
    public function loginAuthController(Request $request): JsonResponse
    {
        $res = $this->userExistInDB($request);
        if ($res) {
            $user = $res[0];
            $token = $this->createToken($user);
            $data = ['token' => $token, "user" => $user];
            $serializedData = $this->serializer->serialize($data, 'json', ["groups" => "user_read"]);
            return new JsonResponse($serializedData, Response::HTTP_OK, [], true);
        }

        $data = $this->serializer->serialize(["message" => "Problème avec les identifiants"], 'json');
        return new JsonResponse($data, Response::HTTP_FORBIDDEN, [], true);
    }

    #[Route('/api/token', name: 'post_auth_isAuthAuthController', methods: "get")]
    public function isAuthAuthController(Request $request): JsonResponse
    {
        $token = $this->token($request);
        if (is_null($token) || !$token->roles) {
            $status = false;
        } else {
            if (in_array("ROLE_ADMIN", $token->roles)) {
                $status = true;
            } else {
                $status = false;
            }
        }

        $data = $this->serializer->serialize(["message" => $status], 'json');
        return new JsonResponse($data, Response::HTTP_OK, [], true);
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

    private function userExistInDB(Request $request)
    {
        $bodyContent = $request->getContent();
        $infos = json_decode($bodyContent, true);
        if (!isset($infos["email"]) || !isset($infos["password"])) {
            return false;
        }
        $userExist = $this->userRepository->retrieveUserByEmail($infos["email"]);
        if (sizeof($userExist) == 0) {
            return false;
        }
        if ($userExist[0]->isIsDeleted()) {
            return false;
        }
        if (password_verify($infos["password"], $userExist[0]->getPassword())) {
            return $userExist;
        }
        return false;
    }

    private function createToken($user)
    {
        $payload = [
            "iat" => time(),
            "exp" => time() + (3 * 60 * 60),
            "roles" => $user->getRoles(),
            "pseudo" => $user->getPseudo(),
            "email" => $user->getEmail()
        ];
        $json = json_encode($payload);
        return base64_encode($json);
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
