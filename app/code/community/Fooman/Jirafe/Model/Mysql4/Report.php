<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @package     Fooman_Jirafe
 * @copyright   Copyright (c) 2010 Jirafe Inc (http://www.jirafe.com)
 * @copyright   Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Fooman_Jirafe_Model_Mysql4_Report extends Mage_Core_Model_Mysql4_Abstract
{

    protected function _construct()
    {
        $this->_init('foomanjirafe/report', 'report_id');
    }

    public function getStoreRevenue ($storeId, $from, $to)
    {
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable('sales/order'), 'SUM(base_grand_total)')
            ->where('store_id = ?', $storeId)
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to);
        $res = $this->_getReadAdapter()->fetchOne($select);
        return $res ? $res : 0;
    }

    public function getStoreOrders ($storeId, $from, $to)
    {
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable('sales/order'), 'COUNT(quote_id)')
            ->where('store_id = ?', $storeId)
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to);
        $res =  $this->_getReadAdapter()->fetchOne($select);
        return $res ? $res : 0;
    }

    public function getStoreUniqueCustomers ($storeId, $from, $to)
    {
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable('sales/order'), 'customer_email')
             ->distinct()
            ->where('store_id = ?', $storeId)
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to);
        $res =  $this->_getReadAdapter()->fetchAll($select);
        return count($res) ? count($res) : 0;
    }

    public function getStoreVisitors ($storeId, $from, $to)
    {
        $numVisitors = 0;
        $ignoreAgents = Mage::getConfig()->getNode('global/ignore_user_agents')->asArray();

        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable('log/visitor'), array($this->getTable('log/visitor_info') . '.remote_addr', $this->getTable('log/visitor_info') . '.http_user_agent'))
                ->distinct()
                ->joinLeft(
                        $this->getTable('log/visitor_info'),
                        $this->getTable('log/visitor_info') . '.visitor_id = ' . $this->getTable('log/visitor') . '.visitor_id',
                        array())
                ->where('store_id = ?', $storeId)
                ->where('first_visit_at >= ?', $from)
                ->where('first_visit_at <= ?', $to)
                ->where($this->getTable('log/visitor_info') . '.http_user_agent NOT IN (?)', $ignoreAgents);
        $res = $this->_getReadAdapter()->fetchAll($select);
        foreach ($res as $result) {
            if (!preg_match("/" . Fooman_Jirafe_Model_Log_Visitor::USER_AGENT_BOT_PATTERN . "/i", $result['http_user_agent'])) {
                $numVisitors++;
            }
        }
        return $numVisitors;
    }

    public function getMaxMinOrders ($storeId, $from, $to)
    {
        $select = $this->_getReadAdapter()->select();
        $res = array('max_order'=>0, 'min_order'=>0);
        $select->from($this->getTable('sales/order'), 'MIN(base_grand_total)')
            ->where('store_id = ?', $storeId)
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to);
        $res['min_order'] +=  $this->_getReadAdapter()->fetchOne($select);
        $select->reset();
        $select->from($this->getTable('sales/order'), 'MAX(base_grand_total)')
            ->where('store_id = ?', $storeId)
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to);
        $res['max_order'] +=  $this->_getReadAdapter()->fetchOne($select);
        return $res;
    }

    public function getStoreAbandonedCarts ($storeId, $from, $to)
    {
        $res = array('num'=>0, 'revenue'=>0);
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable('sales/order'), 'quote_id')
            ->where('store_id = ?', $storeId)
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to);
        $convertedQuoteIds =  $this->_getReadAdapter()->fetchOne($select);

        $select->reset();
        $select->from($this->getTable('sales/quote'), 'COUNT(entity_id)')
            ->where('store_id = ?', $storeId)
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to)
            ->where('is_active = 1')
            ->where('entity_id NOT IN (?)',$convertedQuoteIds);
        $res['num'] +=  $this->_getReadAdapter()->fetchOne($select);

        $select->reset();
        $select->from($this->getTable('sales/quote'), 'SUM(base_grand_total)')
            ->where('store_id = ?', $storeId)
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to)
            ->where('is_active = 1')
            ->where('entity_id NOT IN (?)',$convertedQuoteIds);
        $res['revenue'] +=  $this->_getReadAdapter()->fetchOne($select);

        return $res;
    }
    public function checkIfReportExists ($storeId, $day)
    {
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable('foomanjirafe/report'), 'COUNT(report_id)')
            ->where('store_id = ?', $storeId)
            ->where('store_report_date = ?', $day);
        $res =  $this->_getReadAdapter()->fetchOne($select);
        return $res ? $res : 0;
    }


}