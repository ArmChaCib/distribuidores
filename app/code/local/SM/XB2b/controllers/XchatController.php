<?php

class SM_XB2b_XchatController extends Mage_Core_Controller_Front_Action {

    protected function _getCurrentCustomer() {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    protected function _printJson($data = array(), $error = 0, $msg = '') {
        $result = array('error' => $error, 'data' => $data, 'msg' => $msg);
        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($result);
        exit();
    }

    /*
     * Send message from customer
     * */
    public function sAction() {
        $customerId     = $this->_getCurrentCustomer()->getId();
        $msg            = $this->getRequest()->getPost('msg', '');
        $adminId        = $this->getRequest()->getPost('supporter_id', '');

        if(empty($customerId) || empty($adminId) || empty($msg)) {
            $this->_printJson(null, 1, 'invalid input');
        }

        $chatModel  = Mage::getModel('xb2b/chat');

        $actorName  = $this->_getCurrentCustomer()->getFirstname() . ' ' .
                      $this->_getCurrentCustomer()->getLastname();

        $chatModel->createMessage($adminId, $customerId, 2, $customerId, $actorName, $msg);
    }

    /*
     * Update notification
     * */
//    public function uAction() {
//        $chatModel  = Mage::getModel('xb2b/chat');
//        $adminId    = $this->getRequest()->getPost('admin_id', 6);
//        $customerId = $this->_getCurrentCustomer()->getId();
//        $unread     = $chatModel->getUnreadCountByCustomerAndAdmin($customerId, $adminId);
//
//        if(!$unread) {
//            $unread = array();
//        }
//
//        $this->_printJson($unread);
//    }

    /*
     * Read messages
     * */
    public function rAction() {
        $supportId  = $this->getRequest()->getPost('support_id');
        $day        = $this->getRequest()->getParam('day');

        $today      = date('m_d_Y', time());
        $firstTime  = false;

        if(empty($day)) {
            $day = $today;
            $firstTime = true;
        }

        if(!$supportId) {
            $this->_printJson(null, 1, 'input is invalid');
        }

        $customerId = $this->_getCurrentCustomer()->getId();
        $chatModel  = Mage::getModel('xb2b/chat');

        $conversation = $chatModel->getConversationByDay($supportId, $customerId, $day);

        if($firstTime) {
            $dateArr = $chatModel->getConversationDateRange($supportId, $customerId);
        } else {
            $dateArr = array();
        }

        $data = array('data' => $conversation, 'date' => $dateArr);

        $this->_printJson($data);
    }

    /* Update messages */
    public function nAction() {
        $sList = $this->getRequest()->getParam('slist');

        $chatModel  = Mage::getModel('xb2b/chat');
        $customerId = $this->_getCurrentCustomer()->getId();

        $messages  = $chatModel->getUnreadMessagesByCustomer($customerId);
        $chatModel->markReadMessagesByCustomer($customerId);
        $chatModel->logCustomerActivate($customerId);

        if(!$messages) {
            $messages = array();
        }

        $data = array('message' => $messages, 'online' => $chatModel->getUserOnlineStatus($sList));

        $this->_printJson($data);
    }

}