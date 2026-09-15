<?php

declare(strict_types=1);

namespace frontend\models;

use common\models\Auction;
use common\models\AuctionImage;
use common\models\Category;
use common\models\User;
use common\services\MailService;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class AuctionForm extends Model
{
    public const MIN_IMAGES = 3;
    public const MAX_IMAGES = 8;

    public string $title = '';
    public string $description = '';
    public string|int|null $category_id = null;
    public string|float|null $starting_bid = null;
    public string $start_time = '';
    public string $end_time = '';

    /** @var UploadedFile[] */
    public array $imageFiles = [];

    public function rules(): array
    {
        return [
            [['title', 'category_id', 'starting_bid', 'start_time', 'end_time'], 'required'],
            [['title'], 'string', 'max' => 200],
            [['description'], 'string'],
            [['category_id'], 'integer'],
            [
                ['category_id'],
                'exist',
                'targetClass' => Category::class,
                'targetAttribute' => ['category_id' => 'category_id'],
            ],
            [['starting_bid'], 'number', 'min' => 1],
            [['start_time', 'end_time'], 'safe'],
            [['end_time'], 'validateEndAfterStart'],
            [
                ['imageFiles'],
                'file',
                'skipOnEmpty' => false,
                'extensions' => ['png', 'jpg', 'jpeg', 'gif', 'webp'],
                'maxSize' => 5 * 1024 * 1024,
                'maxFiles' => self::MAX_IMAGES,
                'wrongExtension' => 'Please upload PNG, JPG, GIF, or WebP images only.',
                'tooBig' => 'Each image must be 5 MB or smaller.',
                'tooMany' => 'You can upload at most ' . self::MAX_IMAGES . ' images.',
            ],
            [['imageFiles'], 'validateImageCount'],
        ];
    }

    public function validateEndAfterStart(string $attribute): void
    {
        if ($this->start_time === '' || $this->end_time === '') {
            return;
        }

        if (strtotime($this->end_time) <= strtotime($this->start_time)) {
            $this->addError($attribute, 'End time must be after start time.');
        }
    }

    public function validateImageCount(string $attribute): void
    {
        $count = count($this->imageFiles);
        if ($count < self::MIN_IMAGES) {
            $this->addError(
                $attribute,
                'Upload at least ' . self::MIN_IMAGES . ' photos from different angles.',
            );
        }
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Title',
            'description' => 'Description',
            'category_id' => 'Category',
            'imageFiles' => 'Auction Photos',
            'starting_bid' => 'Starting Bid (KES)',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
        ];
    }

    public function create(User $auctioneer): ?Auction
    {
        $this->imageFiles = UploadedFile::getInstances($this, 'imageFiles');

        if (!$this->validate()) {
            return null;
        }

        $tx = Yii::$app->db->beginTransaction();

        try {
            $auction = new Auction();
            $auction->auctioneer_id = $auctioneer->user_id;
            $auction->category_id = (int) $this->category_id;
            $auction->title = trim($this->title);
            $auction->description = trim($this->description) !== '' ? trim($this->description) : null;
            $auction->image_url = null;
            $auction->starting_bid = (float) $this->starting_bid;
            $auction->current_bid = (float) $this->starting_bid;
            $auction->start_time = date('Y-m-d H:i:s', strtotime($this->start_time));
            $auction->end_time = date('Y-m-d H:i:s', strtotime($this->end_time));
            $auction->status = Auction::STATUS_ACTIVE;

            if (!$auction->save()) {
                $this->addError('title', 'Could not create auction.');
                $tx->rollBack();

                return null;
            }

            $urls = [];
            foreach ($this->imageFiles as $index => $file) {
                $url = $this->storeUploadedImage($file);
                if ($url === null) {
                    $this->addError('imageFiles', 'Could not upload one of the images. Please try again.');
                    $tx->rollBack();

                    return null;
                }

                $image = new AuctionImage();
                $image->auction_id = (int) $auction->auction_id;
                $image->image_url = $url;
                $image->sort_order = $index;
                if (!$image->save()) {
                    $this->addError('imageFiles', 'Could not save auction photos.');
                    $tx->rollBack();

                    return null;
                }

                $urls[] = $url;
            }

            $auction->image_url = $urls[0];
            $auction->save(false, ['image_url']);

            $tx->commit();

            MailService::auctionCreated($auctioneer, $auction);

            return $auction;
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            $this->addError('title', 'Could not create auction.');

            return null;
        }
    }

    private function storeUploadedImage(UploadedFile $file): ?string
    {
        $relativeDir = 'uploads/auctions';
        $absoluteDir = Yii::getAlias('@webroot/' . $relativeDir);

        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            Yii::error("Failed to create upload directory: {$absoluteDir}", __METHOD__);

            return null;
        }

        $extension = strtolower((string) $file->extension);
        $filename = Yii::$app->security->generateRandomString(20) . '.' . $extension;
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;

        if (!$file->saveAs($absolutePath)) {
            Yii::error("Failed to save uploaded image to {$absolutePath}", __METHOD__);

            return null;
        }

        $this->ensureWebReadable($absolutePath);

        $baseUrl = rtrim((string) Yii::$app->request->baseUrl, '/');

        return $baseUrl . '/' . $relativeDir . '/' . $filename;
    }

    private function ensureWebReadable(string $absolutePath): void
    {
        @chmod($absolutePath, 0644);

        if (strncasecmp(PHP_OS, 'WIN', 3) !== 0 || !is_file($absolutePath)) {
            return;
        }

        $escaped = '"' . str_replace('"', '', $absolutePath) . '"';
        @exec('icacls ' . $escaped . ' /grant "IUSR:(R)" /grant "IIS_IUSRS:(R)" /grant "Users:(R)" /Q');
    }
}
