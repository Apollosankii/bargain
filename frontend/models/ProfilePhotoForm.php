<?php

declare(strict_types=1);

namespace frontend\models;

use common\models\User;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class ProfilePhotoForm extends Model
{
    public ?UploadedFile $photo = null;

    public function rules(): array
    {
        return [
            [['photo'], 'file', 'skipOnEmpty' => false, 'extensions' => ['png', 'jpg', 'jpeg', 'gif', 'webp'], 'maxSize' => 2 * 1024 * 1024, 'wrongExtension' => 'Please upload a PNG, JPG, GIF, or WebP image.', 'tooBig' => 'The photo must be 2 MB or smaller.'],
        ];
    }

    public function upload(User $user): bool
    {
        $this->photo = UploadedFile::getInstance($this, 'photo');

        if (!$this->validate()) {
            return false;
        }

        $relativeDir = 'uploads/profile';
        $absoluteDir = Yii::getAlias('@webroot/' . $relativeDir);

        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            Yii::error("Failed to create profile upload directory: {$absoluteDir}", __METHOD__);
            $this->addError('photo', 'Could not create the upload directory.');

            return false;
        }

        $extension = strtolower((string) $this->photo->extension);
        $filename = Yii::$app->security->generateRandomString(20) . '.' . $extension;
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;

        if (!$this->photo->saveAs($absolutePath)) {
            Yii::error("Failed to save uploaded profile photo to {$absolutePath}", __METHOD__);
            $this->addError('photo', 'Could not upload the profile photo.');

            return false;
        }

        $this->ensureWebReadable($absolutePath);

        $baseUrl = rtrim((string) Yii::$app->request->baseUrl, '/');
        $user->profile_photo = $baseUrl . '/' . $relativeDir . '/' . $filename;

        if (!$user->save(false, ['profile_photo'])) {
            Yii::error('Failed to save profile photo URL for user ' . $user->user_id, __METHOD__);
            $this->addError('photo', 'Could not save the profile photo.');

            return false;
        }

        return true;
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
