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
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

// $(document).on('click', 'input[name="categories[]"]', function(e){
//   var op_checked = ($(this).attr('checked') === 'checked')?'checked':false;
//   var node = $(this).parent().parent()[0];
//   var inputs = $(node).find('input');
//   $(inputs).attr('checked', op_checked);
// });

//$(document).ready(function(){
  //$('#uncheck_all', '#uncheck-all-categories-tree').click();
//});

$(document).on('click', '.nav li', function(e){
  e.preventDefault();

  //console.log('tab change');
  $('#uncheck_all').click();
  $('#uncheck-all-categories-tree').click();

  var configurator_id = $("#configurator_cat_select :selected").val();
  var configurator_node = $('input[name="categories[]"][value="'+configurator_id+'"]').parent().parent()[0];
  var inputs = $(configurator_node).find('input');
  $(inputs).attr('disabled', 'disabled');
});

$(document).on('click', '.product_list #check_all', function(e){
  e.preventDefault();
  var options_box = $('input[name="options[]"]');
  $(options_box).attr('checked', 'checked');
});

$(document).on('click', '.product_list #uncheck_all', function(e){
  e.preventDefault();
  var options_box = $('input[name="options[]"]');
  $(options_box).attr('checked', false);
});
