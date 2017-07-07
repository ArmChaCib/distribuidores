<?php

require_once(BP . DS . 'app' . DS . 'code' . DS . 'core' . DS . 'Mage' . DS . 'Adminhtml' . DS . 'controllers' . DS . 'Sales' . DS . 'Order' . DS . 'CreateController.php');

class SM_XB2b_Adminhtml_Xb2bController extends Mage_Adminhtml_Sales_Order_CreateController
{

    protected $_storeid;
    protected $_validated = false;

    protected function _construct()
    {
        parent::_construct();
        $this->_storeid = Mage::getStoreConfig('xb2b/general/storeid');

        if(Mage::getStoreConfig('xb2b/general/enable_quotation') == 0){
            return $this->_redirect("adminhtml/system_config/edit/section/xb2b");
        }
    }

    protected function _validateSecretKey()
    {
        return true;
    }

    protected function _checkLicense()
    {
        if (!Mage::helper('smcore')->checkLicense("X-B2B", Mage::getStoreConfig('xb2b/general/key')) || !Mage::helper("xb2b")->isEnable()) {
            return $this->_redirect("adminhtml/system_config/edit/section/xb2b");
        } else {
            $this->_validated = true;
        }
    }

    protected function _processData($action = null)
    {
        if ($action != 'save') {
            $data = $this->getRequest()->getParams();
            if (isset($data['coupon']['code']) && !empty($data['coupon']['code'])) {
                $this->_getOrderCreateModel()->getShippingAddress()->setCouponCode(trim($data['coupon']['code']));
            }
            $quote = $this->_getOrderCreateModel()->getQuote();
            if ($data['customer_id'] != "false" && intval($data['customer_id'])) {
                $customer = Mage::getModel('customer/customer')->load($data['customer_id']);
                if ($quote->getCustomerGroupId() == null) {
                    $quote->setCustomerGroupId(0);
                }
                $quote->assignCustomer($customer);
            } else {
                $quote->setCustomerId(0);
                $quote->setCustomerTaxClassId(0);
                $quote->setCustomerGroupId(0);
                $quote->setCustomerEmail('');
                $quote->setCustomerFirstname('');
                $quote->setCustomerLastname('');
            }
            parent::_processActionData($action);
        }
    }

    protected function _getOrderCreateModel()
    {
        return Mage::getSingleton('xb2b/sales_create');
    }

    protected function _getCurrentAdmin() {
        return Mage::getSingleton('admin/session')->getUser();
    }

    protected function _initProduct($pid)
    {
        $product = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($pid);
        if ($product->getId()) {
            return $product;
        }
        return false;
    }

    public function indexAction()
    {
        Mage::getModel('xb2b/observer')->checkQuotationExpired();
        $this->_title("Quotation Management - XB2B");
        $this->_checkLicense();
        $this->loadLayout();
        $this->renderLayout();
    }

