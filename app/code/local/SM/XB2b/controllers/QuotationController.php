<?php
class SM_XB2b_QuotationController extends Mage_Core_Controller_Front_Action {

    private $_xb2b = null;

    protected function _grandAccess() {
        $this->_xb2b = Mage::helper('xb2b')->loadXB2BConfig();
        if(!$this->_xb2b || !$this->_xb2b['root_enable_b2b'] || !$this->_xb2b['customer_group_enabled_b2b'] || !$this->_xb2b['enable_quotation']) {
            $redirect_url = Mage::getUrl('');
            Mage::app()->getFrontController()->getResponse()->setRedirect($redirect_url);
            return false;
        }
        return true;
    }

    public function indexAction() {
        if($this->_grandAccess()) {
            $this->loadLayout();
            $this->renderLayout();
        }

    }

    public function contactAction() {
        if($this->_grandAccess()) {
            $this->loadLayout();
            $this->renderLayout();
        }
    }

    public function getQuoteDetailJsonAction() {
        $quoteId = $this->getRequest()->getParam('quote_id', '');
        $jsonResult = array('error' => 0, 'data' => array());

        if(empty($quoteId)) {
            $jsonResult['error'] = 1;
        }

        $quote = Mage::getModel('sales/quote')->load($quoteId);
        if(!$quote || !$quote->getId()) {
            $jsonResult['error'] = 1;
        }

        $coreHelper = Mage::helper('core');

        $items = $quote->getAllItems();
        $jsonResult['data']['items'] = array();
        foreach($items as $item) {
            $product = $item->getProduct();
            $jsonResult['data']['items'][] = array(
                'name'      => $product->getName(),
                'id'        => $product->getId(),
                'sku'       => $product->getSku(),
                'small'     => $product->getSmallImageUrl(),
                'url'       => $product->getProductUrl(),
                'ord_qty'   => $item->getQty(),
                'price'     => $coreHelper->currency($item->getPrice(), true, false)
            );
        }


        $jsonResult['data']['price'] = array(
            'subtotal'      => $coreHelper->currency($quote->getSubtotal(), true, false),
            'grand_total'   => $coreHelper->currency($quote->getGrandTotal(), true, false)
        );

        //Get comments
        $commentCollection = Mage::getModel('xb2b/quotecomment')->getCollection()
                                                                ->addFieldToFilter('quote_id', $quoteId);
        $comments = array();

        foreach($commentCollection as $comment) {
            $name       = '';
            $ownerType  = $comment->getOwnerType();
            $ownerId    = $comment->getOwnerId();

            if($ownerType == 1) {
                $customer = Mage::getModel('customer/customer')->load($ownerId);
                $name = $customer->getFirstname() . ' ' . $customer->getLastname() . ' at '.$coreHelper->formatTime($comment->getDate()) . ' ' .$coreHelper->formatDate($comment->getDate());
            } else if($ownerType == 0) {
                $admin = Mage::getModel('admin/user')->load($ownerId);
                $name = $admin->getFirstname() . ' ' . $admin->getLastname() . '(supporter) at '.$coreHelper->formatTime($comment->getDate()) . ' ' .$coreHelper->formatDate($comment->getDate());
            }
            $comments[] = array(
                'name'  => $name,
                'msg'   => $comment->getContent()
            );
        }
        $jsonResult['data']['comments'] = $comments;

        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($jsonResult);
    }

    public function denyQuoteAction() {
        $quotationId    = $this->getRequest()->getParam('quotation_id');
        $quotation      = Mage::getModel('xb2b/quotation')->load($quotationId);
        $quotation->setQuotationStatus(0);
        $quotation->save();
    }

    public function setQuoteAcceptedAction() {
        $quotationId    = $this->getRequest()->getParam('quotation_id');
        $quotation      = Mage::getModel('xb2b/quotation')->load($quotationId);
        $quotation->setQuotationStatus(2);
        $quotation->save();
    }

    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    protected function _getOrderCreateModel()
    {
        return Mage::getSingleton('xb2b/sales_create');
    }

