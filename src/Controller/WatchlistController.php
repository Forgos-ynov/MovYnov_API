<?php

namespace App\Controller;

use App\Entity\Watchlist;
use App\Repository\UserRepository;
use App\Repository\WatchlistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WatchlistController extends AbstractController
{
    private WatchlistRepository $watchlistRepository;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(WatchlistRepository $watchlistRepository, SerializerInterface $serializer,
                                ValidatorInterface      $validator, EntityManagerInterface $entityManager)
    {
        $this->watchlistRepository = $watchlistRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/watchlists', name: 'post_watchlist_createWatchlist', methods: 'POST')]
    public function createWatchlist(Request $request, UserRepository $userRepository): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }

        $token = $this->token($request);
        $user = $userRepository->findUserByEmail($token->email);

        $watchlist = $this->serializer->deserialize($request->getContent(), Watchlist::class, "json");
        $watchlist->setIdUser($user[0]);

        if ($this->validatorError($watchlist)) {
            return $this->jsonResponseValidatorError($watchlist);
        }

        $this->entityManager->persist($watchlist);
        $this->entityManager->flush();

        $watchlistJson = $this->serializer->serialize($watchlist, "json", ["groups" => "watchlist_read"]);
        return new JsonResponse($watchlistJson, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/watchlists', name: 'get_watchlist_getWatchlistsByUserToken', methods: 'GET')]
    public function getWatchlistsByUserToken(Request $request, UserRepository $userRepository): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }

        $token = $this->token($request);
        $user = $userRepository->findUserByEmail($token->email);

        $watchlists = $this->watchlistRepository->findAllWatchlistsByUserId($user[0]->getId());

        $watchlistsJson = $this->serializer->serialize($watchlists, "json", ["groups" => "watchlist_read"]);
        return new JsonResponse($watchlistsJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/watchlists/{idWatchlist}', name: 'put_watchlist_updateWatchlist', methods: 'PUT')]
    #[ParamConverter("watchlist", options: ["id" => "idWatchlist"])]
    public function updateWatchlist(Watchlist $watchlist, Request$request): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }

        $updateWatchlist = $this->serializer->deserialize($request->getContent(), Watchlist::class, "json");
        $watchL = $this->loadWatchlistData($updateWatchlist, $watchlist);

        if ($this->validatorError($watchL)) {
            return $this->jsonResponseValidatorError($watchL);
        }

        $this->entityManager->persist($watchL);
        $this->entityManager->flush();

        $watchlistsJson = $this->serializer->serialize($watchL, "json", ["groups" => "watchlist_read"]);
        return new JsonResponse($watchlistsJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/watchlists/{idWatchlist}', name: 'delete_watchlist_removeWatchlist', methods: 'DELETE')]
    #[ParamConverter("watchlist", options: ["id" => "idWatchlist"])]
    public function removeWatchlist(Watchlist $watchlist, Request $request): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }

        $this->watchlistRepository->remove($watchlist, true);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/watchlists/movies/{idMovie}', name: 'delete_watchlist_removeWatchlistByIdMovie', methods: 'DELETE')]
    public function removeWatchlistByIdMovie(int $idMovie, Request $request, UserRepository $userRepository): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }
        $token = $this->token($request);
        $user = $userRepository->retrieveUserByEmail($token->email);

        $watchlists = $this->watchlistRepository->findWatchlistByIdUserAndIdMedia($user[0]->getId(), $idMovie);
        foreach ($watchlists as $watchlist) {
            $this->watchlistRepository->remove($watchlist, true);
        }
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/watchlists/{idMovie}', name: 'get_watchlist_IsInWatchList', methods: 'GET')]
    public function IsInWatchList(int $idMovie, Request $request, UserRepository $userRepository): Response
    {
        $tokenRes = $this->tokenVerification($request);
        if ($tokenRes != "pass") {
            return $tokenRes;
        }
        $token = $this->token($request);
        $user = $userRepository->retrieveUserByEmail($token->email);

        $res = $this->watchlistRepository->findWatchlistByIdUserAndIdMedia($user[0]->getId(), $idMovie);
        if (sizeof($res) == 0) {
            $message = ["message" => false];
        } else {
            $message = ["message" => true];
        }

        $data = $this->serializer->serialize($message, "json");
        return new JsonResponse($data, Response::HTTP_OK, [], true);
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

    private function loadWatchlistData(Watchlist $updateWatchlist, Watchlist $watchlist)
    {
        $watchlist->setStatus($updateWatchlist->getStatus() ?? $watchlist->getStatus());

        return $watchlist;
    }

    private function tokenNotValaible()
    {
        $data = $this->serializer->serialize(["message" => "Token non invalide, ou expiré."], 'json');
        return new JsonResponse($data, 498, [], true);
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
}
