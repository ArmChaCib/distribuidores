<?php

class SM_Xb2b_Adminhtml_XchatController extends Mage_Adminhtml_Controller_Action {

    protected function _getCurrentAdmin() {
        return Mage::getSingleton('admin/session')->getUser();
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
        $customerId     = $this->getRequest()->getParam('customer_id', 0);
        $msg            = $this->getRequest()->getParam('msg', '');
        $adminId        = $this->_getCurrentAdmin()->getId();

        if(empty($customerId) || empty($adminId) || empty($msg)) {
            $this->_printJson(null, 1, 'invalid input');
        }

        $chatModel  = Mage::getModel('xb2b/chat');

        $actorName  = $this->_getCurrentAdmin()->getFirstname() . '' . $this->_getCurrentAdmin()->getLastname();

        $chatModel->createMessage($adminId, $customerId, 1, $adminId, $actorName, $msg);
    }

    /*
     * Update notification
     * */
    public function uAction() {
        $chatModel  = Mage::getModel('xb2b/chat');
        $adminId    = $this->_getCurrentAdmin()->getId();
        $unread     = $chatModel->getUnreadCountByAdmin($adminId);
        if(!$unread) {
            $unread = array();
        }

        $this->_printJson($unread);
    }

    /*
     * Read messages
     * */
    public function rAction() {
        $customerId = $this->getRequest()->getParam('cid');
        $day        = $this->getRequest()->getParam('day');

        $today      = date('m_d_Y', time());
        $firstTime  = false;

        if(empty($day)) {
            $day = $today;
            $firstTime = true;
        }


        if(!$customerId) {
            $this->_printJson(null, 1, 'undefined customer');
        }

        $adminId    = $this->_getCurrentAdmin()->getId();
        $chatModel  = Mage::getModel('xb2b/chat');

        $conversation   = $chatModel->getConversationByDay($adminId, $customerId, $day);

        if($firstTime) {
            $dateArr = $chatModel->getConversationDateRange($adminId, $customerId);
        } else {
            $dateArr = array();
        }

        $data = array('data' => $conversation, 'date' => $dateArr);
        $this->_printJson($data);
    }

    public function nAction() {
        $cList = $this->getRequest()->getParam('clist');

        $chatModel  = Mage::getModel('xb2b/chat');
        $adminId    = $this->_getCurrentAdmin()->getId();
        $messages   = $chatModel->getUnreadMessagesByAdmin($adminId);
                      $chatModel->markReadMessagesByAdmin($adminId);

        $chatModel->logUserActivate($adminId);

        if(!$messages) {
            $messages = array();
        }

        $data = array('message' => $messages, 'online' => $chatModel->getCustomerOnlineStatus($cList));

        $this->_printJson($data);
    }

}