    protected function _getCustomer() {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    public function gotoCheckoutWithQuoteAction() {
        $quoteId = $this->getRequest()->getParam('quote_id');
        $jsonResult = array('error' => 0);

        if(empty($quoteId)) {
            $jsonResult['error'] = 1;
        }

        $currQuote = $this->_getQuote();

        //Remove all item
        $cart = $this->_getCart();
        $items = $cart->getItems();
        foreach ($items as $item)
        {
            $itemId = $item->getItemId();
            $cart->removeItem($itemId);
        }

        //Get quote info
        $quote = Mage::getModel('sales/quote')->load($quoteId);

        $couponCode = $quote->getCouponCode();

        $quoteItems = $quote->getAllItems();
        foreach($quoteItems as $item) {
            try {
                $cart->addProduct($item->getProduct(), $item->getQty());
            } catch(Exception $e) { }

            $addedItem = $currQuote->getItemByProduct($item->getProduct());
            $addedItem->setCustomPrice($item->getPrice());
            $addedItem->setOriginalCustomPrice($item->getPrice());
        }
        $cart->getQuote()->setCouponCode($couponCode)->save();
        $cart->save();
        $this->_getSession()->setCartWasUpdated(true);
        
        //Add flag to current session
        Mage::getSingleton('core/session')->setHasQuotation(1);
        
        //Set quote accepted
//        $quotationId    = $this->getRequest()->getParam('quotation_id');
//        $quotation      = Mage::getModel('xb2b/quotation')->load($quotationId);
//        $quotation->setQuotationStatus(2);
//        $quotation->save();

        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($jsonResult);
    }

    public function requestCustomerQuoteAction() {
        $quote = $this->_getQuote();
        $quotation = Mage::getModel('xb2b/quotation');

        $data['quote_id'] = $quote->getId();
        $data['quotation_status'] = 3;
        $data['customer_id'] = $this->_getCustomer()->getId();
        $data['expired_date'] = strtotime($this->getRequest()->getParam('expired_date'));
        $data['create_date'] = time();
        $data['update_date'] = time();
        $quotation->setData($data);

        //Save quotation and deactivate the current quote
        $quotation->save();
        $quote->setIsActive(false);
        $quote->save();

        $comment = $this->getRequest()->getParam('comment');
        $quoteComment = Mage::getModel('xb2b/quotecomment');
        $quoteComment->setData(array(
            'owner_id'      => $this->_getCustomer()->getId(),
            'owner_type'    => 1,
            'quote_id'      => $quote->getId(),
            'quotation_id'  => $quotation->getId(),
            'content'       => $comment,
            'date'          => time()
        ));
        $quoteComment->save();
        $jsonResult = array('error' => 0, 'quotation_id' => $quotation->getId());
        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($jsonResult);
    }

    public function sendContactAction(){
        $customer_data = Mage::getSingleton('customer/session')->getCustomer();
        $customer_id = $customer_data->getData('entity_id');
        $customer_email = $customer_data->getData('email');
        $customer_name = $customer_data->getData('firstname') . " " . $customer_data->getData('middlename') . " " . $customer_data->getData('lastname');

        $contact_content = $this->getRequest()->getParam('contact_content');
        $contact_time = $this->getRequest()->getParam('contact_time');
        $contact_mobile = $this->getRequest()->getParam('contact_mobile');

        $model = Mage::getModel('admin/user');
        $collection = $model->getCollection();
        $collection->getSelect()
            ->join(Mage::getConfig()->getTablePrefix().'sm_assignment', 'main_table.user_id ='.Mage::getConfig()->getTablePrefix().'sm_assignment.user_id',array('customer_id'))
            ->where("customer_id = " . $customer_id)
            ->group("user_id");

        $content =  "Client has requested a call-back. \r\n";
        $content =  $content . "Customer: " . $customer_name . ".\r\n";
        $content =  $content . "Customer ID: " . $customer_id . ".\r\n";
        $content =  $content . "Phone number: " . $contact_mobile . ".\r\n";
        $content =  $content . "Time contact: " . $contact_time . ".\r\n";
        $content =  $content . "Note: " . $contact_content . ".\r\n";
        $body = $content;

        $list_emails = array();
        foreach($collection as $admin){

            $list_emails[] = $admin->getEmail();
            $admin_name = $admin->getData('firstname') . " " . $admin->getData('lastname');

            $mail = Mage::getModel('core/email');
            $mail->setToName($admin_name);
            $mail->setToEmail($admin->getEmail());
            $mail->setBody($body);
            $mail->setSubject('Call-back request From '. $customer_name);
            $mail->setFromEmail($customer_name);
            $mail->setFromName($customer_email);
            $mail->setType('text');// You can use 'html' or 'text'

            try {
                $mail->send();
            }
            catch (Exception $e) {
                echo $e->getMessage(); die;
            }

        }

        echo "oke";die;
    }
}