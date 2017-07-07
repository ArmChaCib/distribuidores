<?php
class SM_XB2b_Model_Chat {

    private $mainFile = 'main.dat';
    private $adUnreadFile = 'ad_unread.dat';
    private $cusUnreadFile = 'cus_unread.dat';
    private $separator  = '#%#!@6';

    protected function _createDirIfNotExist($dir) {
        $path = Mage::getBaseDir('media') . DS . 'xb2b' . DS . 'chat' . DS . $dir;
        if(!is_dir($path)){
            mkdir($path, 0775, true);
        }
        return $path;
    }

    public function _getDataDirByCustomer($customerId) {
        $path = Mage::getBaseDir('media') . DS . 'xb2b' . DS . 'chat';
        $path .= DS . "*_".$customerId."_*";
        $files = glob($path."*");
        return $files;
    }

    protected function _getDataDirByAdmin($adminId) {
        $path = Mage::getBaseDir('media') . DS . 'xb2b' . DS . 'chat' . DS . $adminId . '_';
        $files = glob($path."*");
        return $files;
    }

    protected function _getDataDirByAdminAndCustomer($adminId, $customerId) {
        $path   = Mage::getBaseDir('media') . DS . 'xb2b' . DS . 'chat' . DS . $adminId . '_' . $customerId . '_';
        $files  = glob($path."*");
        $dateArr   = array();

        foreach ($files as $file) {
            $date = str_replace($path, '', $file);
            $dateArr[] = $date;
        }

        return $dateArr;
    }

    public function getUnreadMessagesByAdmin($adminId) {
        $dirs = $this->_getDataDirByAdmin($adminId);
        $data = array();
        $chatDir = Mage::getBaseDir('media') . DS . 'xb2b' . DS . 'chat' . DS;
        foreach($dirs as $dir) {
            $filePath   = $dir. DS . $this->adUnreadFile;
            $customerId = explode('_', str_replace($chatDir, '', $dir));
            $customerId = $customerId[1];

            if(file_exists($filePath)) {
                $linecount = 0;
                $handle = fopen($filePath, "r");
                $lines = array();
                while(!feof($handle)){
                    $line = fgets($handle);
                    if(!empty($line)) {
                        $linecount++;
                        $lines[] = $line;
                    }
                }
                if($linecount > 0) {
                    if(isset($data[$customerId])) {
                        $data[$customerId]['count'] += $linecount;
                        $data[$customerId]['data']  = array_merge($data[$customerId]['data'], $lines);
                    } else {
                        $data[$customerId] = array('count' => $linecount, 'data' => $lines);
                    }
                }
                fclose($handle);
            }
        }

        return $data;
    }

    public function getUnreadMessagesByCustomer($customerId) {
        $dirs = $this->_getDataDirByCustomer($customerId);
        $data = array();
        $chatDir = Mage::getBaseDir('media') . DS . 'xb2b' . DS . 'chat' . DS;
        foreach($dirs as $dir) {
            $filePath   = $dir. DS . $this->cusUnreadFile;
            $adminId = explode('_', str_replace($chatDir, '', $dir));
            $adminId = $adminId[0];

            if(file_exists($filePath)) {
                $linecount = 0;
                $handle = fopen($filePath, "r");
                $lines = array();
                while(!feof($handle)){
                    $line = fgets($handle);
                    if(!empty($line)) {
                        $linecount++;
                        $lines[] = $line;
                    }
                }
                if($linecount > 0) {
                    if(isset($data[$adminId])) {
                        $data[$adminId]['count'] += $linecount;
                        $data[$adminId]['data']  = array_merge($data[$adminId]['data'], $lines);
                    } else {
                        $data[$adminId] = array('count' => $linecount, 'data' => $lines);
                    }
                }
                fclose($handle);
            }
        }

        return $data;
    }

    public function markReadMessagesByAdmin($adminId) {
        $dirs = $this->_getDataDirByAdmin($adminId);
        foreach($dirs as $dir) {
            $filePath   = $dir. DS . $this->adUnreadFile;
            unlink($filePath);
        }
    }

    public function markReadMessagesByCustomer($customerId) {
        $dirs = $this->_getDataDirByCustomer($customerId);
        foreach($dirs as $dir) {
            $filePath   = $dir. DS . $this->cusUnreadFile;
            unlink($filePath);
        }
    }

    public function getConversationByDay($adminId, $customerId, $day) {
        $dir    = $adminId . '_' . $customerId . '_' . $day . DS . $this->mainFile;
        $path   = Mage::getBaseDir('media') . DS . 'xb2b' . DS . 'chat' . DS . $dir;
        $content = '';

        if(file_exists($path)){
            $content = file_get_contents($path);
        }

        if(empty($content)) {
            $content = array();
        } else {
            $content = explode("\n", $content);
        }

        return $content;
    }

