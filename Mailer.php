<?php

namespace talma\sendgrid;

use SendGrid\Response;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\BaseArrayHelper;
use yii\mail\BaseMailer;

/**
 * Mailer implements a mailer based on SendGrid.
 *
 * To use Mailer, you should configure it in the application configuration. See README for more information.
 *
 * @see https://github.com/sendgrid/sendgrid-php
 * @package talma\sendgrid
 *
 * @property \SendGrid $sendGridMailer
 */
class Mailer extends BaseMailer
{
    /**
     * @var string message default class name.
     */
    public $messageClass = 'talma\sendgrid\Message';

    /**
     * @var string key for the sendgrid api
     */
    public $key;

    /**
     * @var array a list of options for the sendgrid api
     */
    public $options = [];

    /**
     * @var string Send grid mailer instance
     */
    private $_sendGridMailer;

    /**
     * @return \SendGrid Send grid mailer instance
     */
    public function getSendGridMailer()
    {
        if (!is_object($this->_sendGridMailer)) {
            $this->_sendGridMailer = $this->createSendGridMailer();
        }

        return $this->_sendGridMailer;
    }

    /**
     * Create send grid mail instance with stored params
     *
     * @return \SendGrid
     * @throws \yii\base\InvalidConfigException
     */
    public function createSendGridMailer()
    {
        if ($this->key) {
            $sendgrid = new \SendGrid($this->key, $this->options);
        } else {
            throw new InvalidConfigException("You must configure mailer.");
        }

        return $sendgrid;
    }

    /**
     * @param array|Message $message
     *
     * @return bool
     * @throws \Exception
     */
    public function sendMessage($message)
    {
        if (!($message instanceof Message) && !(BaseArrayHelper::isAssociative($message))) {
            throw new InvalidParamException('Parâmetro $message deve ser um array associativo ou instância de \talma\sendgrid\Message');
        }

        $data = ($message instanceof Message) ? $message->sendGridMessage : $message;

        /** @noinspection PhpUndefinedMethodInspection */
        /** @var Response $response */
        $response = $this->sendGridMailer->client->mail()->send()->post($data);

        if ($response) {
            if (empty($response->statusCode())) {
                throw new \Exception('Invalid SendGrid response');
            }

            return $this->isResponseOk($response);
        }

        return false;
    }

    /**
     * @param $response
     *
     * @return bool
     */
    public function isResponseOk(Response $response)
    {
        return $response->statusCode() >= 200 && $response->statusCode() < 300;
    }
}
