<?php

namespace App\Http\Controllers\V1;

use App\Models\Advertisement;
use App\Models\AdvertisementDisplay;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\UploadedFile;
use Laminas\Diactoros\Response;
use League\Route\Http\Exception\BadRequestException;
use ErrorException;
use App\Models\File as FileModel;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Контроллер рекламных объявлений.
 *
 * Class AdvertisementController
 * @package App\Http\Controllers\V1
 */
class AdvertisementController
{
    /** @var ValidatorInterface Валидатор данных из запросов */
    private $validator;

    /**
     * AdvertisementController constructor.
     */
    public function __construct()
    {
        $this->validator = Validation::createValidator();
    }

    /**
     * Получает рекламное объявление по идентификатору.
     *
     * @param ServerRequest $request
     * @param array $args Аргументы запроса
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function get(ServerRequest $request, array $args): ResponseInterface
    {
        /** @var int $advertisementId */
        $advertisementId = (int)$args['id'];
        /** @var Advertisement|null $advertisement */
        $advertisement = Advertisement::query()->where('id', $advertisementId)->first();

        if (!$advertisement) {
            throw new NotFoundException("Объявление с идентификатором $advertisementId не найдено");
        }

        $response = new Response();
        $response->getBody()->write(json_encode($advertisement->toArray()));
        return $response;
    }

    /**
     * Создает новое рекламное объявление в соответствии с настройками, заданными в запросе.
     *
     * @param ServerRequest $request
     * @return ResponseInterface
     * @throws BadRequestException
     * @throws ErrorException
     */
    public function create(ServerRequest $request): ResponseInterface
    {
        $this->validateForAdvertisementEntity($request);

        /** @var array $requestData Релевантные данные, полученные из запроса */
        $requestData = [
            'text' => (string)$request->getParsedBody()['text'],
            'amount' => (int)$request->getParsedBody()['amount'],
            'price' => (float)$request->getParsedBody()['price'],
            'banner_file_id' => $this->saveBanner($request->getUploadedFiles()['banner']),
        ];

        $advertisement = new Advertisement();
        $advertisement->fill($requestData);
        if (!$advertisement->save()) {
            throw new ErrorException('Ошибка при сохранении рекламного объявления');
        }

        $response = new Response();
        $response->getBody()->write(json_encode(['id' => $advertisement->id]));
        return $response->withStatus(201);
    }

    /**
     * Обновляет рекламное объявление в соответствии с настройками, заданными в запросе.
     *
     * @param ServerRequest $request
     * @param array $args
     * @return ResponseInterface
     * @throws BadRequestException
     * @throws ErrorException
     * @throws NotFoundException
     */
    public function update(ServerRequest $request, array $args): ResponseInterface
    {
        $this->validateForAdvertisementEntity($request);

        /** @var array $requestData Релевантные данные, полученные из запроса */
        $requestData = [
            'text' => (string)$request->getParsedBody()['text'],
            'amount' => (int)$request->getParsedBody()['amount'],
            'price' => (float)$request->getParsedBody()['price'],
            'banner_file_id' => $this->saveBanner($request->getUploadedFiles()['banner']),
        ];

        /** @var int $advertisementId */
        $advertisementId = (int)$args['id'];
        /** @var Advertisement|null $advertisement */
        $advertisement = Advertisement::query()->where('id', $advertisementId)->first();

        if (!$advertisement) {
            throw new NotFoundException("Объявление с идентификатором $advertisementId не найдено");
        }

        $advertisement->fill($requestData);
        if (!$advertisement->save()) {
            throw new ErrorException('Ошибка при сохранении рекламного объявления');
        }

        $response = new Response();
        $response->getBody()->write(json_encode(['id' => $advertisement->id]));
        return $response->withStatus(204);
    }

    /**
     * Инициирует открутку рекламного объявления.
     *
     * @param ServerRequest $request
     * @return ResponseInterface
     * @throws NotFoundException
     * @todo В реальных условиях для решения задачи в зависимости от нагрузки на API имело бы смысл
     *       во имя быстродействия рассмотреть примение таких инструментов, как Redis,
     *       а не строить всю функциональность вокруг ACID-БД
     */
    public function run(ServerRequest $request): ResponseInterface
    {
        /** @var Advertisement|null $advertisementToRun */
        $advertisementToRun = Advertisement::query()
            ->with(['banner'])
            ->where('active', true)
            ->whereRaw('price = (SELECT MAX(price) FROM advertisement a INNER JOIN advertisement_display ad ON a.id = ad.advertisement_id WHERE a.amount > ad.display_count)')
            ->first();

        if (!$advertisementToRun) {
            throw new NotFoundException('Нет доступного для открутки рекламного объявления');
        }

        AdvertisementDisplay::query()->where('advertisement_id', $advertisementToRun->id)->increment('display_count');

        $response = new Response();
        $response->getBody()->write(json_encode([
            'id' => $advertisementToRun->id,
            'text' => $advertisementToRun->text,
            'banner_file_id' => $advertisementToRun->banner_file_id,
            'banner_url' => $advertisementToRun->banner->url,
        ]));
        return $response;
    }

    /**
     * Сохраняет баннер и возвращает идентификатор соответствующего файла, сохраненного в таблице файлов.
     *
     * @param UploadedFile $bannerFile Загруженный файл с изображением-баннером
     * @return int Идентификатор файла, сохраненного в таблице файлов
     * @throws ErrorException
     */
    private function saveBanner(UploadedFile $bannerFile): int
    {
        /** @var string $bannerFileName Рандомизированное имя файла для сохранения на диске во избежание коллизий */
        $bannerFileName =
            substr(md5(rand()), 0, 12) . '.' .
            end(explode('.', $bannerFile->getClientFileName()));

        $bannerPath = '../storage/files/' . $bannerFileName;

        $bannerFile->moveTo($bannerPath);

        $file = new FileModel();
        $file->path = '/storage/files/' . $bannerFileName;
        if (!$file->save()) {
            throw new ErrorException('Ошибка при сохранении файла баннера в таблицу файлов');
        }
        /** @var int $bannerFileId Идентификатор сохраненного в таблицу файлов файла */
        $bannerFileId = $file->id;

        return $bannerFileId;
    }

    /**
     * Проверяет соответствие запроса базовым ограничениям, накладываемым на сущность рекламных объявлений.
     *
     * @param ServerRequest $request
     * @throws BadRequestException
     */
    private function validateForAdvertisementEntity(ServerRequest $request)
    {
        /** @var array $parsedBody Тело запроса */
        $parsedBody = $request->getParsedBody();
        /** @var array|UploadedFile[] $uploadedFiles Загруженные файлы */
        $uploadedFiles = $request->getUploadedFiles();

        $this->validateRequiredBody($parsedBody, ['text', 'amount', 'price']);
        $this->validateByRule($parsedBody, 'text', [new Length(['min' => 3, 'max' => 128]), new NotBlank()]);
        $this->validateByRule($parsedBody, 'amount', [new Type('digit'), new PositiveOrZero()]);
        $this->validateByRule($parsedBody, 'price', [new Positive()]);
        $this->validateFile($uploadedFiles, 'banner', ['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Осуществляет валидацию на наличие в запросе всех обязательных полей.
     *
     * @param array $parsedBody
     * @param string[] $requiredFields Список обязательных полей
     * @return void
     * @throws BadRequestException
     */
    private function validateRequiredBody(array $parsedBody, array $requiredFields): void
    {
        $errorFields = [];

        /** @var string $requiredField */
        foreach ($requiredFields as $requiredField) {
            if (!array_key_exists($requiredField, $parsedBody) || is_null($parsedBody[$requiredField])) {
                $errorFields[] = $requiredField;
            }
        }

        if (!empty($errorFields)) {
            throw new BadRequestException(
                'Не передано одно или несколько обязательных полей: ' . implode(', ', $errorFields)
            );
        }
    }

    /**
     * Осуществляет валидацию на соответствие данных переданным правилам.
     *
     * @param array $data Массив проверяемых данных
     * @param string $field Проверяемое поле
     * @param array $rules Правила валидации для данного поля
     * @throws BadRequestException
     */
    private function validateByRule(array $data, string $field, array $rules)
    {
        $violations = $this->validator->validate($data[$field], $rules);

        if ($violations->count()) {
            throw new BadRequestException("Некорректное значение поля $field");
        }
    }

    /**
     * Осуществляет валидацию файла.
     *
     * @param array $data
     * @param string $field
     * @param array|null $extensions
     * @throws BadRequestException
     */
    private function validateFile(array $data, string $field, ?array $extensions = null)
    {
        if (!isset($data[$field]) || !$data[$field] instanceof UploadedFile) {
            throw new BadRequestException("Поле $field не передано или не является файловым");
        }

        /** @var UploadedFile $file */
        $uploadedFile = $data[$field];
        if ($extensions && !in_array($uploadedFile->getClientMediaType(), $extensions)) {
            throw new BadRequestException(
                "Некорректное расширение файла $field. Используется {$uploadedFile->getClientMediaType()}, " .
                "допустим(ы) " . implode(', ', $extensions)
            );
        }
    }
}
