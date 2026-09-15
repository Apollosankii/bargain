<?php

declare(strict_types=1);

namespace frontend\models;

use common\models\Auction;
use common\models\Bid;
use common\models\User;
use yii\base\Model;

class BidForm extends Model
{
    public string|float|null $bid_amount = null;
    public int $auction_id = 0;

    public function rules(): array
    {
        return [
            [['bid_amount', 'auction_id'], 'required'],
            [['auction_id'], 'integer'],
            [['bid_amount'], 'number', 'min' => 0.01],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'bid_amount' => 'Your Bid (KES)',
        ];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public function place(User $bidder): array
    {
        if (!$this->validate()) {
            return ['ok' => false, 'message' => implode(' ', $this->getFirstErrors())];
        }

        $auction = Auction::findOne($this->auction_id);

        if ($auction === null) {
            return ['ok' => false, 'message' => 'Auction not found.'];
        }

        return Bid::placeBid($auction, $bidder, (float) $this->bid_amount);
    }
}
