<?php
class SM_XB2b_Model_Sales_Create extends Mage_Adminhtml_Model_Sales_Order_Create
{

    public function recollectCart(){
        if ($this->_needCollectCart === true) {
            $this->getCustomerCart()
                ->collectTotals()
                ->save();
        }
        $this->setRecollect(true);
        return $this;
    }

    /**
     * Update quantity of order quote items
     *
     * @param   array $data
     */
    public function updateQuoteItems($data)
    {
        if (is_array($data)) {
            try {
                foreach ($data as $itemId => $info) {
                    if (!empty($info['configured'])) {
                        $item = $this->getQuote()->updateItem($itemId, new Varien_Object($info));
                        $itemQty = (float)$item->getQty();
                    } else {
                        $item       = $this->getQuote()->getItemById($itemId);
                        $itemQty    = (float)$info['qty'];
                    }
                    if ($item) {
                        if ($item->getProduct()->getStockItem()) {
                            if (!$item->getProduct()->getStockItem()->getIsQtyDecimal()) {
                                $itemQty = (int)$itemQty;
                            } else {
                                $item->setIsQtyDecimal(1);
                            }
                        }

                        //$itemQty    = $itemQty > 0 ? $itemQty : 1;
                        if($itemQty > 0) {
                            if (isset($info['custom_price'])) {
                                $itemPrice  = $this->_parseCustomPrice($info['custom_price']);
                            } else {
                                $itemPrice = null;
                            }
                            // $noDiscount = !isset($info['use_discount']);
                            $noDiscount = false;

                            if (empty($info['action']) || !empty($info['configured'])) {
                                $item->setQty($itemQty);
                                $item->setCustomPrice($itemPrice);
                                $item->setOriginalCustomPrice($itemPrice);
                                $item->setNoDiscount($noDiscount);
                                $item->getProduct()->setIsSuperMode(true);
                                $item->getProduct()->unsSkipCheckRequiredOption();
                                $item->checkData();
                            } else {
                                $this->moveQuoteItem($item->getId(), $info['action'], $itemQty);
                            }
                        } else {
                            $this->getQuote()->removeItem($item->getId());
                        }
                    } else {
                        try {
                            $itemQty    = (float)$info['qty'];
                            $t = explode('-',$itemId);
                            $realItemId = $itemId;
                            if (isset($t[0])){
                                $realItemId = $t[0];
                            }

                            if ($itemQty > 0 && $realItemId > 0){
                                $this->addProduct($itemId, $info);
                            }
                            /**
                             * Fixed: Cant update custom price at first time click button Update Items
                            */
                            $this->recollectCart();
                            $this->updateCustomPrice($data);
                        }
                        catch (Mage_Core_Exception $e){
                            $this->getSession()->addError($e->getMessage());
                        }
                        catch (Exception $e){
                            return $e;
                        }
                    }
                }
            } catch (Mage_Core_Exception $e) {
                $this->recollectCart();
                throw $e;
            } catch (Exception $e) {
                Mage::logException($e);
            }
            $this->recollectCart();
        }
        return $this;
    }

    public function updateCustomPrice($data)
    {
        if (is_array($data)) {
            try {
                foreach ($data as $itemId => $info) {
                    if (!empty($info['configured'])) {
                        $item = $this->getQuote()->updateItem($itemId, new Varien_Object($info));
                        $itemQty = (float)$item->getQty();
                    } else {
                        $p = Mage::getModel('catalog/product')->load($itemId);
                        $item       = $this->getQuote()->getItemByProduct($p);

                        $itemQty    = (float)$info['qty'];
                    }
                    if ($item) {
                        if ($item->getProduct()->getStockItem()) {
                            if (!$item->getProduct()->getStockItem()->getIsQtyDecimal()) {
                                $itemQty = (int)$itemQty;
                            } else {
                                $item->setIsQtyDecimal(1);
                            }
                        }

                        //$itemQty    = $itemQty > 0 ? $itemQty : 1;
                        if($itemQty > 0) {
                            if (isset($info['custom_price'])) {
                                $itemPrice  = $this->_parseCustomPrice(floatval(preg_replace('/[^\d\.]/', '', $info['custom_price'])));
                            } else {
                                $itemPrice = null;
                            }
                            //HiepHM Fixed bug Can not add Coupon code 9/4/2013
                            // $noDiscount = !isset($info['use_discount']);
                            $noDiscount = false;

                            if (empty($info['action']) || !empty($info['configured'])) {
                                $item->setQty($itemQty);
                                $item->setCustomPrice($itemPrice);
                                $item->setOriginalCustomPrice($itemPrice);
                                $item->setNoDiscount($noDiscount);
                                $item->getProduct()->setIsSuperMode(true);
                                $item->getProduct()->unsSkipCheckRequiredOption();
                                $item->checkData();
                            } else {
                                $this->moveQuoteItem($item->getId(), $info['action'], $itemQty);
                            }
                        }
                    }
                }
            } catch (Mage_Core_Exception $e) {
                $this->recollectCart();
                throw $e;
            } catch (Exception $e) {
                Mage::logException($e);
            }
            $this->recollectCart();
        }
        return $this;
    }

