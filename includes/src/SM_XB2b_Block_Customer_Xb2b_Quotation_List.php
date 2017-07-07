<?php
class SM_XB2b_Block_Customer_Xb2b_Quotation_List extends Mage_Core_Block_Template {

    private $itemPerPage = 10;

    public function getFormKey() {
        return Mage::getSingleton('core/session')->getFormKey();
    }

    public function getQuotes() {
        $page = $this->getRequest()->getParam('page', 1);
        $customerSession        = Mage::getSingleton("customer/session");
        $currentCustomer    = $customerSession->getCustomer();

        $quotes             = Mage::getModel('xb2b/quotation')
                                    ->getCollection()
                                    ->addFieldToFilter('customer_id', $currentCustomer->getId())
                                    ->setOrder('create_date', 'DESC')
                                    ->setPageSize($this->itemPerPage)
                                    ->setCurPage($page);
        return $quotes;
    }

    public function getNumberOfPages() {
        $customerSession    = Mage::getSingleton("customer/session");
        $currentCustomer    = $customerSession->getCustomer();

        $count = Mage::getModel('xb2b/quotation')
                    ->getCollection()
                    ->addFieldToFilter('customer_id', $currentCustomer->getId())->getSize();

        return ceil($count / $this->itemPerPage);
    }

    public function getCurrentPageNumb() {
        return $this->getRequest()->getParam('page', 1);
    }




}