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

class RelatedOptionClass extends ObjectModel
{
	public $id_related_options;
	public $id_product;
    public $id_category;
    public $quantity;
    public $packaging;
    public $checked;

    /**
    * @see ObjectModel::$definition
    */
    public static $definition = array(
        'table' => 'related_options',
        'primary' => 'id_related_options',
        'multilang' => false,
        'fields' => array(
            'id_related_options' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_category'=> array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'quantity'=> array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'packaging'=> array('type' => self::TYPE_STRING),
            'checked' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
        ),
    );

    public function __construct($id_related_options = null, $id_lang = null, $id_shop, Context $context = null) {
        parent::__construct($id_related_options, $id_lang, $id_shop);
    }

    public function add($autodate = true, $nullValues = false) {
        $result = parent::add($autodate, $nullValues);
    }


    public function removeOpt($id) {
        Db::getInstance()->delete('related_options', 'id_related_options ='.$id);
    }

    public function getProductCategories($product_id) {
        $oSql = new DbQuery();
        $oSql->select('*');
        $oSql->from('related_options', 'r');
        $oSql->where('r.id_product = '.(int)$product_id);
        return Db::getInstance()->executeS($oSql);
    }

    public function clearProductCategories($product_id){
        $categories = $this->getProductCategories($product_id);
        // return $categories;

        foreach($categories as $cat){
            // if(!Db::getInstance()->delete('related_options', 'id_related_options ='.$cat['id_related_options']));

            if(!Db::getInstance()->update('related_options_quantity', array(
                'checked' => 0
            ), "id_related_options =". (int)$cat['id_related_options']));
            $this->context->controller->_errors[] = Tools::displayError('Error: SQL');
        }

    }

    public function isRow($product_id, $category_id) {
        $oSql = new DbQuery();
        $oSql->select('*');
        $oSql->from('related_options', 'r');
        $oSql->where('r.id_product = '.(int)$product_id);
        $oSql->where('r.id_category = '.(int)$category_id);


        $result = Db::getInstance()->getRow($oSql);

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

    public function setProductOptions($product_id, $categories, $quantities, $packages, $checkeds){
        //$this->clearProductCategories($product_id);

        foreach($categories as $cat){

            $exist = $this->isRow($product_id, $cat);

            $method = 'insert';
            $id_row = null;
            $where = null;
            if($exist != false){
                $method = 'update';
                $id_row = $exist['id_related_options'];
                $where = "id_related_options =". (int)$id_row;
            }
            Db::getInstance()->$method('related_options', array(
                'id_related_options' => (int)$id_row,
                'id_product'      => (int)$product_id,
                'id_category'      => (int)$cat,
                'quantity'      => (int)$quantities[$cat],
                'packaging' => $packages[$cat],
                'checked' => (int)$checkeds[$cat]
            ), $where);
        }
    }


}
