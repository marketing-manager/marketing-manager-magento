<?php

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
