<?php

declare(strict_types=1);

use App\HtmlParser;
use App\HttpClient;
use App\RatingService;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$dotenv = new Dotenv();

$app->addErrorMiddleware(false, true, true);

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv->load(__DIR__ . '/../.env');
}

if (file_exists(__DIR__ . '/../.env.local')) {
    $dotenv->overload(__DIR__ . '/../.env.local');
}

$username = $_ENV['LETTERBOXD_LOGIN'] ?? throw new \RuntimeException('LETTERBOXD_LOGIN is not set');
$password = $_ENV['LETTERBOXD_PASSWORD'] ?? throw new \RuntimeException('LETTERBOXD_PASSWORD is not set');

$cache = new FilesystemAdapter(directory: __DIR__.'/../var/cache');
$httpClient = new HttpClient($username, $password);
$service = new RatingService($httpClient, new HtmlParser());

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->get('/', function (Request $request, Response $response) {
    $html = file_get_contents(__DIR__ . '/index.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/api/ratings/{listName}', function (Request $request, Response $response, $args) use ($service, $username, $cache) {
    $filmListName = $args['listName'];
    $cacheKey = "films_{$username}_{$filmListName}";

    $queryParams = $request->getQueryParams();
    $forceRefresh = ($queryParams['refresh'] ?? '') === '1';

    if ($forceRefresh) {
        $cache->delete($cacheKey);
    }

    $queryParams = $request->getQueryParams();
    if (array_key_exists('refresh', $queryParams) && $queryParams['refresh'] === '1') {
        $cache->delete($cacheKey);
    }

    $body = $cache->get($cacheKey, function () use ($service, $username, $filmListName) {
        $filmList = $service->getFilmListWithUserRatings($username, $filmListName);
        return json_encode($filmList, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    });

    $response->getBody()->write($body);

    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});

$app->run();
