<?php

/**
* 2007-2022 PrestaShop
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
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/models/ecConfiguratorCategoryClass.php');
require_once(dirname(__FILE__) . '/models/ecConfiguratorOptionClass.php');

class EcConfigurator extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ecConfigurator';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'E-cone';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        parent::__construct();

        $this->secure_key = Tools::encrypt($this->name);
        $this->displayName = $this->l('Econe Configurator');
        $this->description = $this->l('Display configurator section in product page to select related optional products');
        $this->confirmUninstall = $this->l('Your module Econe Configurator has been successfully uninstalled');

        $this->confCatManager = new ecConfiguratorCategoryClass();
        $this->confOptManager = new ecConfiguratorOptionClass();

    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
     public function install()
     {
         include(dirname(__FILE__) . '/sql/install.php');

         $installed = parent::install() &&
             $this->registerHook('header') &&
             $this->registerHook('backOfficeHeader') &&
             $this->registerHook('displayAdminProductsExtra') &&
             $this->registerHook('displayEcConfigurator') &&
             $this->registerHook('hookDisplayShoppingCart') &&
             $this->registerHook('actionProductUpdate') &&
             $this->registerHook('actionAfterDeleteProductInCart');

         if ($installed) {
           if (!Configuration::get('EC_CONFIGURATOR_CATEGORY_ID')) {
               Configuration::updateValue('EC_CONFIGURATOR_CATEGORY_ID', null);
           }
           return true;
         } else{
           // if some thing blocks the hook registration uninstall the module
           $this->uninstall();
           return false;
         }
     }

    public function uninstall()
    {
        $this->deleteTables();

        if (!parent::uninstall()) {
            return false;
        }

        return parent::uninstall() &&
          Configuration::deleteByName('EC_CONFIGURATOR_CATEGORY_ID');
    }

    private function deleteTables()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS
            `' . _DB_PREFIX_ . 'ec_configurator_categories`,
            `' . _DB_PREFIX_ . 'ec_configurator_options`');
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }


    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        $output = '';

        if (((bool)Tools::isSubmit('submitEcConfiguratorModule')) == true) {
            $output .= $this->postProcess();
        }

        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        $this->context->smarty->assign('module_dir', $this->_path);

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEcConfiguratorModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
      //recuperation de la liste catégorie pour sélection de la catégorie du configurateur
      $categories = Category::getCategories($this->context->language->id, true, false);

      //recuperation des options définies dans le configurateur
      $selected_cat_id = Configuration::get('EC_CONFIGURATOR_CATEGORY_ID');
      $category_tree = Category::getNestedCategories($selected_cat_id, $this->context->language->id);
      $childrenCat = $category_tree[$selected_cat_id]['children'];


      $opt_query = array();
      foreach ($childrenCat as $cat) {
        $ps_products = Product::getProducts(Context::getContext()->language->id, 0, 0, 'date_upd', 'ASC', $cat['id_category'], true);
        foreach ($ps_products as $option) {
          $opt_query[] = [
            'id' => $option['id_product'],
            'value' => $option['id_product'],
            'name' => $cat['name'].' - '.$option['name']
          ];
        }
      }

      $cat_query = array();
      $configurator_initialised = false ;//si selected_cat_id est null ou ne correspond à aucune catégorie

      foreach ($categories as $category) {
          //on récupere toutes les catégories de niveaux <=2
          if($category['level_depth'] <= 2){
            $cat_query[] = [
              'id' => $category['id_category'],
              'value' => $category['id_category'],
              'name' => $category['name'],
            ];
            //si configurator_cat_id est connu configurator est initialisé => affichage tab2
            if($category['id_category'] === $selected_cat_id){
              $configurator_initialised = true;
            }
          }
      }

      //construction du formulaire
      $tabs['configurator_category'] = $this->l('Configurator category');
      $fields[] = [
        'col' => 6,
        'tab' => 'configurator_category',
        'type' => 'select',
        'label' => $this->l('Configurator category'),
        'id' => 'configurator_cat_select',
        'name' => 'EC_CONFIGURATOR_CATEGORY_ID',
        'label' => $this->l('Configurator category ID'),
        'options' => [
            'query' => $cat_query,
            'id' => 'id',
            'name' => 'name'
        ]
      ];

      if($configurator_initialised){
        $tabs['global_assignment'] = $this->l('Global assignment');
        $tabFileds = [
          [
              'type' => 'html',
              'label' => $this->l('Notice'),
              'name' => 'Notice',
              'tab' => 'global_assignment',
              'form_group_class' => 'relop_notice',
              'html_content' =>
              "<p>
                <strong>Gagnez du temps en appliquant les options directement aux catégories :</strong><br>
                Sélectionnez dans la liste de droite les options à affecter, et dans la liste de gauche les catégories pour lesquelles vous souhaitez proposer l'option.<br>
                Le tour est joué !
              </p>"
          ],
          [
            'type' => 'checkbox_table',
            'name' => 'options[]',
            'class_block' => 'product_list',
            'configurator_cat_id' => $selected_cat_id,
            'label' => $this->l('Select options:'),
            'class_input' => 'select_products',
            'lang' => true,
            'tab' => 'global_assignment',
            'hint' => '',
            'search' => false,
            'display'=> true,
            'values' => [
              'query' => $opt_query,
              'id' => 'relopt_id',
              'name' => 'relopt',
              'default' => array()
            ]
          ],
          [
            'type'  => 'categories',
            'label' => $this->l('Apply selected options to categories'),
            'name'  => 'categories',
            'tab' => 'global_assignment',
            'form_group_class' => 'form_group_filter_category',
            'tree'  => [
              'id'  => 'categories-tree',
              'use_checkbox' => true,
              'use_search' => true,
              'selected_categories' => array()
            ],
          ]
        ];

        $fields = array_merge($fields, $tabFileds);
      }

      return array(
          'form' => array(
              'legend' => array(
                  'title' => $this->l('Settings'),
                  'icon' => 'icon-cogs',
              ),
              'tabs' => $tabs,
              'input' => $fields,
              'submit' => array(
                  'tab' => 'configurator_category',
                  'title' => $this->l('Save'),
              ),
          ),
      );

    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {

        $form_values = array(
            'EC_CONFIGURATOR_CATEGORY_ID' => Configuration::get('EC_CONFIGURATOR_CATEGORY_ID', null)
        );

        if(array_key_exists('options', $_POST) || array_key_exists('categories', $_POST)){
            $form_values['globalAssignment_options'] = ($_POST['options'] != null)?$_POST['options']:false;
            $form_values['globalAssignment_categories'] = ($_POST['categories'] != null)?$_POST['categories']:false;
        }

        return $form_values;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        Configuration::updateValue('EC_CONFIGURATOR_CATEGORY_ID', Tools::getValue('EC_CONFIGURATOR_CATEGORY_ID'));

        $result = $this->displayConfirmation($this->l('CONFIGURATOR_CATEGORY_ID successfully updated'));

        if(array_key_exists('globalAssignment_options', $form_values) || array_key_exists('globalAssignment_categories', $form_values)){

            //retourne erreur si pas d'options soumises
            if(!$form_values['globalAssignment_options']){
              return $this->displayError($this->l('No related options selected.'));
            }

            //retourne erreur si pas de categories soumises
            if(!$form_values['globalAssignment_categories']){
              return $this->displayError($this->l('No related categories selected.'));
            }

            //collect de l'id catégorie par defaut pour chaque options sélectionnée
            $opt_cat_ids = array();
            foreach($form_values['globalAssignment_options'] as $optId){
              //Ci dessous permet de gérer toutes les catégories du produit même celles qui n'est pas par défaut
              // $opt_full_categories = Product::getProductCategoriesFull($optId);
              // $opt_categories_ids = array_keys($opt_full_categories);
              // //on retire la categorie configurateur de la liste des categories de l'option
              // $opt_categories_ids = array_diff( $opt_categories_ids, [$configurator_cat_id] );
              // foreach(...

              $opt_product = new Product($optId);
              $opt_cat_ids[] = $opt_product->id_category_default;
            }

            $loops = 0;
            $errors = array();
            $deft_packaging = $this->l('Units');

            //enregistrement des options/catégories pour les produits appartenant aux catégories sélectionnées
            foreach($form_values['globalAssignment_categories'] as $catId){
              $products = Product::getProducts(Context::getContext()->language->id, 0, 0, 'date_upd', 'ASC', $catId, true);
              foreach($products as $product){
                $product_id = $product['id_product'];
                foreach($opt_cat_ids as $opt_cat_id){
                  $savedCat = $this->confCatManager->getCat($product_id, $opt_cat_id, 'id_configurator_category');
                  if(!$savedCat){
                    $cat_assignment = $this->confCatManager->saveCat($product_id, $opt_cat_id, $deft_packaging);
                    if(!$cat_assignment){
                      $errors[] = 'product-'.$product_id.'_opt_cat-'.$opt_cat_id;
                    }
                  }
                }

                foreach($form_values['globalAssignment_options'] as $opt_id){
                  $savedOpt = $this->confOptManager->getOpt($product_id, $opt_id, 'id_configurator_option');
                  if(!$savedOpt){
                    $opt_assignment = $this->confOptManager->saveOpt($product_id, $opt_id, true);
                    if(!$opt_assignment){
                      $errors[] = 'product-'.$product_id.'_opt-'.$opt_id;
                    }
                  }
                }
              $loops++;
              }
            }

            $success = $loops - count($errors);
            if($success < $total){
              return $this->displayError(count($errors).'/'.$loops.' '.$this->l('Product(s) have not been successfully updated with related option'));
            }

            return $this->displayConfirmation($success.'/'.$loops.' '.$this->l('Product(s) updated with related option'));
        }

        //$this->confOptManager = new ecConfiguratorOptionClass();

        $result = $this->displayConfirmation($this->l('CONFIGURATOR_CATEGORY_ID successfully updated'));

        $configurator_cat_id = $form_values['EC_CONFIGURATOR_CATEGORY_ID'];

        return $result;

    }


    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }


    public function hookActionProductUpdate($params)
    {
        $main_product_id = $_POST['id_product'];

        //enregistrement des categories
        $cat_packages = $_POST['opt_packaging'];
        $cat_quantities = $_POST['opt_qty'];
        $configurator_cat_ids = array_keys($cat_quantities);
        $active_cat = array_keys($_POST['active_cat']);

        foreach($configurator_cat_ids as $cat_id){
          $cat_active = (in_array($cat_id, $active_cat));
          $saveCat = $this->confCatManager->saveCat($main_product_id, $cat_id, $cat_packages[$cat_id], $cat_quantities[$cat_id], $cat_active);
        }

        //enregistrement des options
        $configurator_opt_ids = $_POST['configurator_opt'];
        $active_opt =$_POST['active_opt'];

        foreach($configurator_opt_ids as $opt_id){
          $opt_active = (in_array($opt_id, $active_opt));
          $saveOpt = $this->confOptManager->saveOpt($main_product_id, $opt_id, $opt_active);
        }

    }

    public function hookDisplayEcConfigurator($params)
    {
        $main_product_id = Tools::getValue('id_product');
        $main_product = new Product($main_product_id);
        $pictures = Product::getCover($main_product_id);

        $main_product_data = array(
            'id' => $main_product_id,
            'name' => $main_product->name[1],
            'price' => Product::getPriceStatic($main_product_id),
            'picture' => $this->context->link->getimageLink($main_product->link_rewrite, $pictures['id_image'], ImageType::getFormatedName('home'))
        );

        $img_dir = $this->context->smarty->getTemplateVars('img_ps_dir');

        $configurator_cat_id = Configuration::get('EC_CONFIGURATOR_CATEGORY_ID', null);
        $category_tree = Category::getNestedCategories($configurator_cat_id, $this->context->language->id);
        $configurator_categories = $category_tree[$configurator_cat_id]['children'];

        $tree = array();
        foreach ($configurator_categories as $cat) {
          //on regarde si la catégorie est paramétrée comme visible pour le produit principal
          $confCat = $this->confCatManager->getCat($main_product_id, $cat['id_category']);
          if ($confCat['active'] === '1' && $cat['active'] === '1'){
            $options = Product::getProducts(Context::getContext()->language->id, 0, 0, 'price', 'ASC', $cat['id_category'], true);

            $cat_options = array();
            foreach ($options as $option) {
                //on regarde si l'option est paramétrée comme visible pour le produit principal
                $confOpt = $this->confOptManager->getOpt($main_product_id, $option['id_product']);

                //condition de recuperation du produit
                if($confOpt['active'] == '1' && $option['active'] && $option['available_for_order']){
                    $images = Product::getCover($option['id_product']);
                    $cover = $this->context->link->getimageLink($option['link_rewrite'], $images['id_image'], ImageType::getFormatedName('home'));
                    $opt_product = new Product($option['id_product']);
                    $groups = $opt_product->getAttributesGroups($this->context->language->id);
                    $variations_libelle = (!empty($groups))?$groups[0]['public_group_name']:null;
                    //@TODO récupérer le libelle (nom_public de l'attribute) dans la langue du site !
                    $combinations = $opt_product->getAttributeCombinations($this->context->language->id);
                    $variations_groups = $this->buildVariationList($combinations, $option['link_rewrite']);

                    $cat_options[$option['id_product']] = array(
                        'product' => $option,
                        'product_cover' => $cover,
                        'product_price' => Product::getPriceStatic($option['id_product']),
                        'product_variations_title' => $variations_libelle,
                        'product_variations_groups' => $variations_groups
                    );
                }
            }

            $cat_cover = $img_dir . 'tmp/category_' . $cat['id_category'] . '.jpg';

            $tree[$cat['id_category']] = array(
                'category' => $cat,
                'category_cover' => $cat_cover,
                'category_qty' => $confCat['quantity'],
                'category_pack' => $confCat['packaging'],
                'category_options' => $cat_options
            );
          }
        }

        $ajax_url = $this->context->link->getModuleLink('ecConfigurator');
        $ajax_add_to_cart =  (bool)Tools::getValue('PS_BLOCK_CART_AJAX', Configuration::get('PS_BLOCK_CART_AJAX'));

        $this->context->smarty->assign(array(
            'tree'            => $tree,
            'main_product'    => $main_product_data,
            'secure_key' => $this->secure_key,
            'ajax_add_to_cart' => $ajax_add_to_cart,
            'ecConfigurator_controller_url' => $ajax_url
        ));

        return $this->display(__FILE__, 'views/templates/hook/ecConfigurator.tpl');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $configurator_cat_id = Configuration::get('EC_CONFIGURATOR_CATEGORY_ID', null);
        $main_product_id = Tools::getValue('id_product');
        $main_product = new Product($product_id);
        if ($main_product->id_category_default == $configurator_cat_id) {
            return "<div class='alert alert-warning'>Ce produit est déjà une option. Vous ne pouvez pas rattacher d'options à une option.</div>";
        }

        $category_tree = Category::getNestedCategories($configurator_cat_id, $this->context->language->id);
        $configurator_categories = $category_tree[$configurator_cat_id]['children'];

        $related_categories = array();
        foreach ($configurator_categories as $cat) {

            $product_options = Product::getProducts(Context::getContext()->language->id, 0, 0, 'date_upd', 'ASC', $cat['id_category'], true);

            $related_options = array();
            foreach ($product_options as $product_opt) {
              $configurator_opt = $this->confOptManager->getOpt($main_product_id, $product_opt['id_product']);

              $related_options[] = array(
                  'id_product' => $product_opt['id_product'],
                  'name' => $product_opt['name'],
                  'active' => $configurator_opt['active']
              );
            }

            $configurator_cat = $this->confCatManager->getCat($main_product_id, $cat['id_category']);

            //ddd($configurator_cat);

            $active = 0;
            $quantity = 0;
            if ($configurator_cat) {
                $active = $configurator_cat['active'];
                $quantity = $configurator_cat['quantity'];
                $packaging = $configurator_cat['packaging'];
            }

            $related_categories[] = array(
                'id_category' => $cat['id_category'],
                'name' => $cat['name'],
                'quantity' => $quantity,
                'packaging' => $packaging,
                'products' => $related_options,
                'active' => $active
            );
        }

        $this->context->smarty->assign(array(
            'related_categories'            => $related_categories,
            'mainproduct_id'    => $main_product_id
        ));

        return $this->display(__FILE__, 'views/templates/admin/configure_product.tpl');
    }

    private function buildVariationList($combinations, $link_rewrite)
    {
        $groups = array();
        foreach ($combinations as $combination) {

            $color_group = ($combination['is_color_group'] == '1') ? true : false;
            $attribute = null;
            $optionPix = $this->confOptManager->getOptionPictureId($combination['id_product_attribute']);
            if ($optionPix != null) {
                $path = $this->context->link->getimageLink($link_rewrite, $optionPix['id_image'], ImageType::getFormatedName('thickbox'));
                $attribute = $path;
            }

            if ($color_group) {
                $ps_attribute = new Attribute($combination['id_attribute'], $this->context->language->id);
                $combinationsDirectory = 'img/co';
                $attributeImg = $this->context->link->getimageLink($combination['id_attribute'], 'img/co');
                $attribute = ($ps_attribute->color != null)?$ps_attribute->color:$attributeImg;
            }

            $groups[$combination['id_attribute_group']]['type'] = ($color_group) ? 'color_group' : 'else';
            $groups[$combination['id_attribute_group']]['variations'][] = array(
                'libelle' => $combination['attribute_name'],
                'value' => $combination['id_product_attribute'],
                'attribute' => $attribute
            );
        }

        return $groups;
    }

    /**
    * hookDisplayShoppingCart
    *
    * Shopping cart extra button
    * Display some specific informations
    **/
    // public function hookDisplayShoppingCart($params)
    // {
    //   ppp("hookDisplayShoppingCart");
    //
    //   $products = $params['products'];
    //   $i = 0;
    //   foreach($products as $product){
    //     $test = $this->confOptManager->isOption($product['id_product']);
    //     ppp($test);
    //     $i++;
    //   }
    //
    //   if (!$this->context->cart->id){
    //     $cart = new Cart();
    //     $cart->id_customer = (int)($this->context->cookie->id_customer);
    //     $cart->id_address_delivery = (int)  (Address::getFirstCustomerAddressId($cart->id_customer));
    //     $cart->id_address_invoice = $cart->id_address_delivery;
    //     $cart->id_lang = (int)($this->context->cookie->id_lang);
    //     $cart->id_currency = (int)($this->context->cookie->id_currency);
    //     $cart->id_carrier = 1;
    //     $cart->recyclable = 0;
    //     $cart->gift = 0;
    //     $cart->add();
    //     $this->context->cookie->id_cart = (int)($cart->id);
    //     $this->context->cart = $cart;
    //   }
    //
    //   $cart = $this->context->cart;
    //
    //   ppp($cart);
    //
    //   return null;
    // }


}
