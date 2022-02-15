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

console.log('related_options JS fired !');

$(window).load(function() {
  $.uniform.restore(".noUniform");

  var timer;
  $('#summary_options, .category-item').hover(function(){
    clearTimeout(timer);
    $('#summary_options').fadeIn();
  }, function(){
    clearTimeout(timer);
    timer = setTimeout(function(){
      $('#summary_options').fadeOut();
    }, 1000);
  });

  var initCatAccordion = $('.category-item-img')[0];
  if(initCatAccordion){
      $(initCatAccordion).trigger("click");
  }

});

//déplier l'accordeon au click sur l'image
$(document).on("click", ".category-item-img", function(e) {
  var categoryBlock = this.parentNode;
  var title = categoryBlock.getElementsByClassName('category-title')[0];
  showOptionsList(title);
});
//deplier l'accorderon au click sur le titre ou le bouton "voir l'option"
$(document).on("click touchend", ".show-options, .category-title", function (e) {
  showOptionsList(this);
});

$(document).on("click touchend", ".button-plus, .button-minus", function(e) {
  qtyEdit(this);
});

$(document).on('change', '.option-item-content select', function (e) {
  //console.log('change selected');
  var select = e.target;
  //Recupere l'image du produit et l'afficher
  var pictureUri = select.options[select.selectedIndex].dataset.picture;

  //Actualisation de l'aperçu de l'image
  if(pictureUri){
    var parentBlock = select.parentNode.parentNode;
    var imgBlock = parentBlock.getElementsByClassName('option-item-img')[0];
    imgBlock.style.backgroundImage = "url("+pictureUri+")";
  }

});

$(document).on("click", ".var_radio", function (e) {
  var var_value = this.dataset.value;
  var ul = this.parentNode;
  ul.dataset.var = var_value;
});

function reduceOptions(){
  //console.log('reduceOptions');
  var accordions= document.getElementById('related-options-accordions');

  var blockCategories = accordions.getElementsByClassName('category-item');

  for (var i = 0; i < blockCategories.length; i++) {
    var blockCat = blockCategories[i];
    var addOptBtn = blockCat.getElementsByClassName('option-add')[0];
    if(addOptBtn){
      addOptBtn.style.display = 'none';
    }
    var indicatifQty = blockCat.getElementsByClassName('indicatif_qty')[0];
    if(indicatifQty){
      indicatifQty.style.display = 'none';
    }
    var categoryLink = blockCat.getElementsByClassName('category-link')[0];
    if(categoryLink){
      categoryLink.style.display = 'none';
    }
    var showOptBtn = blockCat.getElementsByClassName('show-options')[0];
    if(showOptBtn){
      showOptBtn.style.display = 'block';
    }

    var optList = blockCat.getElementsByClassName('option-item');

    for (var j = 0; j < optList.length; j++) {
      var opt = optList[j];
      //console.log(opt);
      var qtyBlock = opt.getElementsByClassName("option-item-qty")[0];
      var qty = qtyBlock.getElementsByClassName('quantity-field')[0].value;

      if(qty>0){
        opt.querySelectorAll('.apear').forEach(function(el) {
           el.style.display = 'block';
        });
        opt.querySelectorAll('.disapear').forEach(function(el) {
           el.style.display = 'none';
        });
      }else{
        opt.style.display = 'none';
      }
    }

  }

}


function getCountClone(optionId){
  var accordions= document.getElementById('related-options-accordions');
  var optList = accordions.getElementsByClassName('option-item');
  var count = 0;

  for (var i = 0; i < optList.length; i++) {
    var opt = optList[i];
    if(opt.dataset.option == optionId){
      count++;
    };
  }

  return count;
}



function addVariation(clicked){
  //console.log('addVariation');
  var optBlock = clicked.closest('.option-item');
  var optionId = optBlock.dataset.option;

  //nombre max autorisé
  var max = 5;
  //compte le nombre de fois que l'attribut est utilisé
  var count = getCountClone(optionId);
  if(count >= 5){
    //@TODO créer et récupérer la traduction dynamiquement
    alert('too much colors!');
  }else{
    var clonedBlock = optBlock.cloneNode(true);
    var quantityField = clonedBlock.getElementsByClassName('quantity-field')[0];
    quantityField.value = 0;

    //renommer le name des radio
    var items = clonedBlock.getElementsByClassName('var_radio');
    for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var input = item.getElementsByClassName('input-attribute')[0];
        input.name = input.name + '-' + count;
    }

    //on insere le clone a la suite
    optBlock.parentNode.insertBefore(clonedBlock, optBlock.nextSibling);

  }

  // $(document).on("click", ".var_radio", function (e) {
  //   var var_value = this.dataset.value;
  //   var ul = this.parentNode;
  //   ul.dataset.var = var_value;
  // });


}


