<?php

declare(strict_types=1);

namespace frontend\models;

use common\models\User;
use common\services\MailService;
use Yii;
use yii\base\Model;

/**
 * Signup form matching Bargain registration fields.
 */
class SignupForm extends Model
{
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_repeat = '';
    public string $role = '';
    public bool $agreeTerms = false;

    public function rules(): array
    {
        return [
            [['first_name', 'last_name', 'email', 'phone', 'password', 'password_repeat', 'role'], 'required'],
            [['first_name', 'last_name'], 'string', 'max' => 50],
            [['email'], 'trim'],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 100],
            [
                ['email'],
                'unique',
                'targetClass' => User::class,
                'targetAttribute' => 'email',
                'message' => 'An account with this email already exists.',
            ],
            [['phone'], 'string', 'max' => 20],
            [
                ['phone'],
                'unique',
                'targetClass' => User::class,
                'targetAttribute' => 'phone',
                'message' => 'This phone number is already registered.',
            ],
            [['password'], 'string', 'min' => 6],
            [['password_repeat'], 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
            [['role'], 'in', 'range' => [User::ROLE_BIDDER, User::ROLE_AUCTIONEER]],
            [['agreeTerms'], 'boolean'],
            [
                ['agreeTerms'],
                'compare',
                'compareValue' => true,
                'message' => 'You must agree to the Terms & Conditions before creating an account.',
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'phone' => 'Phone Number',
            'password' => 'Password',
            'password_repeat' => 'Confirm Password',
            'role' => 'Account Type',
            'agreeTerms' => 'I agree to the Terms & Conditions',
        ];
    }

    /**
     * Creates the user and logs them in (no email verification — matches Bargain).
     */
    public function signup(): ?User
    {
        if (!$this->validate()) {
            return null;
        }

        $user = new User();
        $user->first_name = trim($this->first_name);
        $user->last_name = trim($this->last_name);
        $user->email = mb_strtolower(trim($this->email));
        $user->phone = trim($this->phone);
        $user->role = $this->role;
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword($this->password);
        $user->generateAuthKey();

        if (!$user->save()) {
            return null;
        }

        MailService::welcome($user);

        return $user;
    }
}
