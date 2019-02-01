<?php

namespace Farmasi\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;


class CustomerLoginObserver implements ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    protected $_responseFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $_cookieManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * CustomerLoginObserver constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\ResponseFactory $responseFactory
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->customerSession = $customerSession;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
        $this->_cookieManager = $cookieManager;
        $this->_messageManager = $messageManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $ambassadorStatus = $customer->getData('ambassador_status');
        if ($ambassadorStatus != NULL && (int) $ambassadorStatus === 0)
        {
            $this->customerSession->logout();
            $cookieValue = $this->_cookieManager->getCookie(\Farmasi\Customer\Controller\Account\ReactivatePost::COOKIE_NAME);
            if (!isset($cookieValue) || $cookieValue == null) {
                $redirectionUrl = $this->_url->getUrl('ffarmasi/account/reactivate');
            } else {
                $redirectionUrl = $this->_url->getUrl('customer/account/login');
                $this->_messageManager->addSuccessMessage('Votre compte n\'est pas encore activé. Veuillez essayer plus tard');

            }
            $this->_responseFactory->create()->setRedirect($redirectionUrl)->sendResponse()->save();
        }
        return $this;
    }
}