function clearOpt(clicked){
  var nullQty = 0;
  var optBlock = clicked.closest('.option-item');
  var inputQty = optBlock.getElementsByClassName('quantity-field')[0];
  inputQty.value = nullQty;

  var opt_total_price = optBlock.getElementsByClassName('total-price')[0];
  var currency = opt_total_price.dataset.currency;
  var opt_unit_price = optBlock.getElementsByClassName('current-price')[0].dataset.price;
  var totalPriceAmount = nullQty*opt_unit_price;
  var roundPrice = (Math.round(totalPriceAmount * 100) / 100).toFixed(2);
  var displayTotalPrice = roundPrice+' '+currency;
  var opt_total_price = optBlock.getElementsByClassName('total-price')[0].innerText = displayTotalPrice;
  updateSummary();
  reduceOptions();
}

function showOptionsList(clicked){
  reduceOptions();
  var optionsBlock = clicked.parentNode.parentNode;


  if (optionsBlock.classList.contains('active-category')) {
    optionsBlock.classList.remove('active-category');
    //console.log('class active-category has been removed');
  }else{
    optionsBlock.classList.add('active-category');

    var clearBtn = optionsBlock.getElementsByClassName('option-item-reset')[0];
    if(clearBtn){
      clearBtn.style.display = 'none';
    }

    var showOptBtn = optionsBlock.getElementsByClassName('show-options')[0];
    if(showOptBtn){
      showOptBtn.style.display = 'none';
    }

    var addoptbtn = optionsBlock.getElementsByClassName('option-add')[0];
    if(addoptbtn){
      addoptbtn.style.display = 'block';
    }

    var indicatifQty = optionsBlock.getElementsByClassName('indicatif_qty')[0];
    if(indicatifQty){
      indicatifQty.style.display = 'block';
    }

    var categoryLink = optionsBlock.getElementsByClassName('category-link')[0];
    if(categoryLink){
      categoryLink.style.display = 'block';
    }

    var optList = optionsBlock.getElementsByClassName('option-item');
    if(optList){
      for (var i = 0; i < optList.length; i++) {
        var opt = optList[i];
        //console.log(opt);
        opt.querySelectorAll('.disapear').forEach(function(el) {
           el.style.display = '';
        });
        opt.style.display = '';
      }
    }
  }

}

function addOption(clicked){
  //console.log('addOption');
  var categoryBlock = clicked.parentNode.parentNode.parentNode;
  var nextCategoryBlock = categoryBlock.nextElementSibling;

  if(nextCategoryBlock != null) {
    var nextCategoryTitle = nextCategoryBlock.getElementsByClassName('category-title')[0];
    showOptionsList(nextCategoryTitle);
  }else{
    reduceOptions();
  }
}

function getVariationValue(var_input){

  var value = null;
  if (var_input != null){

    if(var_input.tagName === 'INPUT' && var_input.type === 'radio'){
      //value = var_input.value;
      value = var_input.parentNode.parentNode.parentNode.dataset.var
    }

    if(var_input.tagName === 'SELECT'){
      value = var_input.options[var_input.selectedIndex].value;
    }
  }

  return value;
}



function qtyEdit(clicked){
  //console.log("qtyEdit");

  var optBlock = clicked.closest('.option-item');
  var qtyBlock = clicked.closest('.option-item-qty');

  var inputQty = qtyBlock.getElementsByClassName('quantity-field')[0];
  var inputVal = inputQty.value;
  var isMinus = clicked.classList.contains('button-minus');
  var addVal = isMinus ? -1:1;

  if(isMinus && inputVal == 0){
    addVal = 0;
  }

  var newVal = parseInt(inputQty.value, 10) + addVal;
  inputQty.value =  newVal <= 0 ? 0 : newVal;

  variationBlockCustom(optBlock, newVal)

  if(newVal <= 0){
    optBlock.classList.remove('cartable');
  }else{
    optBlock.classList.add('cartable');
  }

  var opt_total_price = optBlock.getElementsByClassName('total-price')[0];
  var currency = opt_total_price.dataset.currency;

  var opt_unit_price = optBlock.getElementsByClassName('current-price')[0].dataset.price;
  var totalPriceAmount = newVal*opt_unit_price;
  var roundPrice = (Math.round(totalPriceAmount * 100) / 100).toFixed(2);

  var displayTotalPrice = roundPrice+' '+currency;

  var opt_total_price = optBlock.getElementsByClassName('total-price')[0].innerText = displayTotalPrice;
  updateSummary();
}

