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

class ecConfiguratorOptionClass extends ObjectModel
{
	  public $id_configurator_option;
	  public $id_product;
    public $id_option;
    public $active;

    /**
    * @see ObjectModel::$definition
    */
    public static $definition = array(
        'table' => 'ec_configurator_options',
        'primary' => 'id_configurator_option',
        'multilang' => false,
        'fields' => array(
            'id_configurator_option' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_option'=> array('type' => self::TYPE_INT, 'validate' => 'isInt'),
						'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
        ),
    );

    public function __construct($id_configurator_option = null, $id_lang = null, $id_shop, Context $context = null) {
        parent::__construct($id_configurator_option, $id_lang, $id_shop);
    }

    public function add($autodate = true, $nullValues = false) {
        $result = parent::add($autodate, $nullValues);
    }

		public function getOpt($id_product, $id_option, $field = '*') {
			    $oSql = new DbQuery();
	        $oSql->select($field);
	        $oSql->from('ec_configurator_options', 'o');
	        $oSql->where('o.id_product = '.(int)$id_product);
	        $oSql->where('o.id_option = '.(int)$id_option);

	        $result = Db::getInstance()->getRow($oSql);

					if(array_key_exists($field, $result)){
						$result = $result[$field];
					}

	        return $result;
    }

		public function saveOpt($id_product, $id_option, $active) {
			//by default : CREATE
			$method = 'insert';
      $where = null;
			$id_configurator_option = $this->getOpt($id_product, $id_option, 'id_configurator_option');
			//if getCat->Id is defined : UPDATE
			if($id_configurator_option){
				$method = 'update';
				$where = "id_configurator_option = ". (int)$id_configurator_option;
			}

			$result = Db::getInstance()->$method('ec_configurator_options', array(
          // 'id_configurator_option' => (int)$id_row,
          'id_product'      => (int)$id_product,
          'id_option'      => (int)$id_option,
          'active' => (bool)$active
      ), $where);

			return $result;
    }

		public function removeOpt($id_product, $id_option) {
				$id_configurator_option = $this->getOpt($id_product, $id_option, 'id_configurator_option');
				$result = Db::getInstance()->delete('ec_configurator_options', 'id_configurator_option ='.$id_configurator_option);
				$this->context->controller->_errors[] = Tools::displayError('Error: SQL');

				return $result;
    }

		public function getOptionPictureId($id_product_attribute) {
				$oSql = new DbQuery();
				$oSql->select('id_image');
				$oSql->from('product_attribute_image', 'p');
				$oSql->where('p.id_product_attribute = '.(int)$id_product_attribute);
				//return Db::getInstance()->executeS($oSql);
				$result = Db::getInstance()->getRow($oSql);

				return $result;
		}


}
