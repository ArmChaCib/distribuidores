<?php

class SM_Xb2b_Adminhtml_XgroupassignController extends Mage_Adminhtml_Controller_Action {

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
        $groups             = $this->getRequest()->getParam('group');
        $currentAdminId     = $this->_getCurrentAdmin()->getId();

        if(!empty($groups)) {
            foreach($groups as $group_id) {
                try {
                    $assignment = Mage::getModel('xb2b/groupassignment');
                    $assignment->setData(array(
                        'user_id'       => $userId,
                        'group_id'      => $group_id,
                        'action_owner'  => $currentAdminId,
                        'assign_date'   => time()
                    ));

                    $assignment->save();

                    $collection_customer = Mage::getModel('customer/customer')
                        ->getCollection()
                        ->addAttributeToSelect('*')
                        ->addFieldToFilter('group_id', $group_id);

                    foreach($collection_customer as $item){
                        $customer_id = $item->getData('entity_id');

                        $assignment_customer = Mage::getModel('xb2b/assignment');
                        $assignment_customer->setData(array(
                            'user_id'       => $userId,
                            'customer_id'   => $customer_id,
                            'action_owner'  => $currentAdminId,
                            'assign_date'   => time()
                        ));
                        $assignment_customer->save();

                    }

                } catch (Exception $ex) {

                }
            }
        }

        $this->_redirect('*/permissions_user/edit/', array('user_id' => $userId));
    }

    public function massUnassignAction() {
        $userId             = $this->getRequest()->getParam('user_id', 0);
        $groups             = $this->getRequest()->getParam('group');

        //Clear all customer linked to this user
        $assignmentModel = Mage::getModel('xb2b/groupassignment');
        $assignments     = $assignmentModel->getCollection()
                            ->addFilter('user_id', $userId)
                            ->addFieldToFilter('group_id', array( 'in' => $groups ));

        foreach($assignments as $assign) {
            try {
                $assign->delete();

                if(!empty($groups)) {
                    foreach($groups as $group_id) {
                        $assignment_customer = Mage::getModel('xb2b/assignment')
                            ->getCollection()
                            ->addFieldToFilter('user_id', $userId);

                        $assignment_customer->getSelect()->join(Mage::getConfig()->getTablePrefix().'customer_entity', 'main_table.customer_id ='.Mage::getConfig()->getTablePrefix().'customer_entity.entity_id',array('group_id'))
                            ->where("group_id = " . $group_id);

                        foreach($assignment_customer as $item){
                            $item->delete();
                        }

                    }
                }


            } catch (Exception $ex) {}
        }

        $this->_redirect('*/permissions_user/edit/', array('user_id' => $userId));
    }
}