<?php

declare(strict_types=1);

namespace common\models;

use Yii;
use yii\base\Model;

/**
 * Login form (email + password).
 */
class LoginForm extends Model
{
    public string $email = '';
    public string $password = '';
    public bool $rememberMe = true;

    private User|null $_user = null;

    public function rules(): array
    {
        return [
            [['email', 'password'], 'required'],
            ['email', 'email'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'email' => 'Email',
            'password' => 'Password',
            'rememberMe' => 'Remember me',
        ];
    }

    public function validatePassword(string $attribute, array|null $params): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();

        if ($user === null) {
            $this->addError($attribute, 'Invalid email or password.');

            return;
        }

        // Inactive accounts are excluded by findByEmail; check raw row for clearer message.
        $inactive = User::find()
            ->where(['email' => mb_strtolower(trim($this->email)), 'status' => User::STATUS_INACTIVE])
            ->exists();

        if ($inactive) {
            $this->addError($attribute, 'Your account has been deactivated. Please contact support.');

            return;
        }

        if (!$user->validatePassword($this->password)) {
            $this->addError($attribute, 'Invalid email or password.');
        }
    }

    public function login(): bool
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
        }

        return false;
    }

    protected function getUser(): User|null
    {
        if ($this->_user === null) {
            $this->_user = User::findByEmail($this->email);
        }

        return $this->_user;
    }
}
