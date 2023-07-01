<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ImdbController extends AbstractController
{
    private string $prefixeUrl;
    private string $sufixeUrl;

    public function __construct()
    {
        $this->prefixeUrl = "https://api.themoviedb.org/3/movie/";
        $this->sufixeUrl = "?append_to_response=credits&language=fr-FR&api_key=b8e285eb83d6d2fccd9f0acc529b5004";
    }

    #[Route('/api/imdb/movies/{idMovie}', name: 'get_imdb_getInformationsOfOneMovie', methods: "GET")]
    public function getInformationsOfOneMovie(int $idMovie): JsonResponse
    {
        $url = $this->prefixeUrl . $idMovie . $this->sufixeUrl;
        $json = $this->getJsonFromUrl($url);
        return $this->json($json);
    }

    #[Route('/api/imdb/movies/recommandation/{idMovieRecommandation}', name: 'get_imdb_getInformationsOfOneMovie', methods: "GET")]
    public function test(int $idMovieRecommandation): JsonResponse
    {
        $url = $this->prefixeUrl . $idMovieRecommandation . $this->sufixeUrl;
        $json = $this->getJsonFromUrl($url);
        return $this->json($json);
    }

    private function getJsonFromUrl(string $url)
    {
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);

        $response = curl_exec($curl);
        return json_decode($response);
    }
}
