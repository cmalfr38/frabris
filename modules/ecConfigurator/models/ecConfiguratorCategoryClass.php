<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class ecConfiguratorCategoryClass extends ObjectModel
{
	  public $id_configurator_category;
	  public $id_product;
    public $id_category;
		public $packaging;
		public $quantity;
    public $checked;

    /**
    * @see ObjectModel::$definition
    */
    public static $definition = array(
        'table' => 'ec_configurator_categories',
        'primary' => 'id_configurator_category',
        'multilang' => false,
        'fields' => array(
            'id_configurator_category' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_category'=> array('type' => self::TYPE_INT, 'validate' => 'isInt'),
						'packaging'=> array('type' => self::TYPE_STRING),
						'quantity'=> array('type' => self::TYPE_INT, 'validate' => 'isInt'),
						'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
        ),
    );

    public function __construct($id_configurator_category = null, $id_lang = null, $id_shop, Context $context = null) {
        parent::__construct($id_configurator_category, $id_lang, $id_shop);
    }

    public function add($autodate = true, $nullValues = false) {
        $result = parent::add($autodate, $nullValues);
    }

		public function getCat($id_product, $id_category, $field = '*') {
			    $oSql = new DbQuery();
	        $oSql->select($field);
	        $oSql->from('ec_configurator_categories', 'c');
	        $oSql->where('c.id_product = '.(int)$id_product);
	        $oSql->where('c.id_category = '.(int)$id_category);

	        $result = Db::getInstance()->getRow($oSql);

					if(array_key_exists($field, $result)){
						$result = $result[$field];
					}

	        return $result;
    }

		public function saveCat($id_product, $id_category, $packaging, $quantity = 0, $active = true) {
			//by default : CREATE
			$method = 'insert';
      $where = null;
			$id_configurator_category = $this->getCat($id_product, $id_category, 'id_configurator_category');
			//if getCat->Id is defined : UPDATE
			if($id_configurator_category){
				$method = 'update';
				$where = "id_configurator_category = ". (int)$id_configurator_category;
			}

			$result = Db::getInstance()->$method('ec_configurator_categories', array(
          // 'id_configurator_category' => (int)$id_row,
          'id_product'      => (int)$id_product,
          'id_category'      => (int)$id_category,
					'packaging'			=> $packaging,
          'quantity'      => (int)$quantity,
          'active' => (bool)$active
      ), $where);

			return $result;
    }

		public function removeCat($id_product, $id_category) {
				$id_configurator_category = $this->getCat($id_product, $id_category, 'id_configurator_category');
				$result = Db::getInstance()->delete('ec_configurator_categories', 'id_configurator_category ='.$id_configurator_category);
				$this->context->controller->_errors[] = Tools::displayError('Error: SQL');

				return $result;
    }



		// public function getProductCategories($id_product) {
		//     $oSql = new DbQuery();
		//     $oSql->select('*');
		//     $oSql->from('ec_configurator_categories', 'c');
		//     $oSql->where('c.id_product = '.(int)$id_product);
		//     return Db::getInstance()->executeS($oSql);
		// }

}