    public function getConversationDateRange($adminId, $customerId) {
        $date   = $this->_getDataDirByAdminAndCustomer($adminId, $customerId);
        $today  = date('m_d_Y');
        foreach ($date as $k => $v) {
            if($v == $today)
                unset($date[$k]);
        }

        return $date;
    }

    public function getArchiveByAdmin($adminId) {
        $dirs = $this->_getDataDirByAdmin($adminId);
        $customers = array();
        $ids = array();
        foreach($dirs as $dir) {
            $customerId = explode('_', $dir);
            $customerId = $customerId[1];
            $ids[] = $customerId;
        }

        $ids = implode("','", $ids);

        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = "SELECT
                    `at_firstname`.`value` AS `firstname`, `at_lastname`.`value` AS `lastname`, e.entity_id
                  FROM `customer_entity` AS `e`
                    LEFT JOIN `customer_entity_varchar` AS `at_firstname`
                        ON (`at_firstname`.`entity_id` = `e`.`entity_id`) AND (`at_firstname`.`attribute_id` = '5')
                    LEFT JOIN `customer_entity_varchar` AS `at_lastname`
                        ON (`at_lastname`.`entity_id` = `e`.`entity_id`) AND (`at_lastname`.`attribute_id` = '7')
                  WHERE (`e`.`entity_id` IN ('$ids'))";

        $results = $readConnection->fetchAll($query);

        if(count($results) > 0) {
            foreach($results as $rs) {
                $customers[$rs['entity_id']] = $rs['firstname'] . ' '. $rs['lastname'];
            }
        }
        return $customers;
    }

    public function createMessage($adminId, $customerId, $actorType, $actorId, 
        $actorName, $msg) {
        $dir    = $adminId . '_' . $customerId . '_' . date('m_d_Y');
        $path   = $this->_createDirIfNotExist($dir);
        $time   = time();
        $msg    = nl2br($msg);
        $line   = $actorType.$this->separator.$actorId.$this->separator.
                    $actorName.$this->separator.$time.$this->separator.$msg;
        file_put_contents($path . DS . $this->mainFile, $line . "\n", FILE_APPEND);
        if($actorType == 1) {
            file_put_contents($path . DS . $this->cusUnreadFile, $line . "\n", FILE_APPEND);
        } else if($actorType == 2) {
            file_put_contents($path . DS . $this->adUnreadFile, $line . "\n", FILE_APPEND);
        }
    }

    public function getAllMessage($adminId, $customerId) {
        $dir    = $adminId . '_' . $customerId . '_' . date('m_d_Y') . DS . 'main.dat';
        $path   = Mage::getBaseDir('media') . DS . 'xb2b' . DS . 'chat' . DS . $dir;
        $content = '';

        if(file_exists($path)){
            $content = file_get_contents($path);
        }
        return $content;
    }

    public function logUserActivate($userId) {
        $dir    = 'useractive';
        $path   = $this->_createDirIfNotExist($dir);
        $time   = time();
        file_put_contents($path . DS . $userId.'.active', $time);
    }

    public function logCustomerActivate($customerId) {
        $dir    = 'customeractive';
        $path   = $this->_createDirIfNotExist($dir);
        $time   = time();
        file_put_contents($path . DS . $customerId.'.active', $time);
    }

    public function getUserOnlineStatus($ids) {
        $dir    = 'useractive';
        $path   = Mage::getBaseDir('media') . DS . 'xb2b' . DS . 'chat' . DS . $dir;
        $status = array();

        $now    = time();
        foreach ($ids as $id) {
            try {
                $time = file_get_contents($path . DS . $id.'.active');
                if(empty($time)) $status[$id] = false;
                else $status[$id] = $now - $time < 5;
            } catch (Exception $ex) {
                $status[$id] = false;
            }
        }

        return $status;
    }

    public function getCustomerOnlineStatus($ids) {
        $dir    = 'customeractive';
        $path   = Mage::getBaseDir('media') . DS . 'xb2b' . DS . 'chat' . DS . $dir;
        $status = array();

        $now    = time();
        foreach ($ids as $id) {
            try {
                $time = file_get_contents($path . DS . $id.'.active');
                if(empty($time)) $status[$id] = false;
                else $status[$id] = $now - $time < 5;
            } catch (Exception $ex) {
                $status[$id] = false;
            }
        }

        return $status;
    }
}