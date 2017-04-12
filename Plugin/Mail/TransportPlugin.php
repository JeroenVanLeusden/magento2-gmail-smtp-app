<?php
/**
 * Mail Transport
 * Copyright © 2015-2017 MagePal. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagePal\GmailSmtpApp\Plugin\Mail;

class TransportPlugin extends \Zend_Mail_Transport_Smtp
{
    /** @var \MagePal\GmailSmtpApp\Helper\Data */
    protected $dataHelper;
    
    /**
     * @param \MagePal\GmailSmtpApp\Helper\Data $dataHelper
     */
    public function __construct(\MagePal\GmailSmtpApp\Helper\Data $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param \Magento\Framework\Mail\TransportInterface $subject
     * @param \Closure $proceed
     */
    public function aroundSendMessage(\Magento\Framework\Mail\TransportInterface $subject, \Closure $proceed){

        if($this->dataHelper->isActive()){
            $message = $subject->getMessage();
            $this->sendSmtpMessage($message);
        }
        else{
            $proceed();
        }
    }

    /**
     * @param \Magento\Framework\Mail\MessageInterface $message
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendSmtpMessage(\Magento\Framework\Mail\MessageInterface $message){
        //Set reply-to path
        $setReturnPath = $this->dataHelper->getConfigSetReturnPath();

        switch ($setReturnPath) {
            case 1:
                $returnPathEmail = $message->getFrom();
                break;
            case 2:
                $returnPathEmail = $this->dataHelper->getConfigReturnPathEmail();
                break;
            default:
                $returnPathEmail = null;
                break;
        }

        if ($returnPathEmail !== null && $this->dataHelper->getConfigSetReturnPath()) {
            $message->setReturnPath($returnPathEmail);
        }

        if ($message->getReplyTo() === NULL && $this->dataHelper->getConfigSetReplyTo()) {
            $message->setReplyTo($returnPathEmail);
        }

        //set config
        $smtpConf = [
            'name' => $this->dataHelper->getConfigName(),
            'auth' => strtolower($this->dataHelper->getConfigAuth()),
            'username' => $this->dataHelper->getConfigUsername(),
            'password' => $this->dataHelper->getConfigPassword(),
            'port' => $this->dataHelper->getConfigSmtpPort(),
        ];

        $ssl = $this->dataHelper->getConfigSsl();
        if ($ssl != 'none') {
            $smtpConf['ssl'] = $ssl;
        }

        $smtpHost = $this->dataHelper->getConfigSmtpHost();
        $this->initialize($smtpHost, $smtpConf);

        try {
            parent::send($message);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\MailException(new \Magento\Framework\Phrase($e->getMessage()), $e);
        }
    }

    /**
     * @param string $host
     * @param array $config
     */
    public function initialize($host = '127.0.0.1', Array $config = array())
    {
        if (isset($config['name'])) {
            $this->_name = $config['name'];
        }
        if (isset($config['port'])) {
            $this->_port = $config['port'];
        }
        if (isset($config['auth'])) {
            $this->_auth = $config['auth'];
        }

        $this->_host = $host;
        $this->_config = $config;
    }
}