{*
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
*}


<div class="panel product-tab">
	<h3>{l s='Related options'}</h3>

{if !count($subcat)} 

<p>Merci de vérifier l'ID dans la configuration du module.</p>

{/if}

  {foreach from=$subcat item=input}
	<input type="hidden" name="related_options[]" value="{$input.id_category}"/>
	<div class="checkbox">
		<label for="opt_cat_{$input.id_category}">
			<input type="checkbox" name="checked[{$input.id_category}]" id="opt_cat_{$input.id_category}" value="1" {if $input.checked}checked{/if}>
			{$input.name}</label>
{* {$input|@var_dump} *}
			{* <div class="form-group">
				<label class="control-label col-lg-2">Quantité</label>
				<div class="col-lg-2">
					<input name="opt_qty[{$input.id_category}]" type="number" value="{$input.quantity}">
				</div>
				<label class="control-label col-lg-1">Conditionnement</label>
				<div class="col-lg-2">
					<input name="opt_packaging[{$input.id_category}]" type="text" value="{$input.packaging}">
				</div>
			</div> *}

		<div class="form-group">
				<div class="col-lg-1"><span class="pull-right">
		</span></div>
				<label class="control-label col-lg-2">
					Quantité
				</label>
				<div class="col-lg-3">
					<div class="input-group">
											<input name="opt_qty[{$input.id_category}]" type="text" value="{$input.quantity}">

						<span class="input-group-addon">conditionnement</span>
					<input name="opt_packaging[{$input.id_category}]" type="text" value="{$input.packaging}">
					</div>
				</div>
			</div>


		<p>Options associées</p>

	  	  {foreach from=$input.products item=product}

		<div class="form-group">
		<div class="col-lg-12">
			<div class="form-group">
				<div class="col-lg-1">
					<span class="pull-right">
											</span>
				</div>
				<label class="control-label col-lg-2" for="opt_visible_{$product.id_product}">
					<strong>{$product.name}</strong>
				</label>
				<div class="col-lg-9">
					<div class="checkbox">
						<label for="opt_visible_{$product.id_product}">
							<input type="checkbox" name="visible[]" id="opt_visible_{$product.id_product}" value="{$product.id_product}" {if $product.visible}checked{/if}>
							Visible</label>
					</div>
					
				</div>
			</div>
		</div>
	</div>
			{* <div class="form-group">
				<label class="control-label col-lg-3"> {$product.name}</label>
				<div class="input-group col-lg-2">
			<input type="checkbox" name="visible[]" id="opt_visible_{$product.id_product}" value="{$product.id_product}" {if $product.visible}checked{/if}>
				</div>
			</div> *}
		  {/foreach}


	</div>


  {/foreach}

	<div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay'}</button>
	</div>

</div>

