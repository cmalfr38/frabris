
<script type="text/javascript">
var relatedOptions_url = '{$related_options_controller_url}';
var secure_key = '{$secure_key}';
</script>


{* {$tree|@var_dump} *}


<!-- related_options module -->
{if !empty($tree)}
<section id="related_options_block" class="page-product-box">
  <div class="container">
      <div class="module-title h2">{l s='Les indispensables' mod='related_options'}</div>

      <div id="related-options-accordions">

        {counter assign=i start=0 print=false}
        {foreach from=$tree item=cat}
        {counter}
        {if $i == 1}
          {assign var=displayCat value = $cat.category.id_category}
          {* {$displayCat|@var_dump} *}
        {/if}


        {if $cat.category_options|@count >= 1}
        <div class="category-item {if $i == 1}active-category{/if}" data-category='{$cat.category.id_category}'>
          <div class="category-item-img"><i class="icon-plus"></i></div>
          <div class="category-item-content">
            <div class="category-title h2">{$cat.category.name}</div>

            <div>{$cat.category.description}</div>

            {if $cat.category_qty != 0}
            <div class="indicatif_qty">
              {l s='With this product you need' mod='related_options'} <strong>{$cat.category_qty} {$cat.category_pack}</strong>
            </div>
            {else}
              {*<div class="indicatif_qty indicatif_empty">{l s='Remember to calculate the necessary quantity of your option, before finalizing your order' mod='related_options'}</div>*}
            {/if}
            <div class="category-link">{$cat.category.description_bottom}</div>
            {* <a class ="show-options" {if $i == 1} style="display: none;" {/if}>{l s='About this option' mod='related_options'}</a> *}
          </div>

        <div class="options-list">
          {foreach from=$cat.category_options item=option}
          <div class="option-item" data-option="{$option.product.id_product}" {if $cat.category.id_category != $displayCat} style="display: none;" {/if}>





            <div class="option-item-flex">
            <div class="option-item-reset apear" style="display: none;" onclick="clearOpt(this)"><i class="icon-trash"></i></div>
            <div class="option-item-img disapear" style="background-image: url('{$option.product_cover}');background-size:cover"></div>
              <div class="option-item-content">
                <h3>{$option.product.name}</h3>
                <div class="disapear">
                {$option.product.description_short}
                </div>

                {foreach from=$option.product_variations_groups item=group}
                {if $group.type != "color_group"}
                <select name="attribute_{$option.product.id_product}" class="input-attribute disapear">

                  {foreach from=$group.variations item=var}
                  <option data-picture="{$var.attribute}" value="{$var.value}">{$var.libelle}</option>
                  {/foreach}
                </select>
                {/if}
                {/foreach}
              </div>


            </div>

            <div class="option-item-right">
              <div class="option-item-price">
                  <p class="unit-price">{l s='Prix unitaire' mod='related_options'}</p>
                  <p class="current-price" data-price="{$option.product_price|string_format:"%.2f"}" data-sign="{$currency->sign}">{$option.product_price|string_format:"%.2f"} {$currency->sign}</p>
                  <!-- <p class="previous-price">000.00 {$currency->sign}</p> -->
                </div>
              <div class="option-item-background">
                <div class="option-item-qty">
                  <label>{l s='Qty' mod='related_options'}</label>
                  <input type="button" value="-" class="button-minus" data-field="quantity">
                  <input type="number" value="0" name="quantity" class="quantity-field">
                  <input type="button" value="+" class="button-plus" data-field="quantity">
                </div>

                <div class="option-item-total">
                  <p class="label-price">Total ttc</p>
                  <p class="total-price" data-currency="{$currency->sign}">{0|string_format:"%.2f"} {$currency->sign}</p>
                </div>
              </div>
            </div>

            {foreach from=$option.product_variations_groups item=group}


            {if $group.type == "color_group"}
            <div class="option-variation-color disapear">
              <p class="variation-title">{$option.product_variations_title}</p>
              <ul data-var ='{$group.variations.0.value}'>
                {counter assign=i start=0 print=false}
                {foreach from=$group.variations item=var}
                {counter}

                {assign var='background' value="background-color:"|cat:$var.attribute}
                {if $var.attribute|strpos:"http"!== false}
                  {assign var=background value="background-size:cover;background-image: url('"|cat:$var.attribute|cat:"')"}
                {/if}

                <li class="var_radio" data-value="{$var.value}">
                  <label>
                    <input class="noUniform input-attribute" type="radio" name="attribute_{$option.product.id_product}" value="{$var.value}" {if $i == 1} checked="checked" {/if}>
                    <span class="swatch" style="{$background}"></span><br> {$var.libelle}
                  </label>
                </li>
                {/foreach}
              </ul>

              <div class="variation-add" style="display:none;">
                <button onclick="addVariation(this)">{l s='more color' mod='related_options'}</button>
              </div>

            </div>
            {/if}
            {/foreach}
          </div>
          {/foreach}
          {* <div class="option-add"  {if $i != 1} style="display: none;" {/if}>
            <button onclick="addOption(this)">{l s='show next option' mod='related_options'}</button>
          </div> *}
        </div>
        </div>
        {/if}
        {/foreach}



      </div>
    </div>


  <div id="summary_options" class="options-summary" style="display: none;">
    <div class="container">
      <div class="display-flex">


        {* <div class="summary-img" style="background-image: url('{$main_product.picture}');background-size:cover"></div> *}
        <div class="summary-content">
          <p class="h2">{$main_product.name}</p>
          <p>{l s='R??f??rence :'} <span>ID{$product->id|intval}</span></p>
        </div>
        <div class="summary-pr">
          <p class="product-price" data-price ="{$main_product.price}" data-currency="{$currency->sign}"><span>{$main_product.price|string_format:"%.2f"} {$currency->sign}</span> TTC</p>
          <p class="options-price"><span id="sum-opt-count">0</span> options : <span id="sum-opt-total">{0|string_format:"%.2f"}{$currency->sign}</span> TTC</p>
        </div>
        <div class="summary-tot">
          <p>{l s='Total :' mod='related_options'}</p>
          <p class="summary-total"><span id="summary-fullprice">{$main_product.price|string_format:"%.2f"} {$currency->sign}</span> TTC</p>
        </div>
        <div class="summary-price">
          <button id="cart-summary" data-main="{$main_product.id}"  {if $ajax_add_to_cart == 1} class="popup_mode" {/if}>{l s='add to cart' mod='related_options'}</button>
          <button id="quotes-cart-trigger">{l s='quote' mod='related_options'}</button>
        </div>

      </div>
    </div>
  </div>

</section>
{/if}
