<?php

namespace talma\sendgrid;

use SendGrid\Content;
use SendGrid\Email;
use SendGrid\Mail;
use SendGrid\Personalization;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseArrayHelper;
use yii\helpers\Json;
use yii\mail\BaseMessage;

/**
 * Message implementation
 *
 * @package talma\sendgrid
 *
 * @property Mail $sendGridMessage
 */
class Message extends BaseMessage
{
    /**
     * @var Mail
     */
    private $_sendGridMessage;

    /**
     * @return Mail
     */
    public function getSendGridMessage()
    {
        if ($this->_sendGridMessage == null) {
            $this->_sendGridMessage = new Mail();
            if (empty($this->_sendGridMessage->getPersonalizations())) {
                $this->_sendGridMessage->addPersonalization(new Personalization());
            }
        }

        return $this->_sendGridMessage;
    }

    /**
     * @param string $templateId sendGrid template id
     * @param array [key => value] array for sendGrid substition
     *
     * @return $this
     */
    public function setSendGridSubstitution($templateId, array $templateSubstitution = [])
    {
        if (BaseArrayHelper::isAssociative($templateSubstitution)) {
            foreach ($templateSubstitution as $key => $value) {
                $this->getPersonalization()->addSubstitution($key, $value);
            }
        }

        $this->getSendGridMessage()->setTemplateId($templateId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCharset()
    {
        // not available on sendgrid
    }

    /**
     * @inheritdoc
     */
    public function setCharset($charset)
    {
        // not available on sendgrid
    }

    /**
     * @inheritdoc
     */
    public function getFrom()
    {
        return $this->getSendGridMessage()->getFrom();
    }

    /**
     * @inheritdoc
     */
    public function setFrom($from)
    {
        if (is_array($from) && BaseArrayHelper::isAssociative($from)) {
            $mailAddress = key($from);
            $name = current($from);
            $email = new Email($name, $mailAddress);
        } else {
            $email = new Email(null, $from);
        }

        $this->getSendGridMessage()->setFrom($email);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getReplyTo()
    {
        return $this->getSendGridMessage()->getReplyTo();
    }

    /**
     * @inheritdoc
     */
    public function setReplyTo($replyTo)
    {
        $this->getSendGridMessage()->setReplyTo($replyTo);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTo()
    {
        return $this->getPersonalization()->getTos();
    }

    /**
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @inheritdoc
     */
    public function setTo($to)
    {
        $this->addEmailParam($to, 'to');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCc()
    {

        return $this->getPersonalization()->getCcs();
    }

    /**
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @inheritdoc
     */
    public function setCc($cc)
    {
        $this->addEmailParam($cc, 'cc');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBcc()
    {
        return $this->getPersonalization()->getBccs();
    }

    /**
     * @inheritdoc
     */
    public function setBcc($bcc)
    {
        $this->addEmailParam($bcc, 'bcc');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSubject()
    {
        return $this->getSendGridMessage()->getSubject();
    }

    /**
     * @inheritdoc
     */
    public function setSubject($subject)
    {
        $this->getSendGridMessage()->setSubject($subject);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setTextBody($text)
    {
        $content = new Content("text/plain", $text);
        $this->getSendGridMessage()->addContent($content);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setHtmlBody($html)
    {
        if (!empty($html)) {
            $content = new Content("text/html", $html);
            $this->getSendGridMessage()->addContent($content);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function attach($fileName, array $options = [])
    {
        $this->getSendGridMessage()->addAttachment($fileName);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function attachContent($content, array $options = [])
    {
        // no available method for sendgrid
    }

    /**
     * @inheritdoc
     */
    public function embed($fileName, array $options = [])
    {
        // no available method for sendgrid
    }

    /**
     * @inheritdoc
     */
    public function embedContent($content, array $options = [])
    {
        $filename = ArrayHelper::getValue($options, 'filename', '');
        $attachment = array_merge($options, ['content' => $content, 'filename' => $filename]);
        $this->getSendGridMessage()->addAttachment($attachment);

        return ArrayHelper::getValue($options, 'content_id', '');
    }

    /**
     * Add categories to message
     *
     * @param array $categories Categories to be added
     *
     * @return $this
     */
    public function addCategories(array $categories = [])
    {
        foreach ($categories as $category) {
            if (!empty($this->getSendGridMessage()->categories) && in_array($category, $this->getSendGridMessage()->categories)) {
                continue;
            }

            $this->getSendGridMessage()->addCategory($category);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toString()
    {
        return Json::encode($this->getSendGridMessage()->jsonSerialize());
    }

    /**
     * @return Personalization
     */
    protected function getPersonalization()
    {
        return $this->getSendGridMessage()->getPersonalizations()[0];
    }

    /**
     * @param string|array $paramValue ['email' => 'name'] or 'email'
     * @param string $paramType sendGrid var name like cc, bcc, to
     *
     * @return $this
     */
    private function addEmailParam($paramValue, $paramType)
    {
        if (is_array($paramValue)) {
            foreach ($paramValue as $key => $value) {
                $this->addSingleParam([$key => $value], $paramType);
            }
        } else {
            $this->addSingleParam($paramValue, $paramType);
        }

        return $this;
    }

    /**
     * @param $paramValue
     * @param $paramType
     */
    private function addSingleParam($paramValue, $paramType)
    {
        $addFunction = 'add' . ucfirst($paramType);
        if (is_array($paramValue)) {
            if (BaseArrayHelper::isAssociative($paramValue)) {
                $address = key($paramValue);
                $name = current($paramValue);
                $this->addEmailFunctionParams($addFunction, $address, $name);
            } else {
                $address = current($paramValue);
                $this->addEmailFunctionParams($addFunction, $address);
            }
        } else {
            $this->addEmailFunctionParams($addFunction, $paramValue);
        }
    }

    /**
     * @param $addFunction
     * @param $address
     * @param null $name
     */
    private function addEmailFunctionParams($addFunction, $address, $name = null)
    {
        $email = new Email($name, $address);
        $this->getPersonalization()->$addFunction($email);
    }
}
