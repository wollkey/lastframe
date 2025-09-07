<?php

declare(strict_types=1);

namespace Tests;

use App\HtmlParser;
use App\HttpClientInterface;
use App\RatingService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RatingService::class)]
#[CoversClass(HtmlParser::class)]
final class RatingServiceTest extends TestCase
{
    private RatingService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $parser = new HtmlParser();
        $this->service = new RatingService($this->httpClient, $parser);
    }

    public function testGetFriendRatings()
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('john/friends/film/apples/')
            ->willReturn(file_get_contents(__DIR__ . '/Fixtures/user_friends_film.html'));

        $ratings = $this->service->getFriendRatings('john', 'apples');

        self::assertCount(7, $ratings);
        self::assertEquals(
            [
                'user' => 'psy667',
                'rating' => '9',
                'isOwner' => false,
            ],
            $ratings[0]
        );
    }

    public function testGetFilmList(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('user/list/sight-sound/')
            ->willReturn(file_get_contents(__DIR__ . '/Fixtures/user_list.html'));

        $films = $this->service->getFilmList('user', 'sight-sound');

        self::assertCount(14, $films);
        self::assertEquals(
            [
                'filmId' => '2702',
                'slug' => 'citizen-kane',
                'title' => 'Citizen Kane',
                'userRatings' => [
                    [
                        'user' => 'wollkey',
                        'rating' => '10',
                        'isOwner' => true,
                    ]
                ],
            ],
            $films[0],
        );
    }

    public function testGetFilmListWithFriendRatings(): void
    {
        $listHtml = file_get_contents(__DIR__ . '/Fixtures/user_list.html');
        $ratingsHtml = file_get_contents(__DIR__ . '/Fixtures/user_friends_film.html');

        $this->httpClient
            ->expects($this->exactly(15))
            ->method('get')
            ->willReturnCallback(function (string $path) use ($listHtml, $ratingsHtml) {
                return str_contains($path, '/list/') ? $listHtml : $ratingsHtml;
            });

        $filmsWithRatings = $this->service->getFilmListWithUserRatings('user', 'sight-sound');

        self::assertCount(14, $filmsWithRatings);

        $film = $filmsWithRatings[0];
        self::assertArrayHasKey('userRatings', $film);
        self::assertNotEmpty($film['userRatings']);
        self::assertCount(8, $film['userRatings']);
    }
}