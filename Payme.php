<?php

namespace uzdevid\payme;

use Yii;
use yii\base\Component;
use yii\base\InvalidValueException;

class Payme extends Component {
    public $merchant;
    public $mode = 'dev';
    public $_dev_url = 'https://test.paycom.uz';
    public $_url = 'https://checkout.paycom.uz';

    public function __construct($config = []) {
        parent::__construct($config);

        if (empty($this->merchant))
            throw new InvalidValueException('merchant ID not set or empty');
    }

    public function sendInvoiceByGet($data) {
        $params = [
            'm' => $this->merchant,
            'ac' => $data['account'],
            'a' => $data['amount'],
            'i' => empty($data['lang']) ? Yii::$app->language : $data['lang'],
            'c' => $data['callback'],
            'ct' => empty($data['callback_timeout']) ? 15 : $data['callback_timeout'],
            'cr' => empty($data['currency']) ? '' : $data['currency'],
            'd' => empty($data['description']) ? '' : $data['description'],
            'ds' => empty($data['detail']) ? '' : $data['detail'],
        ];

        $params = base64_encode(http_build_query($params, '', ';'));

        if ($this->mode == 'prod')
            $url = $this->_url . '/' . $params;
        else
            $url = $this->_dev_url . '/' . $params;

        return $url;
    }
}
