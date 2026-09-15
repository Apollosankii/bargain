<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\LoginForm;
use common\models\Auction;
use common\models\User;
use frontend\models\ContactForm;
use frontend\models\SignupForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\mail\MailerInterface;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

/**
 * Site controller — Bargain landing + auth.
 */
class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly MailerInterface $mailer,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }

    public function actionIndex(): string|Response
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirectAfterLogin(Yii::$app->user->identity);
        }

        return $this->renderMarketing('index', [
            'featuredAuctions' => Auction::findEndingSoon(4),
        ]);
    }

    public function actionLogin(): string|Response
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirectAfterLogin(Yii::$app->user->identity);
        }

        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            Yii::$app->session->setFlash('success', 'Welcome back, ' . Yii::$app->user->identity->first_name . '!');

            return $this->redirectAfterLogin(Yii::$app->user->identity);
        }

        $model->password = '';

        return $this->renderMarketing('login', [
            'model' => $model,
            'heroAuction' => Auction::findEndingSoon(1)[0] ?? null,
        ]);
    }

    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionSignup(): string|Response
    {
        $model = new SignupForm();

        if ($model->load(Yii::$app->request->post())) {
            $user = $model->signup();

            if ($user !== null) {
                Yii::$app->user->login($user, 3600 * 24 * 30);
                Yii::$app->session->setFlash(
                    'success',
                    "Welcome to Bargain, {$user->first_name}! Your account has been created.",
                );

                return $this->redirectAfterLogin($user);
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    public function actionContact(): string|Response
    {
        $model = new ContactForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $sent = $model->sendEmail(
                $this->mailer,
                Yii::$app->params['adminEmail'],
                Yii::$app->params['senderEmail'],
                Yii::$app->params['senderName'],
            );

            if ($sent) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    public function actionAbout(): string
    {
        return $this->renderMarketing('about');
    }

    public function actionHowItWorks(): string
    {
        return $this->renderMarketing('how-it-works', [
            'featuredAuctions' => Auction::findEndingSoon(4),
        ]);
    }

    public function actionTerms(): string
    {
        return $this->renderMarketing('terms');
    }

    /**
     * Marketing pages stay dark-only (no light theme / toggle).
     *
     * @param array<string, mixed> $params
     */
    private function renderMarketing(string $view, array $params = []): string
    {
        $this->view->params['forceDarkTheme'] = true;
        $this->view->params['landingLayout'] = true;

        return $this->render($view, $params);
    }

    private function redirectAfterLogin(User $user): Response
    {
        return match ($user->role) {
            User::ROLE_AUCTIONEER => $user->requiresSubscriptionGate()
                ? $this->redirect(['/subscription/choose'])
                : $this->redirect(['/dashboard/auctioneer']),
            User::ROLE_ADMIN => $this->redirect(['/dashboard/admin']),
            default => $this->redirect(['/dashboard/bidder']),
        };
    }
}
