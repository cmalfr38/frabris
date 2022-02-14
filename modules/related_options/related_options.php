<?php

/**
 * 2007-2021 PrestaShop
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
 *  @copyright 2007-2021 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/models/relatedOptionClass.php');
require_once(dirname(__FILE__) . '/models/relatedOptionVisibleClass.php');

class Related_options extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'related_options';
        $this->tab = 'administration';
        $this->version = '1.0.1';
        $this->author = 'E-cone';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        parent::__construct();

        $this->secure_key = Tools::encrypt($this->name);
        $this->displayName = $this->l('Related options');
        $this->description = $this->l('Options related to the product');
    }

    public function install()
    {
        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayAdminProductsExtra') &&
            $this->registerHook('displayProductOptions') &&
            $this->registerHook('actionAfterDeleteProductInCart') &&
            $this->registerHook('hookActionProductUpdate');

        if ($installed) {
          if (!Configuration::get('CONFIGURATOR_CATEGORY_ID')) {
              Configuration::updateValue('CONFIGURATOR_CATEGORY_ID', null);
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
        //include(dirname(__FILE__) . '/sql/uninstall.php');
        $this->deleteTables();

        if (!parent::uninstall()) {
            return false;
        }

        return parent::uninstall() &&
          Configuration::deleteByName('CONFIGURATOR_CATEGORY_ID');
          //Configuration::deleteByName('CONFIGURATOR_CATEGORY_ID');
    }

    private function deleteTables()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS
            `' . _DB_PREFIX_ . 'related_options`,
            `' . _DB_PREFIX_ . 'related_options_visible`');
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
        if (Tools::isSubmit('submitRelated_optionsModule'))
            $output .= $this->postProcess();

        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        $this->context->smarty->assign('module_dir', $this->_path);

        return $output . $this->renderForm();
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
        $helper->submit_action = 'submitRelated_optionsModule';

        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
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

        //recuperation des options définies dans le configurateur (@TODO par catégorie ?)
        $selected_cat_id = Configuration::get('CONFIGURATOR_CATEGORY_ID');
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
          'name' => 'CONFIGURATOR_CATEGORY_ID',
          'label' => $this->l('Configurator category ID'),
          'options' => [
              'query' => $cat_query,
              'id' => 'id',
              'name' => 'name'
          ]
        ];

        if($configurator_initialised){
          $tabs['categories_options'] = $this->l('Categories options');
          $tabFileds = [
            [
                'type' => 'html',
                'label' => $this->l('Notice'),
                'name' => 'Notice',
                'tab' => 'categories_options',
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
              'tab' => 'categories_options',
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
              'tab' => 'categories_options',
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
            'CONFIGURATOR_CATEGORY_ID' => Configuration::get('CONFIGURATOR_CATEGORY_ID', null)
        );

        if(array_key_exists('options', $_POST) && array_key_exists('categories', $_POST)){
          if($_POST['options'] != null && $_POST['categories'] != null){
            $form_values['importool_options'] = $_POST['options'];
            $form_values['importool_categories'] = $_POST['categories'];
          }
        }

        return $form_values;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {

        $form_values = $this->getConfigFormValues();
        Configuration::updateValue('CONFIGURATOR_CATEGORY_ID', Tools::getValue('CONFIGURATOR_CATEGORY_ID'));

        $result = $this->displayConfirmation($this->l('CONFIGURATOR_CATEGORY_ID successfully updated'));

        $configurator_cat_id = $form_values['CONFIGURATOR_CATEGORY_ID'];

        //importation des options sélectionnées par catégories
        if(array_key_exists('importool_options', $form_values) || array_key_exists('importool_categories', $form_values)){
          $relop_result = false;
          //retourne erreur si pas d'options soumises
          if(!array_key_exists('importool_options', $form_values) || !count($form_values)){
            return $this->displayError($this->l('No related options selected.'));
          }
          //retourne erreur si pas de categories soumises
          if(!array_key_exists('importool_categories', $form_values) || !count($form_values)){
            return $this->displayError($this->l('No category selected.'));
          }

          //TRAITEMENT
          $class = new RelatedOptionClass();
          $vis_class = new RelatedOptionVisibleClass();

          //récupération des id catégorie des options sélectionnées
          $all_opt_cat_ids = array();
          foreach($form_values['importool_options'] as $optId){
            //$opt_product = new Product($optId);
            $opt_full_categories = Product::getProductCategoriesFull($optId);
            $opt_categories_ids = array_keys($opt_full_categories);
            //on retire la categorie configurateur de la liste des categories de l'option
            $opt_categories_ids = array_diff( $opt_categories_ids, [$configurator_cat_id] );
            //on collecte toutes les catgories des options configurateur soumises...
            foreach($opt_categories_ids as $cat_id){
              $all_opt_cat_ids = array();
              if(!in_array($cat_id, $all_opt_cat_ids)){
                $all_opt_cat_ids[] = $cat_id;
              }
            }
          }

          //enregistrement des options pour les produits appartenant aux catégories sélectionnées
          foreach($form_values['importool_categories'] as $catId){
            $products = Product::getProducts(Context::getContext()->language->id, 0, 0, 'date_upd', 'ASC', $catId, true);
            foreach($products as $product){
              $product_id = $product['id_product'];
              foreach($all_opt_cat_ids as $cat_id){
                if(!$class->isRow($product_id, $cat_id)){
                  $relop_result[$product_id.'-'.$cat_id] = Db::getInstance()->insert('related_options', array(
                    'id_related_options' => (int)null,
                    'id_product'      => (int)$product_id,
                    'id_category'      => (int)$cat_id,
                    'quantity'      => (int)0,
                    'packaging' => '',
                    'checked' => (int)1
                  ));
                }
              }
              //toutes les options sont visibles par defaut
              $vis_class->setProductVisible($product_id, $form_values['importool_options']);
            }
          }

          $total = count($relop_result);
          $relop_values = array_values($relop_result);
          $balance = array_diff($relop_values, [1] );
          $errors = count($errors);
          $success = $total - $errors;

          if($success < $total){
            $result = $this->displayError($errors.'/'.$total.' '.$this->l('Product(s) have not been successfully updated with related option'));
          }else{
            $result = $this->displayConfirmation($success.'/'.$total.' '.$this->l('Product(s) updated with related option'));
          }

        }

        return $result;

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
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');

        $class = new RelatedOptionClass();

        $main_product_id = Tools::getValue('id_product');

        $configurator_cat_id = Configuration::get('CONFIGURATOR_CATEGORY_ID', null);

        $category_tree = Category::getNestedCategories($configurator_cat_id, $this->context->language->id);
        $subCat = $category_tree[$configurator_cat_id]['children'];

        $tree = array();
        foreach ($subCat as $cat) {
            $cat_related = $class->isRow($main_product_id, $cat['id_category']);
            if ($cat_related['checked'] == '1' && $cat['active'] == '1') {
                $tree[$cat['id_category']] = array(
                    'category' => $cat
                );
            }
        }

        $this->context->smarty->assign('options_array', $tree);
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $product_id = Tools::getValue('id_product');

        $configurator_cat_id = Configuration::get('CONFIGURATOR_CATEGORY_ID', null);

        $p_instance = new Product($product_id);
        if ($p_instance->id_category_default == $configurator_cat_id) {
            return "<div class='alert alert-warning'>Ce produit est déjà une option. Vous ne pouvez pas rattacher d'options à une option.</div>";
        }

        $category_tree = Category::getNestedCategories($configurator_cat_id, $this->context->language->id);

        $childrenCat = $category_tree[$configurator_cat_id]['children'];

        $class = new RelatedOptionClass();
        $visClass = new RelatedOptionVisibleClass();

        $subcat = array();
        foreach ($childrenCat as $cat) {

            $ps_products = Product::getProducts(Context::getContext()->language->id, 0, 0, 'date_upd', 'ASC', $cat['id_category'], true);

            $products = array();
            foreach ($ps_products as $option) {
                $opt_visible = $visClass->isRow($product_id, $option['id_product']);

                $products[] = array(
                    'id_product' => $option['id_product'],
                    'name' => $option['name'],
                    'visible' => $opt_visible['visible']
                );
            }

            $row = $class->isRow($product_id, $cat['id_category']);

            $checked = 0;
            $packaging = 0;
            $quantity = 0;
            if ($row != null) {
                $checked = $row['checked'];
                $packaging = $row['packaging'];
                $quantity = $row['quantity'];
            }

            $subcat[] = array(
                'id_category' => $cat['id_category'],
                'name' => $cat['name'],
                'quantity' => $quantity,
                'packaging' => $packaging,
                'products' => $products,
                'checked' => $checked
            );
        }

        $this->context->smarty->assign(array(
            'subcat'            => $subcat,
            'mainproduct_id'    => $product_id
        ));


        return $this->display(__FILE__, 'views/templates/admin/configure_product.tpl');
    }

    public function hookActionProductUpdate($params)
    {
        $options = $_POST['related_options'];

        $product_id = $_POST['id_product'];
        $checkeds = $_POST['checked'];

        $quantities = $_POST['opt_qty'];
        $packages = $_POST['opt_packaging'];

        $options_visible = $_POST['visible'];

        $vis_class = new RelatedOptionVisibleClass();
        $vis_class->setProductVisible($product_id, $options_visible);

        $class = new RelatedOptionClass();
        $class->setProductOptions($product_id, $options, $quantities, $packages, $checkeds);
    }

    public function hookDisplayProductOptions($params)
    {
        $class = new RelatedOptionClass();
        $visClass = new RelatedOptionVisibleClass();

        // // futur ajax start here
        //
        // $configurator_cat_id = Configuration::get('CONFIGURATOR_CATEGORY_ID', null);
        // $category_tree = Category::getNestedCategories($configurator_cat_id, $this->context->language->id);
        // $confCat = $category_tree[$configurator_cat_id]['children'];
        // $confCatIds = array_keys($subCat);
        //
        //
        // $products = $this->context->cart->getProducts();
        //
        // $cartIds = array();
        // foreach ($products as $product){
        //   $cartIds[] = (int)$product['id_product'];
        // }
        //
        //
        //
        // $products = $this->context->cart->getProducts();
        // foreach($products as $item){
        //   ppp($item);
        //   if(!array_key_exists($item['id_category_default'], $confCatIds) && !array_key_exists($item['id_product'], $cartIds)) {
        //
        //
        // }
        //
        // // ddd('stop!');
        // // futur ajax finish here

        $ajax_add_to_cart =  (bool)Tools::getValue('PS_BLOCK_CART_AJAX', Configuration::get('PS_BLOCK_CART_AJAX'));
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
        $configurator_cat_id = Configuration::get('CONFIGURATOR_CATEGORY_ID', null);

        $category_tree = Category::getNestedCategories($configurator_cat_id, $this->context->language->id);
        $subCat = $category_tree[$configurator_cat_id]['children'];

        $tree = array();
        foreach ($subCat as $cat) {
            //on regarde si la catégorie est paramétrée comme visible pour le produit principal
            $cat_related = $class->isRow($main_product_id, $cat['id_category']);
            $cat_visible = ($cat_related['checked'] == '1');
            if ($cat_related['checked'] == '1' && $cat['active'] == '1') {
                $products = Product::getProducts(Context::getContext()->language->id, 0, 0, 'price', 'ASC', $cat['id_category'], true);



                $cat_options = array();
                foreach ($products as $product) {
                    //on regarde si l'option est paramétrée comme visible pour le produit principal
                    $visItem = $visClass->isRow($main_product_id, $product['id_product']);

                    //condition de recuperation du produit
                    if ($visItem['visible'] == '1' && $product['active'] && $product['available_for_order']) {
                        $images = Product::getCover($product['id_product']);
                        $cover = $this->context->link->getimageLink($product['link_rewrite'], $images['id_image'], ImageType::getFormatedName('home'));

                        $p_instance = new Product($product['id_product']);

                        $groups = $p_instance->getAttributesGroups($this->context->language->id);

                        $combinations = $p_instance->getAttributeCombinations($this->context->language->id);

                        $variations_groups = $this->buildVariationList($combinations, $product['link_rewrite']);

                        $cat_options[$product['id_product']] = array(
                            'product' => $product,
                            'product_cover' => $cover,
                            'product_price' => Product::getPriceStatic($product['id_product']),
                            'product_variations_groups' => $variations_groups
                        );
                    }
                }

                $cat_cover = $img_dir . 'tmp/category_' . $cat['id_category'] . '.jpg';

                $tree[$cat['id_category']] = array(
                    'category' => $cat,
                    'category_cover' => $cat_cover,
                    'category_qty' => $cat_related['quantity'],
                    'category_pack' => $cat_related['packaging'],
                    'category_options' => $cat_options
                );
            }
        }

        $ajax_url = $this->context->link->getModuleLink('related_options');

        $this->context->smarty->assign(array(
            'tree'            => $tree,
            'main_product'    => $main_product_data,
            'secure_key' => $this->secure_key,
            'ajax_add_to_cart' => $ajax_add_to_cart,
            'related_options_controller_url' => $ajax_url
        ));

        return $this->display(__FILE__, 'views/templates/hook/related_options.tpl');
    }


    public function hookActionAfterDeleteProductInCart($params)
    {

        //@TODO clean related_product when main product is deleted from cart

        // if ($this->context->cart->nbProducts()) {
        //     $only_additional_products = true;
        //     foreach ($this->context->cart->getProducts() as $product) {
        //         if ($product['id_category_default'] != 10) {
        //             $only_additional_products = false;
        //             break;
        //         }
        //     }
        //     if ($only_additional_products) {
        //         $this->context->cart->delete();
        //     }
        // }
    }

    private function buildVariationList($combinations, $link_rewrite)
    {
        $class = new RelatedOptionClass();

        $groups = array();
        foreach ($combinations as $combination) {

            $color_group = ($combination['is_color_group'] == '1') ? true : false;
            $attribute = null;
            $optionPix = $class->getOptionPictureId($combination['id_product_attribute']);
            if ($optionPix != null) {
                $path = $this->context->link->getimageLink($link_rewrite, $optionPix['id_image'], ImageType::getFormatedName('thickbox'));
                $attribute = $path;
            }

            if ($color_group) {
                $ps_attribute = new Attribute($combination['id_attribute'], $this->context->language->id);
                $attribute = $ps_attribute->color;
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
}
