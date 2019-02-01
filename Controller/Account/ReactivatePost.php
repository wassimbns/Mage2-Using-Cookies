<?php

namespace Farmasi\Customer\Controller\Account;

use Farmasi\Sync\Helper\Data;


/**
 * Class ReactivatePost
 * @package Farmasi\Customer\Controller\Account
 */
class ReactivatePost extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    public $_cookieManager;
    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    public $_cookieMetadataFactory;

    const COOKIE_NAME = 'FARMASI-REACTIVATE';
    const COOKIE_DURATION = 600000; // lifetime in seconds set for 1 week
    const COOKIE_VALUE = 1;


    /**
     * ReactivatePost constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        Data $helper

    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $codetsi = $data['code_tsi'];
        $codeParrain = $data['code_parrain'];
        $phone = $data['telephone'];
        $data = array(
            'CodeTSI' => $codetsi,
            'Telephone' => $phone,
            'CodeTSIParrain' => $codeParrain
        );

        // @todo later  Tests sur les entrées
        $url = $this->helper->getWsdUpdateUrl();
        try {
            $authorization = $this->helper->getAuthorization();
            if ($authorization) {
                $opts = array(
                    'http' => array(
                        'header' => 'Authorization: ' . $authorization
                    )
                );
                $context = stream_context_create($opts);
                $client = new \SoapClient($url, [
                    'exceptions' => TRUE,
                    'soap_version' => SOAP_1_2,
                    'stream_context' => $context
                ]);
            }
            else {
                $client = new \SoapClient($url, ['exceptions' => TRUE]);
            }
            $result = $client->ActivationInactifs($data);
            $this->messageManager->addSuccessMessage('Votre demande a été prise en compte.');

            // @todo create cookie with name : FARMASI-REACTIVATE / value = 1
            $metadata = $this->_cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration(self::COOKIE_DURATION)
                ->setPath('/');
            $this->_cookieManager->setPublicCookie(
                self::COOKIE_NAME,
                self::COOKIE_VALUE,
                $metadata
            );

        } catch (\SoapFault $sf) {
            $this->messageManager->addErrorMessage($sf->getMessage());
        } catch (\Exception $ex) {
            $this->messageManager->addErrorMessage($ex->getMessage());
        }
        $this->_redirect('customer/account/login');
    }
}
