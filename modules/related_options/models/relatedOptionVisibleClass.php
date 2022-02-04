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

class RelatedOptionVisibleClass extends ObjectModel
{

    public $id_related_options_visible;
    public $id_product;
	public $id_option;
    public $visible;

    /**
    * @see ObjectModel::$definition
    */
    public static $definition = array(
        'table' => 'related_options_visible',
        'primary' => 'id_related_options_visible',
        'multilang' => false,
        'fields' => array(
            'id_related_options_visible' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_option' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'visible'=> array('type' => self::TYPE_INT, 'validate' => 'isInt'),
        ),
    );

    public function __construct($id_related_options_visible = null, $id_lang = null, $id_shop, Context $context = null) {
        parent::__construct($id_related_options_visible, $id_lang, $id_shop);
    }

    public function add($autodate = true, $nullValues = false) {
        $result = parent::add($autodate, $nullValues);
    }




    public function getProductOptions($product_id) {
        $oSql = new DbQuery();
        $oSql->select('*');
        $oSql->from('related_options_visible', 'r');
        $oSql->where('r.id_product = '.(int)$product_id);

        $result = Db::getInstance()->executeS($oSql);

        return $result;

    }

    public function clearOptionsVisibility($product_id){


        $options = $this->getProductOptions($product_id);

        foreach($options as $opt){
            if(!Db::getInstance()->update('related_options_visible', array(
                'visible' => 0
            ), "id_related_options_visible =". (int)$opt['id_related_options_visible']));
            $this->context->controller->_errors[] = Tools::displayError('Error: SQL');
        }

    }

    public function isRow($product_id, $option_id) {
        $oSql = new DbQuery();
        $oSql->select('*');
        $oSql->from('related_options_visible', 'r');
        $oSql->where('r.id_product = '.(int)$product_id);
        $oSql->where('r.id_option = '.(int)$option_id);

        $result = Db::getInstance()->getRow($oSql);

        return $result;
    }

    public function setProductVisible($product_id, $options_visible){

        $this->clearOptionsVisibility($product_id);

        foreach($options_visible as $opt){

            $exist = $this->isRow($product_id, $opt);

            $method = 'insert';
            $id_row = null;
            $where = null;
            if($exist != false){
                $method = 'update';
                $id_row = $exist['id_related_options_visible'];
                $where = "id_related_options_visible =". (int)$id_row;
            }
            Db::getInstance()->$method('related_options_visible', array(
                'id_related_options_visible' => (int)$id_row,
                'id_product'      => (int)$product_id,
                'id_option'      => (int)$opt,
                'visible' => (int)1
            ), $where);
        }
    }

    // public function createQtyOptions($product_id, $option_id, $option_visible) {

    //     $exist = $this->getQtyOptions($product_id, $option_id);

    //     $method = 'insert';
    //     $id_roqy = null;
    //     $where = null;
    //     if($exist != false){
    //         $method = 'update';
    //         $id_roqy = $exist['id_related_options_visible'];
    //         $where = "id_related_options_visible =". (int)$id_roqy;
    //     }


    //     Db::getInstance()->$method('related_options_visible', array(
    //         'id_related_options_visible' => $id_roqy,
    //         'id_product' => (int)$product_id,
    //         'id_option'      => (int)$option_id,
    //         'visible'      => (int)$option_visible,
    //     ), $where);

    // }


}