function variationBlockCustom(optBlock, newVal){
  //si quantité passe au dessus de 0 et que le block est de type 'variation' (couleurs) alors on affiche le bouton pour cloner.
  //si quantité passe en dessous de 1 et que le block est cloné alors on supprime le block.
  //si quantité passe en dessous de 1 et que le block est seul alors on masque le bouton pour cloner le block.

  var isVariation = optBlock.getElementsByClassName('variation-add');
  var variationBtn = optBlock.getElementsByClassName('variation-add')[0];

  if(isVariation.length){
    var optionId = optBlock.dataset.option;
    var countClone = getCountClone(optionId);
    if(newVal < 1){
      if(countClone > 1){
        optBlock.parentNode.removeChild( optBlock );
      }else{
        variationBtn.style.display = 'none';
      }
    }else{
      variationBtn.style.display = 'block';
    }
  }
}

function updateSummary(){
  //console.log('updateSummary2');
  var summary_block = document.getElementById('summary_options');
  var mainProduct_price = summary_block.getElementsByClassName('product-price')[0].dataset.price;
  var currency = summary_block.getElementsByClassName('product-price')[0].dataset.currency;

  //var countQtyOptions = countQtyOptBlock();
  var countQtyOptions = countOptQties();
  document.getElementById('sum-opt-count').innerText = countQtyOptions;

  var totalOptionsAmount = optTotalAmount();
  document.getElementById('sum-opt-total').innerText = totalOptionsAmount+' '+currency;

  var fullPrice = parseFloat(totalOptionsAmount) + parseFloat(mainProduct_price);
  document.getElementById('summary-fullprice').innerText = fullPrice.toFixed(2)+' '+currency;
}




function optTotalAmount(){
  //console.log('optTotalAmount');
  var optPrices = document.getElementsByClassName('total-price');

  var totalAmount = 0
  for (var i = 0; i < optPrices.length; i++) {
      var optPriceBlock = optPrices[i];
      var optPriceStr = optPriceBlock.textContent
      var optPriceNum = optPriceStr.substring(0, optPriceStr.length -2);
      totalAmount = totalAmount + parseFloat(optPriceNum);
  }
  return totalAmount.toFixed(2);
}


function countOptQties(){
  var optQtyFields = document.getElementsByClassName('quantity-field');
  qties = 0;
  for (var i = 0; i < optQtyFields.length; i++) {
    var qtyField = optQtyFields[i];
    qties = qties + parseInt(qtyField.value);
  }
  return qties;
}

function countQtyOptBlock(){
  //compte 1 fois chaque bloque des qu'il a une quantité
  var optQtyFields = document.getElementsByClassName('quantity-field');
  var count = 0;
  for (var i = 0; i < optQtyFields.length; i++) {
    var qtyField = optQtyFields[i];
      if(qtyField.value > 0){
        count++;
      }
  }
  return count;
}



$(document).on("click touchend", "#cart-summary", function(e) {
  //console.log('cart-summary');
  var cart_url = document.getElementById('buy_block').action;
  var popup = this.classList.contains('popup_mode');
  var product_id = this.dataset.main;

  var toCart = [];
  var main_qty = 1;

  if(popup){
    main_qty = 0;
  }

  var mainProduct = {
    product_id: product_id,
    type:'main',
    var_id: null,
    qty: main_qty,
  };
  toCart.push(mainProduct);


  var catOptionBlocks = document.getElementsByClassName('options-list');
  for (var i = 0; i < catOptionBlocks.length; i++) {
     var optionBlocks = catOptionBlocks[i].getElementsByClassName('option-item');

     for (var j = 0; j < optionBlocks.length; j++) {
         var optionBlock = optionBlocks[j];
         var product_id = optionBlock.dataset.option;
         var qty = optionBlock.getElementsByClassName('quantity-field')[0].value;
         var var_input = optionBlock.getElementsByClassName('input-attribute')[0];
         // var var_input = optionBlock.getElementsByClassName('var_list')[0];
         //
         // console.log(var_input);

         var var_id = getVariationValue(var_input);

         var option = {
           product_id: product_id,
           type:'option',
           var_id: var_id,
           qty: qty
         };

         if(qty != 0){
           toCart.push(option);
         }
     }
   }


   $.ajax({
       url: relatedOptions_url + '?action=suboptocart&secure_key=' + secure_key + '&rand=' + new Date().getTime(),
       data: {tocart : toCart},//data: $('#id_bsaimmediaterecall_form').serialize(),
       type: 'POST',
       headers: { "cache-control": "no-cache" },
       dataType: "json",
       success: function(data){
          //console.log(data);
           if(data.success){
             if(!popup){
               window.location.href = cart_url;
             } else{
               var cartForm = document.getElementById('buy_block');
               var submitBtn = cartForm.querySelectorAll('[type="submit"]')[0];
               submitBtn.click();
             }
           }
       },
       error: function (xhr, ajaxOptions, thrownError) {
         console.warn('AJAX : an error occured');
         //console.log(xhr);
      }
   });
});
