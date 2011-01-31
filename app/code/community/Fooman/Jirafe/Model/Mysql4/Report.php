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

	public function getStoreRevenues($storeId, $from, $to)
	{
		$sales = array('sales_grand_total'=>0, 'sales_taxes'=>0, 'sales_shipping'=>0, 'sales_subtotal'=>0, 'sales_discounts'=>0);
		
		try {
			$select = $this->_getReadAdapter()->select()
				->from($this->getTable('sales/order'), array())
				->columns('SUM(base_grand_total) AS sales_grand_total')
				->columns('SUM(base_tax_amount) AS sales_taxes')
				->columns('SUM(base_shipping_amount) AS sales_shipping')
				->columns('SUM(base_subtotal) AS sales_subtotal')
				->columns('SUM(base_discount_amount) AS sales_discounts')
				->where('created_at >= ?', $from)
				->where('created_at < ?', $to)
				->where('store_id = ?', $storeId);

			$res = $this->_getReadAdapter()->fetchRow($select);
			
			foreach (array('sales_grand_total', 'sales_taxes', 'sales_shipping', 'sales_subtotal', 'sales_discounts') as $value) {
				if (!empty($res[$value])) {
					$sales[$value] = $res[$value];
				}
			}
		} catch (Exception $e) {
            // Unable to retrieve - just return zeros
			Mage::logException($e);
		}
		return $sales;
	}

	public function getStoreRefunds($storeId, $from, $to)
	{
		$refunds = array('refunds_grand_total'=>0, 'refunds_taxes'=>0, 'refunds_shipping'=>0, 'refunds_subtotal'=>0, 'refunds_discounts'=>0);
		
		try {
			$select = $this->_getReadAdapter()->select()
				->from($this->getTable('sales/creditmemo'), array())
				->columns('SUM(base_grand_total) AS refunds_grand_total')
				->columns('SUM(base_tax_amount) AS refunds_taxes')
				->columns('SUM(base_shipping_amount) AS refunds_shipping')
				->columns('SUM(base_subtotal) AS refunds_subtotal')
				->columns('SUM(base_discount_amount) AS refunds_discounts')
				->where('created_at >= ?', $from)
				->where('created_at < ?', $to)
				->where('store_id = ?', $storeId);
				
			$res = $this->_getReadAdapter()->fetchRow($select);
			
			foreach (array('refunds_grand_total', 'refunds_taxes', 'refunds_shipping', 'refunds_subtotal', 'refunds_discounts') as $value) {
				if (!empty($res[$value])) {
					$refunds[$value] = $res[$value];
				}
			}
		} catch (Exception $e) {
            // Unable to retrieve - just return zeros
			Mage::logException($e);
		}
		return $refunds;
	}

    public function getStoreOrders($storeId, $from, $to)
    {
		$orders = array('order_num'=>0, 'order_min'=>0, 'order_max'=>0);
		
		try {
            $select = $this->_getReadAdapter()->select()
				->from($this->getTable('sales/order'), array())
				->columns('COUNT(entity_id) AS order_num')
				->columns('MIN(base_grand_total) AS order_min')
				->columns('MAX(base_grand_total) AS order_max')
				->where('created_at >= ?', $from)
				->where('created_at < ?', $to)
				->where('store_id = ?', $storeId);
				
            $res = $this->_getReadAdapter()->fetchRow($select);

			foreach (array('order_num', 'order_min', 'order_max') as $value) {
				if (!empty($res[$value])) {
					$orders[$value] = $res[$value];
				}
			}
        } catch (Exception $e) {
            // Unable to retrieve - just return zeros
			Mage::logException($e);
        }
        return $orders;
    }

	public function getStoreUniqueCustomers ($storeId, $from, $to)
	{
		try {
			$res = array();
			// We cannot call COUNT(DISTINCT(customer_email)) because v1.4.0 and below have customer_email in another table
			$collection = Mage::getModel('sales/order')->getCollection()
				->addAttributeToSelect('customer_email')
				->addAttributeToFilter('store_id', $storeId)
				->addAttributeToFilter('created_at', array('from' => $from, 'to' => $to));
			foreach ($collection as $order) {
				$res[$order->getCustomerEmail()] = true;
			}
			$res = count($res) ? count($res) : 0; // If null, set to 0
		} catch (Exception $e) {
			$res = null; // Unable to retrieve - leave as null
		}
		return $res;
	}

	// first - is this the first email sent?  Therefore, we need to filter the first day of visitors from non-filtered results.
	// after this, they will automatically be filtered
    public function getStoreVisitors($storeId, $from, $to, $first=false)
    {
        $numVisitors = 0;
		
		try {
            $select = $this->_getReadAdapter()->select()
				->from(array('v'=>$this->getTable('log/visitor')), array())
				->joinLeft(array('vi'=>$this->getTable('log/visitor_info')), 'v.visitor_id=vi.visitor_id', array())
				->columns('vi.remote_addr')
				->columns('vi.http_user_agent')
				->distinct()
				->where('v.first_visit_at >= ?', $from)
				->where('v.first_visit_at < ?', $to)
				->where('v.store_id = ?', $storeId);

            if ($first) {
                if (version_compare(Mage::getVersion(), '1.4.0.0') < 0) {
                    $ignoreAgents = array();
                    $ignoreAgentsConfig = Mage::getConfig()->getNode('global/ignore_user_agents');
                    foreach ($ignoreAgents as $ignoreAgent) {
                        $ignoreAgents[] = (string) $ignoreAgent->innerXml();
                    }
                } else {
                    $ignoreAgents = Mage::getConfig()->getNode('global/ignore_user_agents')->asArray();
                }
                $select->where('vi.http_user_agent NOT IN (?)', $ignoreAgents);
            }
            $res = $this->_getReadAdapter()->fetchAll($select);
            foreach ($res as $result) {
                if (!preg_match("/" . Fooman_Jirafe_Model_Log_Visitor::USER_AGENT_BOT_PATTERN . "/i", $result['http_user_agent'])) {
                    $numVisitors++;
                }
            }
        } catch (Exception $e) {
			Mage::logException($e);
        }	
        return $numVisitors;
    }

    public function getStoreAbandonedCarts($storeId, $from, $to)
    {
        $abandonedCarts = array('abandoned_cart_num'=>0, 'abandoned_cart_grand_total'=>0);
		
		try {
			$select = $this->_getReadAdapter()->select()
				->from(array('q'=>$this->getTable('sales/quote')), array())
				->joinLeft(array('o'=>$this->getTable('sales/order')), 'q.entity_id=o.quote_id', array())
				->columns('COUNT(q.entity_id) AS abandoned_cart_num')
				->columns('SUM(q.base_grand_total) AS abandoned_cart_grand_total')
				->where('o.quote_id IS NULL')
				->where('q.created_at >= ?', $from)
				->where('q.created_at < ?', $to)
				->where('q.store_id = ?', $storeId)
				->where('q.is_active = 1');

            $res = $this->_getReadAdapter()->fetchRow($select);
			
			foreach (array('abandoned_cart_num', 'abandoned_cart_grand_total') as $value) {
				if (!empty($res[$value])) {
					$abandonedCarts[$value] = $res[$value];
				}
			}
        } catch (Exception $e) {
            // Older version of DB.  We need to modify to ensure we run the correct query for all versions of DB
			Mage::logException($e);
        }
		
        return $abandonedCarts;
    }
	
    public function getReport($storeId, $day)
    {
		try {
			$select = $this->_getReadAdapter()->select()
				->from($this->getTable('foomanjirafe/report'))
				->where('store_id = ?', $storeId)
				->where('store_report_date = ?', $day);
				
			$res =  $this->_getReadAdapter()->fetchRow($select);
		} catch (Exception $e) {
			Mage::logException($e);
		}

        return $res ? $res : 0;
    }
}