{extends file="helpers/form/form.tpl"}
{block name="input_row"}
  {if $input.type == 'checkbox_table'}

    {assign var=id value=$input.values.id}
    {assign var=name value=$input.values.name}
    {assign var=opt_selected value=$input.values.default}
    {assign var=options value=$input.values.query}

    {if isset($options) && count($options) > 0}
      <div class="form-group {$input.class_block|escape:'html'}" data-configurator ="{$input.configurator_cat_id}"  {if isset($input.tab)}data-tab-id="{$input.tab|escape:'htmlall':'UTF-8'}"{/if} >
        <label class="control-label col-lg-3">
        <span class="{if $input.hint}label-tooltip{else}control-label{/if}" data-toggle="tooltip" data-html="true" title="" data-original-title="{$input.hint|escape:'htmlall':'UTF-8'}">
          {$input.label|escape:'htmlall':'UTF-8'}
        </span>
        </label>
        <div class="col-lg-9">
          <div class="row">
            <div class="col-lg-6">
              <table class="table table-bordered">
                <thead>
                <tr>
                  <th>box</th>
                  <th>Id</th>
                  <th>Option</th>
                  <th style="float: right;">
                    <a href="#" id="check_all" class="btn btn-default"><i class="icon-check-sign"></i> {l s='Check all'  mod='ecConfigurator'}</a>
                    &nbsp;
                    <a href="#" id="uncheck_all" class="btn btn-default"><i class="icon-check-empty"></i> {l s='Uncheck All'  mod='ecConfigurator'}</a>
                  </th>
                  {if $input.search}
                  <th>
                    <span class="title_box">
                        <input type="text" class="search_checkbox_table" placeholder="{l s='search...'  mod='ecConfigurator'}">
                    </span>
                  </th>
                  {/if}
                </tr>
                </thead>
                <tbody>
                {foreach $options as $key => $option}
                  <tr>
                    <td>
                      <input type="checkbox" class="{$input.type|escape:'htmlall':'UTF-8'} {$input.class_input|escape:'htmlall':'UTF-8'}" name="{$input.name|escape:'htmlall':'UTF-8'}" id="{$id|escape:'htmlall':'UTF-8'}_{$option.id|escape:'htmlall':'UTF-8'}" value="{$option['value']|escape:'htmlall':'UTF-8'}" {if $option.value && in_array($opt_selected, $option.value)}checked="checked" {/if} />
                    </td>
                    <td>{$option.id|escape:'htmlall':'UTF-8'}</td>
                    <td>
                      <label for="{$id|escape:'htmlall':'UTF-8'}_{$option.id|escape:'htmlall':'UTF-8'}">
                          {$option['name']|escape:'htmlall':'UTF-8'}
                      </label>
                    </td>
                  </tr>
                {/foreach}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    {/if}
  {elseif $input.type == 'checkbox_fields'}
    <div class="form-group {$input.class_block|escape:'htmlall':'UTF-8'}" {if isset($input.tab)}data-tab-id="{$input.tab|escape:'htmlall':'UTF-8'}"{/if} {if $input.display}style="display: block" {/if}>
      <div class="col-lg-9 col-lg-offset-3">
        {foreach $input['values']['query'] as $field}
          <div class="checkbox {$input.class|escape:'htmlall':'UTF-8'}">
            <label>
              <input type="checkbox" {if isset($input['values']['checked'][$field[$input['values']['value']]])}checked="checked" {/if}  {if isset($field['disabled'])}disabled="disabled" {/if} name="field[{$field[$input['values']['value']]|escape:'htmlall':'UTF-8'}]" value="{$field[$input['values']['name']]|escape:'htmlall':'UTF-8'}">
              {if isset($field['disabled'])}<input type="hidden" name="field[{$field[$input['values']['value']]|escape:'htmlall':'UTF-8'}]" value="{$field[$input['values']['name']]|escape:'htmlall':'UTF-8'}" >{/if}
              {$field[$input['values']['name']]|escape:'htmlall':'UTF-8'}
            </label>
          </div>
        {/foreach}
      </div>
    </div>
  {else}
    {$smarty.block.parent}
  {/if}
{/block}
