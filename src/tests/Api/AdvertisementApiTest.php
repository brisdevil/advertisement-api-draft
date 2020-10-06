<?php

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use App\Models\Advertisement;
use App\Models\AdvertisementDisplay;
use GuzzleHttp\Client;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use App\Models\File as FileModel;
use Psr\Http\Message\ResponseInterface;

/**
 * Тестировщик API рекламных объявлений.
 *
 * Class AdvertisementApiTest
 */
class AdvertisementApiTest extends TestCase
{
    /** @var array Параметр, добавляющийся в каждый запрос для работы с тестовой БД */
    private const TEST_QUERY_PARAM = ['test_mode' => true];

    /** @var FakerGenerator Вспомогательный объект для генерирования случайных данных */
    private $faker;

    /** @var Client HTTP-клиент для взаимодействия с API */
    private $httpClient;

    /**
     * {@inheritDoc}
     *
     * Заполняет тестовую БД рыбными данными перед каждым тестом.
     *
     * @return void
     */
    protected function setUp(): void
    {
        /** @var FakerGenerator faker */
        $this->faker = FakerFactory::create();
        $this->httpClient = new Client(['base_uri' => 'nginx:80/api/v1/']);

        for ($counter = 0; $counter < 10; $counter++) {
            $fakeFile = new FileModel();
            $fakeFile->path = rand(1, 9999) . '.png';
            $fakeFile->save();

            $fakeAdvertisement = new Advertisement();
            $fakeAdvertisement->text = $this->faker->name;
            $fakeAdvertisement->amount = rand(1, 1000);
            $fakeAdvertisement->price = $this->faker->randomFloat(2);
            $fakeAdvertisement->banner_file_id = $fakeFile->id;
            $fakeAdvertisement->save();
        }
    }

    /**
     * {@inheritDoc}
     *
     * Транкейтит таблицы тестовой БД после каждого теста.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->faker = null;
        $this->httpClient = null;

        AdvertisementDisplay::query()->truncate();
        Advertisement::query()->truncate();
        FileModel::query()->truncate();
    }

    /**
     * Тестирует создание нового рекламного объявления.
     *
     * @return void
     * @throws GuzzleException
     */
    public function testCreate(): void
    {
        /** @var ResponseInterface $response */
        $response = $this->httpClient->post('advertisement', [
            'multipart' => [
                ['name' => 'text', 'contents' => 'Тестовое название объявления'],
                ['name' => 'banner', 'contents' => fopen(__DIR__ . '/example_banner.png', 'r')],
                ['name' => 'price', 'contents' => 1.25],
                ['name' => 'amount', 'contents' => 100],
            ],
            'query' => static::TEST_QUERY_PARAM,
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaders()['Content-Type'][0]);
    }

    /**
     * Тестирует обновление рекламного объявления.
     *
     * @return void
     * @throws GuzzleException
     */
    public function testUpdate(): void
    {
        $text = 'Тестовое название объявления';
        $price = 1.25;
        $amount = 100;

        /** @var ResponseInterface $response */
        $response = $this->httpClient->post('advertisement/1', [
            'multipart' => [
                ['name' => 'text', 'contents' => $text],
                ['name' => 'banner', 'contents' => fopen(__DIR__ . '/example_banner.png', 'r')],
                ['name' => 'price', 'contents' => $price],
                ['name' => 'amount', 'contents' => $amount],
            ],
            'query' => static::TEST_QUERY_PARAM,
        ]);

        $this->assertEquals(204, $response->getStatusCode(), 'Некорректный код ответа');

        /** @var string $contentType */
        $contentType = $response->getHeaders()['Content-Type'][0];
        $this->assertEquals('application/json', $contentType, 'Некорректный Content-Type ответа');

        /** @var Advertisement $advertisement */
        $advertisement = Advertisement::query()->where('id', 1)->first();
        $this->assertEquals($advertisement->text, $text, 'Текст объявления не обновлен');
        $this->assertEquals($advertisement->price, $price, 'Цена объявления не обновлена');
        $this->assertEquals($advertisement->amount, $amount, 'Лимит показов объявления не обновлен');
    }

    /**
     * Тестирует открутку рекламного объявления.
     *
     * @return void
     * @throws GuzzleException
     */
    public function testRun(): void
    {
        /** @var ResponseInterface $response */
        $response = $this->httpClient->request('POST', 'advertisement/run', [
            'query' => static::TEST_QUERY_PARAM,
        ]);

        $this->assertEquals(200, $response->getStatusCode(), 'Некорректный код ответа');
        $this->assertEquals('application/json', $response->getHeaders()['Content-Type'][0], 'Некорректный Content-Type ответа');

        $advertisementId = json_decode($response->getBody()->getContents(), true)['id'];
        /** @var int $advertisementDisplayCount Число откруток объявления */
        $advertisementDisplayCount = AdvertisementDisplay::query()
            ->where('advertisement_id', $advertisementId)
            ->first()
            ->display_count;
        $this->assertEquals(1, $advertisementDisplayCount, 'Счетчик просмотров объявления не инкрементирован');
    }

    /**
     * Тестирует получение рекламного объявления.
     *
     * @return void
     * @throws GuzzleException
     */
    public function testGet(): void
    {
        $response = $this->httpClient->get('advertisement/1', [
            'query' => static::TEST_QUERY_PARAM,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaders()['Content-Type'][0]);

        $advertisementId = json_decode($response->getBody()->getContents(), true)['id'];
        $this->assertEquals(1, $advertisementId, 'Получено объявление с несоответствующим id');
    }
}
