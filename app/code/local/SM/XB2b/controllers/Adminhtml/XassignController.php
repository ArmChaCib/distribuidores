<?php

class SM_Xb2b_Adminhtml_XassignController extends Mage_Adminhtml_Controller_Action {

    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    protected function _getCurrentAdmin() {
        return Mage::getSingleton('admin/session')->getUser();
    }

    public function massAssignAction() {
        $userId             = $this->getRequest()->getParam('user_id', 0);
        $customers          = $this->getRequest()->getParam('customer');
        $currentAdminId     = $this->_getCurrentAdmin()->getId();

        if(!empty($customers)) {
            foreach($customers as $cust) {
                try {
                    $assignment = Mage::getModel('xb2b/assignment');
                    $assignment->setData(array(
                        'user_id'       => $userId,
                        'customer_id'   => $cust,
                        'action_owner'  => $currentAdminId,
                        'assign_date'   => time()
                    ));
                    $assignment->save();
                } catch (Exception $ex) {

                }
            }
        }

        $this->_redirect('*/permissions_user/edit/', array('user_id' => $userId));
    }

    public function massUnassignAction() {
        $userId             = $this->getRequest()->getParam('user_id', 0);
        $customers          = $this->getRequest()->getParam('customer');
        $currentAdminId     = $this->_getCurrentAdmin()->getId();

        //Clear all customer linked to this user
        $assignmentModel = Mage::getModel('xb2b/assignment');
        $assignments     = $assignmentModel->getCollection()
                            ->addFilter('user_id', $userId)
                            ->addFieldToFilter('customer_id', array( 'in' => $customers ));

        foreach($assignments as $assign) {
            try {
                $assign->delete();
            } catch (Exception $ex) { }
        }

        $this->_redirect('*/permissions_user/edit/', array('user_id' => $userId));
    }
}