<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializationContext;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use DateTimeImmutable;

class AuthController extends AbstractController
{
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private JWTTokenManagerInterface $jwtManager;


    /**
     * Constructeur de mon controlleur de booklet
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator,
                                EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->jwtManager = $jwtManager;
    }

    #[Route('/api/register', name: 'post_auth_registerAuthController', methods: ["POST"])]
    public function registerAuthController(Request $request): JsonResponse
    {
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
        $passHash = password_hash($pass, PASSWORD_ARGON2I );
        $user->setPassword($passHash);

        if ($this->validatorError($user)) {
            return $this->jsonResponseValidatorError($user);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);
        $data = ['token' => $token];
        $context = SerializationContext::create()->setGroups(["getUser"]);
        $serializedData = $this->serializer->serialize($data, 'json', $context);
        return new JsonResponse($serializedData, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/login', name: 'post_auth_loginAuthController', methods: "POST")]
    public function loginAuthController(Request $request): JsonResponse
    {
        var_dump($request);
        die();
    }

    /**
     * Fonction retournant le nombre de validator error contenue dans un objet, 0 étant pareil que False
     *
     * @param $object
     * @return integer
     */
    public function validatorError($object): int
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
    public function jsonResponseValidatorError($object): JsonResponse
    {
        $errors = $this->validator->validate($object);
        return new JsonResponse($this->serializer->serialize($errors, "json"),
            Response::HTTP_BAD_REQUEST, [], true);
    }
}