    public function createOrder()
    {
        $this->_prepareCustomer();
        $this->_errors = array_unique($this->_errors);

        $quote = $this->getQuote();
        // hieunt : fixed get shiping
        $shippingAddress = $quote->getShippingAddress();
        $shippingMethod = Mage::registry('pos_shipping_method');

        if ($shippingMethod) {
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
                ->setShippingMethod($shippingMethod);
        }
        $this->_validate();

        $this->_prepareQuoteItems();

        if (! $quote->getCustomer()->getId() || ! $quote->getCustomer()->isInStore($this->getSession()->getStore())) {
            $account = $this->getData('account');
            if($account['type'] != 'guest'){
                $quote->getCustomer()->sendNewAccountEmail('registered', '', $quote->getStoreId());
            }
        }
        $service = Mage::getModel('xb2b/sales_quote', $quote);
        if ($this->getSession()->getOrder()->getId()) {
            $oldOrder = $this->getSession()->getOrder();
            $originalId = $oldOrder->getOriginalIncrementId();
            if (!$originalId) {
                $originalId = $oldOrder->getIncrementId();
            }
            $orderData = array(
                'original_increment_id'     => $originalId,
                'relation_parent_id'        => $oldOrder->getId(),
                'relation_parent_real_id'   => $oldOrder->getIncrementId(),
                'edit_increment'            => $oldOrder->getEditIncrement()+1,
                'increment_id'              => $originalId.'-'.($oldOrder->getEditIncrement()+1)
            );
            $quote->setReservedOrderId($orderData['increment_id']);
            $service->setOrderData($orderData);
        }

        $order = $service->submit();

        if (!$quote->getCustomer()->getId() || !$quote->getCustomer()->isInStore($this->getSession()->getStore())) {
            $quote->getCustomer()->setCreatedAt($order->getCreatedAt());

            $quote->getCustomer()->save();
        }
        if ($this->getSession()->getOrder()->getId()) {
            $oldOrder = $this->getSession()->getOrder();

            $this->getSession()->getOrder()->setRelationChildId($order->getId());
            $this->getSession()->getOrder()->setRelationChildRealId($order->getIncrementId());
            $this->getSession()->getOrder()->cancel()
                ->save();
            $order->save();
        }
        if ($this->getSendConfirmation()) {
            $order->sendNewOrderEmail();
        }

        Mage::dispatchEvent('checkout_submit_all_after', array('order' => $order, 'quote' => $quote));

        return $order;
    }

    public function getCustomerGroupId()
    {
        $orderXb2b = Mage::registry('xb2b_order');
        if (isset($orderXb2b))
            $groupId = $orderXb2b;
        else
            $groupId = $this->getQuote()->getCustomerGroupId();
        if (!$groupId) {
            $groupId = $this->getSession()->getCustomerGroupId();
        }
        return $groupId;
    }

    public function addProduct($product, $config = 1)
    {
        if (!is_array($config) && !($config instanceof Varien_Object)) {
            $config = array('qty' => $config);
        }
        $config = new Varien_Object($config);

        if (!($product instanceof Mage_Catalog_Model_Product)) {
            $productId = $product;
            $product = Mage::getModel('catalog/product')
                ->setStore($this->getSession()->getStore())
                ->setStoreId($this->getSession()->getStoreId())
                ->load($product);
            if (!$product->getId()) {
                Mage::throwException(
                    Mage::helper('adminhtml')->__('Failed to add a product to cart by id "%s".', $productId)
                );
            }
        }

        $stockItem = $product->getStockItem();
        if ($stockItem && $stockItem->getIsQtyDecimal()) {
            $product->setIsQtyDecimal(1);
        } else {
            $config->setQty((int) $config->getQty());
        }

        $product->setCartQty($config->getQty());
        $item = $this->getQuote()->addProductAdvanced(
            $product,
            $config,
            Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_FULL
        );
        if (is_string($item)) {
            $item = $this->getQuote()->addProductAdvanced(
                $product,
                $config,
                Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_LITE
            );
            if (is_string($item)) {
                Mage::throwException($item);
            }
        }
        $item->checkData();

        $this->setRecollect(true);
        return $this;
    }

}
?>
