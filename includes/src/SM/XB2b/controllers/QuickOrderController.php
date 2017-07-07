<?php
class SM_XB2b_QuickOrderController extends Mage_Core_Controller_Front_Action {

    private $_xb2b = null;

    protected function _grandAccess() {
        $this->_xb2b = Mage::helper('xb2b')->loadXB2BConfig();
        if(!$this->_xb2b || !$this->_xb2b['root_enable_b2b'] || !$this->_xb2b['customer_group_enabled_b2b']) {
            $redirect_url = Mage::getUrl('customer/account/login/');
            Mage::app()->getFrontController()->getResponse()->setRedirect($redirect_url);
            return false;
        }
        return true;
    }

    public function indexAction() {
        if(!$this->_grandAccess()) {
            return false;
        }
        $this->loadLayout();
        $this->renderLayout();
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

    private function _getCustomer() {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    private function _getCustomerAddress() {
        return Mage::getModel('customer/address');
    }

    private function _getCustomerDefaultShippingAddress() {
        $customerAddressId  = $this->_getCustomer()->getDefaultShipping();
        if ($customerAddressId){
            $address        = $this->_getCustomerAddress()->load($customerAddressId);
            return $address;
        }
        return false;
    }

    private function _getCustomerDefaultBillingAddress() {
        $customerAddressId  = $this->_getCustomer()->getDefaultBilling();
        if ($customerAddressId){
            $address        = $this->_getCustomerAddress()->load($customerAddressId);
            return $address;
        }
        return false;
    }

    private function _getQuoteBillingAddress() {
        $quote          = $this->_getQuote();
        $billingAddress = $quote->getBillingAddress();
        return $billingAddress;
    }

    private function _getQuoteShippingAddress() {
        $quote          = $this->_getQuote();
        $shippingAddress = $quote->getShippingAddress();
        return $shippingAddress;
    }

    protected function _emptyShoppingCart()
    {
        try {
            $this->_getCart()->truncate()->save();
            $this->_getSession()->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $exception) {
            $this->_getSession()->addError($exception->getMessage());
        } catch (Exception $exception) {
            $this->_getSession()->addException($exception, $this->__('Cannot update shopping cart.'));
        }
    }

    protected function _updateShoppingCart()
    {
        try {
            $cartData = $this->getRequest()->getParam('cart');
//            echo "<pre>"; print_r($cartData); echo "</pre>";exit();
            if (is_array($cartData)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                    }
                }
                $cart = $this->_getCart();
                if (! $cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }

                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)
                    ->save();
            }
            $this->_getSession()->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError(Mage::helper('core')->escapeHtml($e->getMessage()));
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot update shopping cart.'));
            Mage::logException($e);
        }
    }

    protected function _goBack()
    {
        $returnUrl = $this->getRequest()->getParam('return_url');
        if ($returnUrl) {

            if (!$this->_isUrlInternal($returnUrl)) {
                throw new Mage_Exception('External urls redirect to "' . $returnUrl . '" denied!');
            }

            $this->_getSession()->getMessages(true);
            $this->getResponse()->setRedirect($returnUrl);
        } elseif (!Mage::getStoreConfig('checkout/cart/redirect_to_cart')
            && !$this->getRequest()->getParam('in_cart')
            && $backUrl = $this->_getRefererUrl()
        ) {
            $this->getResponse()->setRedirect($backUrl);
        } else {
            if (($this->getRequest()->getActionName() == 'add') && !$this->getRequest()->getParam('in_cart')) {
                $this->_getSession()->setContinueShoppingUrl($this->_getRefererUrl());
            }
            $this->_redirect('checkout/cart');
        }
        return $this;
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

    public function selectProductAction() {
        $json_data  = array(
            'error' => 0
        );

        $cart   = $this->_getCart();
        $params = $this->getRequest()->getParams();
        $search_by  = $params['by'];
        $search_val = $params['s'];

        if(empty($search_by) || empty($search_val)) {
            $json_data['error'] = 1;
        } else {
            if($search_by == 'id') {
                $product = Mage::getModel('xb2b/product')->load($search_val);
            } else if($search_by == 'sku') {
                $product = Mage::getModel('xb2b/product')->loadByAttribute('sku', $search_val);
            }

            if($product->isSaleable()) {
                $product    = $this->_initProduct($product->getId());
                $quote      = $this->_getQuote();

                $quoteItem = $quote->getItemByProduct($product);
                if($quoteItem != false && (($quoteItem->getQty() + 1) > $product->getStockQuantity())) {
                    $json_data['error'] = 3;
                    $json_data['msg']   = "The requested quantity is not available";
                } else {
                    $prod_meta = array(
                        'uenc'      => $params['uenc'],
                        'qty'       => 1,
                        'form_key'  => $params['form_key'],
                        'product'   => (int)$product->getId()
                    );

                    $cart->addProduct($product, $prod_meta);

                    if(!$quoteItem) $quoteItem = $quote->getItemByProduct($product);

                    $cart->save();
                    $this->_getSession()->setCartWasUpdated(true);
                    $json_data['product'] = array(
                        'name'      => $product->getName(),
                        'id'        => $product->getId(),
                        'price'     => Mage::helper('core')->currency($quoteItem->getPrice(), true, false),
                        't_price'     => Mage::helper('core')->currency(($quoteItem->getQty() * $quoteItem->getPrice()), true, false),
                        'sku'       => $product->getSku(),
                        'small'     => $product->getSmallImageUrl(),
                        'normal'    => $product->getImageUrl(),
                        'high'      => $product->getThumbnailUrl(),
                        'url'       => $product->getProductUrl(),
                        'stock_qty' => $product->getStockQuantity()
                    );

                    $return_item = $quote->getItemByProduct($product);
                    $json_data['quote'] = array(
                        'subtotal'      => Mage::helper('core')->
                                currency($quote->getSubtotal(), true, false),
                        'grand_total'   => Mage::helper('core')->
                                currency($quote->getGrandTotal(), true, false)
                    );
                    $json_data['product']['quote_item_id'] = $return_item->getId();
                }
            } else {
                $json_data['error'] = 2;
            }
        }

        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($json_data);
    }

    public function importCSVAction(){
        $params = $this->getRequest()->getParams();
        $json_data  = array( 'error' => 0, 'msg'   => array(), 'products' => array() );

        if (isset($_FILES['input_csv']['name']) && $_FILES['input_csv']['name'] != '') {
            try{
                //Save file from server
                $fileName       = $_FILES['input_csv']['name'];
                $fileExt        = strtolower(substr(strrchr($fileName, ".") ,1));
                $fileNamewoe    = rtrim($fileName, $fileExt);
                $fileName       = preg_replace('/\s+', '', $fileNamewoe) . time() . '.' . $fileExt;

                $uploader       = new Varien_File_Uploader('input_csv');
                $uploader->setAllowedExtensions(array('csv','txt'));
                $uploader->setAllowRenameFiles(false);
                $uploader->setFilesDispersion(false);

                $path = Mage::getBaseDir('media') . DS . 'xb2b';
                if(!is_dir($path)){
                    mkdir($path, 0777, true);
                }
                $uploader->save($path . DS, $fileName);

                //Get data from file the uploaded
                $absolute_path  = $path . DS . $fileName;
                $csv            = new Varien_File_Csv();
                $data           = $csv->getData($absolute_path);

                //Begin import data
                $productModel   = Mage::getModel('xb2b/product');
                $cart           = $this->_getCart();
                $quote          = $this->_getQuote();
                //Check product availability for current website
                $currentWebsiteId = Mage::app()->getStore()->getWebsiteId();
                $cartNeedTobeUpdate = false;

                for($i=1; $i < count($data); $i++) {
                    $pSku   = trim($data[$i][0]);
                    $pQty   = intval($data[$i][1]);

                    $hasError = false;

                    if(empty($pSku) || $pQty == null || $pQty == '') {
                        $json_data['error'] = 1;
                        $json_data['msg'][]   = "Can not import product at line: ".($i+1);
                        $hasError = true;
                        continue;
                    }

                    $product = $productModel->loadByAttribute('sku', $pSku);

                    if(!$product || !$product->isSaleable() ||
                        $product->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_SIMPLE ||
                        !in_array($currentWebsiteId, $product->getWebsiteIds())) {
                        $json_data['error'] = 1;
                        $json_data['msg'][]   = "Can not import product with SKU: $pSku";
                        $hasError = true;
                        continue;
//                        break;
                    }

                    if(!is_integer($pQty) || $pQty <= 0 || intval($pQty) > $product->getStockQuantity()) {
                        $json_data['error'] = 1;
                        $json_data['msg'][]   = "Quantity is not available for product with SKU <b>$pSku</b>. Quantity must be numeric and greater than 0";
                        $hasError = true;
                        continue;
//                        break;
                    }

                    $quoteItem = $quote->getItemByProduct($product);
                    if($quoteItem) {
                        if(($quoteItem->getQty() + $pQty) > $product->getStockQuantity()) {
                            $json_data['error'] = 1;
                            $json_data['msg'][]   = "The requested quantity is not available for product with SKU <b>$pSku</b>";
                            $hasError = true;
                            continue;
//                            break;
                        }
                    }

                    if(!$hasError) {
                        $product = $this->_initProduct($product->getId());
                        $cartNeedTobeUpdate = true;

                        $prod_meta = array(
                            'uenc'      => $params['uenc'],
                            'qty'       => $pQty,
                            'form_key'  => $params['form_key'],
                            'product'   => intval($product->getId())
                        );

                        $cart->addProduct($product, $prod_meta);

                        $qtyUpTo = $pQty;

                        if($quoteItem != false) {
                            $qtyUpTo = $quoteItem->getQty();
                        }

                        $json_data['products'][] = array(
                            'name'      => $product->getName(),
                            'id'        => $product->getId(),
                            'sku'       => $product->getSku(),
                            'small'     => $product->getSmallImageUrl(),
                            'normal'    => $product->getImageUrl(),
                            'high'      => $product->getThumbnailUrl(),
                            'url'       => $product->getProductUrl(),
                            'stock_qty' => $product->getStockQuantity(),
                            'ord_qty'   => $pQty,
                            'qty_up_to' => $qtyUpTo,
                            'p_obj'     => $product
                        );
                    }
                }

                if($cartNeedTobeUpdate) {
                    $cart->save();
                    $this->_getSession()->setCartWasUpdated(true);
                }

                //Get quote data
                $coreHelper = Mage::helper('core');
                $json_data['quote'] = array(
                    'subtotal'      => $coreHelper->currency($quote->getSubtotal(), true, false),
                    'grand_total'   => $coreHelper->currency($quote->getGrandTotal(), true, false)
                );

                foreach($json_data['products'] as $pk => $prod) {
                    $product = $prod['p_obj'];
                    $quoteItem = $quote->getItemByProduct($product);
                    $prod['quote_item_id'] = $quoteItem->getId();
                    $prod['price'] = Mage::helper('core')
                        ->currency($quoteItem->getPrice(), true, false);
                    $prod['t_price'] = Mage::helper('core')
                        ->currency(($quoteItem->getPrice() *
                            $prod['qty_up_to']), true, false);
                    unset($prod['p_obj']);
                    $json_data['products'][$pk] = $prod;
                }
            }catch (Exception $e){
                $json_data['error'] = 1;
                $json_data['msg'][] = $e->getMessage();
            }

            header('Content-Type: application/json');
            echo Mage::helper('core')->jsonEncode($json_data);
        }
    }

    public function updateQuantityAction() {
        //Prepare and check request
        $params = $this->getRequest()->getParams();
        $json_data  = array( 'error' => 0 );
        if (!$this->_validateFormKey()) {
            $json_data['error'] = 1;
        } else {
            $this->_updateShoppingCart();
            $quote = $this->_getQuote();
            $json_data['quote'] = array(
                'subtotal'      => Mage::helper('core')->
                        currency($quote->getSubtotal(), true, false),
                'grand_total'   => Mage::helper('core')->
                        currency($quote->getGrandTotal(), true, false),
            );

            $params     = array_values($params['cart']);
            $product    = $this->_initProduct($params[0]['pid']);
            $quote      = $this->_getQuote();

            $quoteItem  = $quote->getItemByProduct($product);
            $totalPrice = $quoteItem->getPrice() * intval($params[0]['qty']);

            $json_data['product'] = array(
                'pid'     => $product->getId(),
                't_price' => Mage::helper('core')->currency($totalPrice, true, false)
            );
        }

        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($json_data);
    }

    public function searchProductAction() {
        $jsonResult = array('error' => 0);
        if (!$this->_validateFormKey()) {
            $jsonResult['error'] = 1;
        } else {
            $value      = $this->getRequest()->getParam('s');
            $by         = $this->getRequest()->getParam('b');

            if(empty($by)) $by = 'entity_id';
            else if($by == 'id') $by = 'entity_id';

            if(!empty($value) && !empty($by)) {
                $productModel = Mage::getModel('xb2b/product');
                $collection = $productModel->searchProduct($by, $value);
                $jsonResult['products'] = $collection;

                header('Content-Type: application/json');
                echo Mage::helper('core')->jsonEncode($jsonResult);

            } else {
                $jsonResult['error'] = 1;
            }
        }
    }

    public function updateManyItemAction() {
        $params     = $this->getRequest()->getParams();
        $json_data  = array( 'error' => 0 );

        if (!$this->_validateFormKey()) {
            $json_data['error'] = 1;
        } else {
            $this->_updateShoppingCart();

            $quote = $this->_getQuote();
            $json_data['quote'] = array(
                'subtotal'      => Mage::helper('core')->currency($quote->getSubtotal(), true, false),
                'grand_total'   => Mage::helper('core')->currency($quote->getGrandTotal(), true, false)
            );

            $tmpProds   = array();
            $params     = array_values($params['cart']);
            $quote      = $this->_getQuote();

            foreach($params as $prod) {
                $product    = $this->_initProduct($prod['pid']);
                $quoteItem  = $quote->getItemByProduct($product);
                $totalPrice = $quoteItem->getPrice() * intval($prod['qty']);
                $tmpProds[] = array(
                    'qid'       => $quoteItem->getId(),
                    't_price'   => Mage::helper('core')->currency($totalPrice, true, false)
                );
            }
            $json_data['products'] = $tmpProds;
        }

        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($json_data);
    }

    public function deleteOneAction() {
        if (!$this->_validateFormKey()) {
            $json_data['error'] = 1;
        } else {
            $itemId = $this->getRequest()->getParam('item_id');
            $cart = $this->_getCart();
            $cart->removeItem($itemId);
            $cart->save();
            $this->_getSession()->setCartWasUpdated(true);

            $quote = $this->_getQuote();
            $json_data['quote'] = array(
                'subtotal'      => Mage::helper('core')->currency($quote->getSubtotal(), true, false),
                'grand_total'   => Mage::helper('core')->currency($quote->getGrandTotal(), true, false)
            );
        }
        header('Content-Type: application/json');
        echo Mage::helper('core')->jsonEncode($json_data);
    }

    public function deleteAllAction() {
        if (!$this->_validateFormKey()) {
            $json_data['error'] = 1;
        } else {
            $cart = $this->_getCart();
            $items = $cart->getItems();
            foreach ($items as $item)
            {
                $itemId = $item->getItemId();
                $cart->removeItem($itemId);
            }
            $cart->save();
            $this->_getSession()->setCartWasUpdated(true);
        }
    }
}