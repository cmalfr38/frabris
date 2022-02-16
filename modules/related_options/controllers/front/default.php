<?php
/*
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class Related_OptionsDefaultModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        $this->context = Context::getContext();
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::isSubmit('action')) {
            switch(Tools::getValue('action')) {
                case 'suboptocart':
                    $this->ajaxProcessSubmitActionToCart();
                    break;
            }
        }
    }

    protected function ajaxProcessSubmitActionToCart()
    {
        $toCart = Tools::getValue('tocart');

        if (!$this->context->cart->id){
          $cart = new Cart();
          $cart->id_customer = (int)($this->context->cookie->id_customer);
          $cart->id_address_delivery = (int)  (Address::getFirstCustomerAddressId($cart->id_customer));
          $cart->id_address_invoice = $cart->id_address_delivery;
          $cart->id_lang = (int)($this->context->cookie->id_lang);
          $cart->id_currency = (int)($this->context->cookie->id_currency);
          $cart->id_carrier = 1;
          $cart->recyclable = 0;
          $cart->gift = 0;
          $cart->add();
          $this->context->cookie->id_cart = (int)($cart->id);
          $this->context->cart = $cart;
        }

        $cart = $this->context->cart;

        //on collecte tous les id des produits dans le panier
        $products = $cart->getProducts();
        $cartIds = array();
        foreach ($products as $product){
          $cartIds[] = (int)$product['id_product'];
        }

        $count_op = 0;
        if(is_array($toCart)){
          foreach($toCart as $item){
            if($item['type'] === 'main' && in_array($item['product_id'], $cartIds)){
              $op = $cart->updateQty(1, $item['product_id'],  $item['var_id'], false, 'down');
            }else{
              $op = $cart->updateQty($item['qty'], $item['product_id'],  $item['var_id']);
            }

            if($op === true){
              $count_op++;
            }
          }

          $count = count($toCart);
          $success = ($count_op == $count);
        }

        echo Tools::jsonEncode(array(
          'success' => $success,
          'op_success' => $count_op,
          'count_products' => $count
        ));
    		exit;

    }
}