    public function newAction()
    {
        $this->_title("Add Quotation - XB2B");
        $this->_getSession()->setStoreId($this->_storeid);
        $quoteId = $this->getRequest()->getParam('id');
        if($quoteId){
            $quote_collection = Mage::getModel('xb2b/quotation')->load($quoteId);
            $quote_id = $quote_collection->getData('quote_id');
            /*$collection = Mage::getModel('sales/quote')
                ->getCollection()
                ->addFilter('entity_id',$quote_id);*/
            $collection = Mage::getModel('sales/quote')->getCollection();
            //$data_quote = $collection->getFirstItem();
            //$this->_getSession()->clear();
            $quote_data =Mage::getModel('sales/quote')->setStoreId($this->_storeid)->load($quote_id);
            $quote_items = $quote_data->getItemsCollection();

            Mage::register('quote_data', $quote_data);
            Mage::register('quote_items', $quote_items);

        }else{
            $this->_getSession()->setCustomerId(false);
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    public function editAction()
    {
        $quotationId = $this->getRequest()->getParam('id');
        if(!empty($quotationId)) {
            $quotation          = Mage::getModel('xb2b/quotation')->load($quotationId);
            $quoteId            = $quotation->getData('quote_id');
            $adminId            = $this->_getCurrentAdmin()->getId();

            $quote              = Mage::getModel('sales/quote')->setStoreId($this->_storeid)->load($quoteId);

            if($quotation->getQuotationStatus() != 2) {
                $tmpQuote = Mage::getModel('sales/quote');
                $quoteData = $quote->getData();
                unset($quoteData['entity_id']);
                $quoteItems = $quote->getAllItems();
                $shippingAddress = $quote->getShippingAddress();
                $billingAddress = $quote->getBillingAddress();

                $couponCode     = $quote->getCouponCode();

                $tmpQuote->setData($quoteData);
                foreach($quoteItems as $item) {
                    $itemData = $item->getData();
                    unset($itemData['quote_id']);
                    unset($itemData['item_id']);
                    $newItem = Mage::getModel('sales/quote_item');
                    $newItem->setData($itemData);
                    $tmpQuote->addItem($newItem);
                }
                $tmpQuote->setShippingAddress($shippingAddress);
                $tmpQuote->setBillingAddress($billingAddress);
                if(!empty($couponCode)) $tmpQuote->setCouponCode($couponCode);
                $tmpQuote->collectTotals();

                $tmpQuote->save();

                Mage::register('quote_edit_'.$adminId, $tmpQuote);
                Mage::register('quote_edit_org_'.$tmpQuote->getId().'_'.$adminId, $quote->getId());
            } else {
                Mage::register('quote_edit_'.$adminId, $quote);
            }

            Mage::register('quotation_edit_'.$adminId, $quotation);
        } else {
            $this->_getSession()->setCustomerId(false);
        }

        $this->_title("Edit Quotation - XB2B");
        $this->_getSession()->setStoreId($this->_storeid);
        $this->loadLayout();
        $this->renderLayout();
    }

    public function saveQuotationEditAction(){
        $quotationId    = $this->getRequest()->getParam('quotation_id');
        $comment        = $this->getRequest()->getParam('customer_note');
        $quoteId        = $this->getRequest()->getParam('quote_id');
        $jsonResult     = array('error' => 0);

        //Update org quote
        $orgQuoteId = $this->getRequest()->getParam('org_quote_id');
        $orgQuote   = Mage::getModel('sales/quote')->setStoreId($this->_storeid)->load($orgQuoteId);
        $newQuote   = Mage::getModel('sales/quote')->setStoreId($this->_storeid)->load($quoteId);

        $shippingAddress = $newQuote->getShippingAddress();
        $billingAddress = $newQuote->getBillingAddress();

        $couponCode     = $newQuote->getCouponCode();

        //Clear all org item
        $orgQuote->removeAllItems();
        $newItems   = $newQuote->getAllItems();
        foreach($newItems as $item) {
            $itemData = $item->getData();
            unset($itemData['quote_id']);
            unset($itemData['item_id']);
            $newItem = Mage::getModel('sales/quote_item');
            $newItem->setData($itemData);
            $orgQuote->addItem($newItem);
        }

        $orgQuote->setShippingAddress($shippingAddress);
        $orgQuote->setBillingAddress($billingAddress);
        if(!empty($couponCode)) $orgQuote->setCouponCode($couponCode);
        $orgQuote->collectTotals();
        $orgQuote->save();

        $quotation = Mage::getModel('xb2b/quotation')->load($quotationId);
        $quotation->setQuotationStatus(1);
        $quotation->setExpiredDate(strtotime($this->getRequest()->getParam('expired_date')));
        $quotation->setUpdateData(time());
        $quotation->save();

        //Save comment
        if(!empty($comment)) {
            $currAdmin = Mage::getSingleton('admin/session')->getUser();
            $quoteComment = Mage::getModel('xb2b/quotecomment');
            $quoteComment->setData(array(
                'owner_id'      => $currAdmin->getId(),
                'owner_type'    => 0,
                'quote_id'      => $quotation->getQuoteId(),
                'quotation_id'  => $quotation->getId(),
                'content'       => $comment,
                'date'          => time()
            ));
            $quoteComment->save();
        }

        $jsonResult['redirect'] = Mage::helper("adminhtml")->getUrl("*/xb2b");
        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($jsonResult);
    }

    public function saveQuoteAction(){
        $quoteId = $this->getRequest()->getParam('quote_id');
        $comment = $this->getRequest()->getParam('customer_note');

        $quotation                  = Mage::getModel('xb2b/quotation');
        $data['quote_id']           = $quoteId;
        $data['quotation_status']   = "1";
        $data['customer_id']        = $this->getRequest()->getParam('customer_id');
        $data['expired_date']       = strtotime($this->getRequest()->getParam('expired_date'));
        $data['create_date']        = time();
        $data['update_date']        = time();
        $quotation->setData($data);
        $quotation->save();

        //Save comment
        $currAdmin = Mage::getSingleton('admin/session')->getUser();
        $quoteComment = Mage::getModel('xb2b/quotecomment');
        $quoteComment->setData(array(
            'owner_id'      => $currAdmin->getId(),
            'owner_type'    => 0,
            'quote_id'      => $quoteId,
            'quotation_id'  => $quotation->getId(),
            'content'       => $comment,
            'date'          => time()
        ));
        $quoteComment->save();

        $this->_getSession()->clear();

        $jsonResult = array('error' => 0, 'redirect' => Mage::helper("adminhtml")->getUrl("*/xb2b"));
        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($jsonResult);
    }

    public function massDeleteAction() {
        $quotationIds = $this->getRequest()->getParam('quotation');
        if (!is_array($quotationIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select quotation(s)'));
        } else {
            try {
                foreach ($quotationIds as $quotationId) {
                    $cashier = Mage::getModel('xb2b/quotation')->load($quotationId);
                    $cashier->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count(row)+1
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }

    public function clearAction()
    {
        $this->_getSession()->clear();
        $this->_redirect('*/*/new');
    }

    public function customerSearchAction()
    {
        $items = array();

        $start = $this->getRequest()->getParam('start', 1);
        $limit = $this->getRequest()->getParam('limit', 10);
        $query = $this->getRequest()->getParam('query', '');

        $searchInstance = new SM_XB2b_Model_Adminhtml_Search_Customer;

        $results = $searchInstance->setStart($start)
            ->setLimit($limit)
            ->setQuery($query)
            ->load()
            ->getResults();

        $items = array_merge_recursive($items, $results);

        $block = $this->getLayout()->createBlock('adminhtml/template')
            ->setTemplate('sm/xb2b/system/autocomplete.phtml')
            ->assign('items', $items);
        $this->getResponse()->setBody($block->toHtml());
    }

    public function customerLoadAction(){
        $customer = array();
        $customer_id = $this->getRequest()->getParam('customer_id', 1);
        $searchInstance = new SM_XB2b_Model_Adminhtml_Search_Customer;
        $results = $searchInstance->loadById($customer_id);
        $customer = array_merge_recursive($customer, $results);
        $customer = $customer[0];
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($customer));
    }

    public function productSearchAction()
    {

        Mage::app()->setCurrentStore(1);
        $items = array();

        $start = $this->getRequest()->getParam('start', 1);
        $limit = $this->getRequest()->getParam('limit', 20);
        $query = $this->getRequest()->getParam('query', '');

        $query = str_replace(array("'", '"', '`', '’'),
                            array('&apos;', '&quot;', '&lsquo;', '&rsquo;'), $query);
        $searchInstance = new SM_XB2b_Model_Adminhtml_Search_Catalog;

        $results = $searchInstance->setStart($start)
                                    ->setLimit($limit)
                                    ->setQuery($query)
                                    ->load()
                                    ->getResults();

        $items = array_merge_recursive($items, $results);

        $block = $this->getLayout()->createBlock('adminhtml/template')
            ->setTemplate('sm/xb2b/system/autocomplete.phtml')
            ->assign('items', $items);

        Mage::app()->setCurrentStore(0);

        $this->getResponse()->setBody($block->toHtml());
    }

    public function ajaxAddProductToQuoteAction() {
        $quoteId    = $this->getRequest()->getParam('quote_id');
        $productId  = $this->getRequest()->getParam('product_id');

        $jsonResult = array('error' => 0, 'data' => array());

        if(empty($quoteId) || empty($productId)) {
            $jsonResult['error'] = 1;
        }

        Mage::app()->setCurrentStore(1);
        $coreHelper = Mage::helper('core');

        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $product = $this->_initProduct($productId);

        try {
            $backorder = Mage::getStoreConfig('cataloginventory/item_options/backorders');
            if($backorder) {
                $product->getStockItem()->setIsInStock(true)->save();
            }
            $quote->addProduct($product);
            $quote->collectTotals();
            $quote->save();

            $item = $quote->getItemByProduct($product);

            // Get row total
            $_incl  = Mage::helper('checkout')->getSubtotalInclTax($item);
            if(Mage::helper('weee')->typeOfDisplay($item, array(0, 1, 4), 'sales')):
                $rowTotalIncTax = $_incl + $item->getWeeeTaxAppliedRowAmount();
            else:
                $rowTotalIncTax = $_incl - $item->getWeeeTaxRowDisposition();
            endif;

            //Get item information
            $quoteItem = $quote->getItemByProduct($product);
            $jsonResult['data'] = array(
                'item_id'       => $quoteItem->getId(),
                'item_price'    => $quoteItem->getPrice(),
                'line_total'    => $coreHelper->currency($rowTotalIncTax, true, false),
                'tax_amount'    => $coreHelper->currency($quote->getShippingAddress()->getData('tax_amount')),
                'subtotal'      => $coreHelper->currency($quote->getSubtotal(), true, false),
                'grand_total'   => $coreHelper->currency($quote->getGrandTotal(), true, false),
                'tax'           => $quoteItem->getTaxPercent()
            );
        } catch (Exception $e) {
            $jsonResult['error'] = 1;
            $jsonResult['msg'] = $e->getMessage();
        }

        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($jsonResult);
    }

    public function ajaxUpdateItemQtyAction() {
        $quoteId    = $this->getRequest()->getParam('quote_id');
        $itemId     = $this->getRequest()->getParam('item_id');
        $qty        = $this->getRequest()->getParam('qty');

        $jsonResult = array('error' => 0, 'data' => array());

        if(empty($quoteId) || empty($itemId)) {
            $jsonResult['error'] = 1;
        }

        Mage::app()->setCurrentStore(1);
        $coreHelper = Mage::helper('core');

        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $item  = $quote->getItemById($itemId);
        $item->setQty($qty);
        $quote->collectTotals();
        $quote->save();

        // Get row total
        $_incl  = Mage::helper('checkout')->getSubtotalInclTax($item);
        if(Mage::helper('weee')->typeOfDisplay($item, array(0, 1, 4), 'sales')):
            $rowTotalIncTax = $_incl + $item->getWeeeTaxAppliedRowAmount();
        else:
            $rowTotalIncTax = $_incl - $item->getWeeeTaxRowDisposition();
        endif;

        $jsonResult['data'] = array(
            'line_total'    => $coreHelper->currency($rowTotalIncTax, true, false),
            'subtotal'   => $coreHelper->currency($quote->getSubtotal(), true, false),
            'grand_total'   => $coreHelper->currency($quote->getGrandTotal(), true, false),
            'tax_amount'    => $coreHelper->currency($quote->getShippingAddress()->getData('tax_amount'))
        );

        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($jsonResult);
    }

    public function ajaxDeleteItemAction() {
        $quoteId    = $this->getRequest()->getParam('quote_id');
        $itemId     = $this->getRequest()->getParam('item_id');

        $jsonResult = array('error' => 0, 'data' => array());

        if(empty($quoteId) || empty($itemId)) {
            $jsonResult['error'] = 1;
        }

        Mage::app()->setCurrentStore(1);
        $coreHelper = Mage::helper('core');

        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $item  = $quote->getItemById($itemId);
        $quote->deleteItem($item);
        $quote->collectTotals();
        $quote->save();

        $jsonResult['data'] = array(
            'subtotal'      => $coreHelper->currency($quote->getSubtotal(), true, false),
            'grand_total'   => $coreHelper->currency($quote->getGrandTotal(), true, false),
            'tax_amount'    => $coreHelper->currency($quote->getShippingAddress()->getData('tax_amount'))
        );

        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($jsonResult);
    }

    public function ajaxUpdatePriceAction() {
        $quoteId    = $this->getRequest()->getParam('quote_id');
        $itemId     = $this->getRequest()->getParam('item_id');
        $price      = $this->getRequest()->getParam('price');

        $jsonResult = array('error' => 0, 'data' => array());

        if(empty($quoteId) || empty($itemId) || empty($price)) {
            $jsonResult['error'] = 1;
        }

        Mage::app()->setCurrentStore(1);
        $coreHelper = Mage::helper('core');

        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $item  = $quote->getItemById($itemId);

        $item->setCustomPrice($price);
        $item->setOriginalCustomPrice($price);
        $item->getProduct()->setIsSuperMode(true);
        $item->save();

        $quote->collectTotals();
        $quote->save();

        // Get row total
        $_incl  = Mage::helper('checkout')->getSubtotalInclTax($item);
        if(Mage::helper('weee')->typeOfDisplay($item, array(0, 1, 4), 'sales')):
            $rowTotalIncTax = $_incl + $item->getWeeeTaxAppliedRowAmount();
        else:
            $rowTotalIncTax = $_incl - $item->getWeeeTaxRowDisposition();
        endif;

        $jsonResult['data'] = array(
            'line_total'    => $coreHelper->currency($rowTotalIncTax, true, false),
            'subtotal'   => $coreHelper->currency($quote->getSubtotal(), true, false),
            'grand_total'   => $coreHelper->currency($quote->getGrandTotal(), true, false),
            'tax_amount'    => $coreHelper->currency($quote->getShippingAddress()->getData('tax_amount'))
        );

        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($jsonResult);
    }

    protected function _getCustomerGroups(){
        $groupIds = array();
        $collection = Mage::getModel('customer/group')->getCollection();
        foreach ($collection as $customer) {
            $groupIds[] = $customer->getId();
        }

        return $groupIds;
    }

    protected function _createACoupon($name, $discount = 0){
        $couponCode = false;

        if ($discount > 0) {
            $model = Mage::getModel('salesrule/coupon');
            $couponCode = substr(md5($name . microtime()), 0, 8);
            $website_id = intval(Mage::getModel('core/store')
                                ->load(Mage::getStoreConfig('xb2b/general/storeid'))->getWebsiteId());
            // create coupon
            $rule = Mage::getModel('salesrule/rule')
                ->setName($name)
                ->setDescription('Coupon for XB2B Quote')
                ->setCustomerGroupIds($this->_getCustomerGroups())
                ->setFromDate(date('Y-m-d',time() - 172800))
                ->setUsesPerCoupon(12)
                ->setUsesPerCustomer(31)
                ->setIsActive(1)
                ->setSimpleAction(Mage_SalesRule_Model_Rule::CART_FIXED_ACTION)
                ->setDiscountAmount($discount)
                ->setStopRulesProcessing(0)
                ->setIsRss(0)
                ->setWebsiteIds($website_id)
                ->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
                ->save();

            $model->setRuleId($rule->getId())
                ->setCode($couponCode)
                ->setIsPrimary(1)
                ->save();
        }
//        if ($couponCode) {
//            $data['coupon']['code'] = $couponCode;
//            $this->_getOrderCreateModel()
//                ->importPostData($data);
//        }

        return $couponCode;
    }


    public function ajaxUpdateTotalDiscountAction() {
        $quoteId    = $this->getRequest()->getParam('quote_id');
//        $quotationId = $this->getRequest()->getParam('quotation_id');
        $discount   = $this->getRequest()->getParam('discount');
//        $byPercent = $this->getRequest()->getParam('by_percent');

        $jsonResult = array('error' => 0, 'data' => array());

        if(empty($quoteId) || empty($discount)) {
            $jsonResult['error'] = 1;
        }

        Mage::app()->setCurrentStore(1);
        $coreHelper = Mage::helper('core');

        $quote = Mage::getModel('sales/quote')->setStoreId($this->_storeid)->load($quoteId);

        //Check coupon
        $couponCode = $quote->getCouponCode();
        if(!empty($couponCode)) {
            $coupon = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');
            $couponRule = Mage::getModel('salesrule/rule')->load($coupon->getRuleId());
            $couponRule->setDiscountAmount($discount);
            $couponRule->save();
        } else {
            $couponCode = $this->_createACoupon('Discount for XB2B quote #' . $quoteId, $discount);
            $quote->setCouponCode($couponCode);
        }

        $quote->collectTotals();
        $quote->save();

        $jsonResult['data'] = array(
            'subtotal'      => $coreHelper->currency($quote->getSubtotal(), true, false),
            'grand_total'   => $coreHelper->currency($quote->getGrandTotal(), true, false),
            'tax_amount'    => $coreHelper->currency($quote->getShippingAddress()->getData('tax_amount'))
        );

        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($jsonResult);
    }

    public function ajaxUpdateTotalDiscountOnAddAction() {
        $quoteId    = $this->getRequest()->getParam('quote_id');
        $quotationId = $this->getRequest()->getParam('quotation_id');
        $discount   = $this->getRequest()->getParam('discount');

        $jsonResult = array('error' => 0, 'data' => array());

        if(empty($quoteId) || empty($discount)) {
            $jsonResult['error'] = 1;
        }

        Mage::app()->setCurrentStore(1);
        $coreHelper = Mage::helper('core');

        $quote = Mage::getModel('sales/quote')->setStoreId($this->_storeid)->load($quoteId);
        //Check coupon
        $couponCode = $quote->getCouponCode();
        if(!empty($couponCode)) {
            $coupon = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');
            $couponRule = Mage::getModel('salesrule/rule')->load($coupon->getRuleId());
            $couponRule->setDiscountAmount($discount);
            $couponRule->save();
        } else {
            $couponCode = $this->_createACoupon('Discount for XB2B quote #' . $quoteId, $discount);
            $quote->setCouponCode($couponCode);
        }
        $quote->collectTotals();
        $quote->save();

        $jsonResult['data'] = array(
            'subtotal'      => $coreHelper->currency($quote->getSubtotal(), true, false),
            'grand_total'   => $coreHelper->currency($quote->getGrandTotal(), true, false),
            'tax_amount'    => $coreHelper->currency($quote->getShippingAddress()->getData('tax_amount'))
        );

        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($jsonResult);
    }

    public function loadBlockAction()
    {
        $request = $this->getRequest();
        try {
            $this->_initSession()
                ->_processData();

        } catch (Mage_Core_Exception $e) {
            $this->_reloadQuote();
            $this->_getSession()->addError($e->getMessage());

        } catch (Exception $e) {
            $this->_reloadQuote();
            $this->_getSession()->addException($e, $e->getMessage());
        }

        $asJson = $request->getParam('json');
        $block = $request->getParam('block');

        if ($block == 'sales_creditmemo_create') {
            $creditmemo = $this->_initCreditmemo();
        }

        $update = $this->getLayout()->getUpdate();
        if ($asJson) {
            $update->addHandle('adminhtml_xb2b_load_block_json');
        } else {
            $update->addHandle('adminhtml_xb2b_load_block_plain');
        }

        if ($block) {
            $blocks = explode(',', $block);
            if ($asJson && !in_array('message', $blocks)) {
                $blocks[] = 'message';
            }

            foreach ($blocks as $block) {
                $update->addHandle('adminhtml_xb2b_load_block_' . $block);
            }
        }
        $this->loadLayoutUpdates()->generateLayoutXml()->generateLayoutBlocks();
        $result = $this->getLayout()->getBlock('content')->toHtml();

        if ($request->getParam('as_js_varname')) {
            Mage::getSingleton('adminhtml/session')->setUpdateResult($result);
            $this->_redirect('*/*/showUpdateResult');
        } else {
            $this->getResponse()->setBody($result);
        }
    }

}
