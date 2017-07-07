<?php

class SM_XB2b_Block_Adminhtml_Customer_Chat extends Mage_Adminhtml_Block_Template
{
    private $chatModel;

    public function __construct() {
        $this->chatModel = Mage::getModel('xb2b/chat');
    }

    public function getCurrentAdmin() {
        return Mage::getSingleton('admin/session')->getUser();
    }

    public function getCustomers() {
        $adminId = $this->getCurrentAdmin()->getId();
        $customers = array();

        $groupsAssigned = Mage::getModel('xb2b/groupassignment')
                                ->getCollection()
                                ->addFilter('user_id', $adminId);

        $groupArr = array();
        foreach ($groupsAssigned as $group) { $groupArr[] = $group->getGroupId(); }

        $specialAssigned = Mage::getModel('xb2b/assignment')
                            ->getCollection()
                            ->addFilter('user_id', $adminId);

        $specialArr = array();
        foreach ($specialAssigned as $assign) { $specialArr[] = $assign->getCustomerId(); }

        $customersFromGroup = Mage::getModel('customer/customer')
                                    ->getCollection()
                                    ->addAttributeToSelect('firstname')
                                    ->addAttributeToSelect('lastname')
                                    ->addAttributeToFilter(array(
                                        array(
                                            'attribute' => 'group_id',
                                            'in'        => $groupArr
                                        ),
                                        array(
                                            'attribute' => 'entity_id',
                                            'in'        => $specialArr
                                        )
                                    ))
                                    ->addAttributeToFilter('xb2b_feature',1)
                                    ->addAttributeToSort('firstname', 'asc');

        return $customersFromGroup;
    }


}